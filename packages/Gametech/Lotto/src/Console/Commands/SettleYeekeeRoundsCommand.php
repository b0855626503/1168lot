<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Models\YeekeeShoot;
use Gametech\Lotto\Services\DrawCancelAllRefundService;
use Gametech\Lotto\Services\DrawService;
use Gametech\Lotto\Services\SettlementService;
use Gametech\Lotto\Services\YeekeeResultEngineService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SettleYeekeeRoundsCommand extends Command
{
    protected $signature = 'lotto:settle-yeekee-rounds
        {--market_id= : Process only one yeekee market}
        {--draw_id= : Process only one draw}
        {--limit=100 : Maximum rounds per run}
        {--dry-run : Preview only without write}';

    protected $description = 'Resolve due yeekee rounds by policy: no-activity void, no-shoot refund, has-shoot settle';

    public function handle(
        DrawService $drawService,
        YeekeeResultEngineService $yeekeeResultEngineService,
        SettlementService $settlementService,
        DrawCancelAllRefundService $drawCancelAllRefundService
    ): int {
        $drawService->syncScheduledStatuses();

        $dryRun = (bool) $this->option('dry-run');
        $limit = max(1, (int) $this->option('limit'));
        $marketId = $this->option('market_id');
        $drawId = $this->option('draw_id');

        $query = YeekeeRound::query()
            ->whereIn('status', ['draft', 'open'])
            ->orderBy('result_compute_at')
            ->orderBy('id')
            ->limit($limit);

        if ($marketId !== null && $marketId !== '') {
            $query->where('market_id', (int) $marketId);
        }

        if ($drawId !== null && $drawId !== '') {
            $query->where('lotto_draw_id', (int) $drawId);
        }

        $roundIds = $query->pluck('id')->all();

        $summary = [
            'dry_run' => $dryRun,
            'selected' => count($roundIds),
            'processed' => 0,
            'computed_settled' => 0,
            'void_no_activity' => 0,
            'void_refund' => 0,
            'skipped_not_due' => 0,
            'skipped_already_final' => 0,
            'errors' => 0,
        ];

        foreach ($roundIds as $roundId) {
            try {
                $result = DB::transaction(function () use (
                    $roundId,
                    $dryRun,
                    $yeekeeResultEngineService,
                    $settlementService,
                    $drawCancelAllRefundService
                ): string {
                    $round = YeekeeRound::query()->lockForUpdate()->find($roundId);
                    if (! $round instanceof YeekeeRound) {
                        return 'skipped_already_final';
                    }

                    if (! in_array((string) $round->status, ['draft', 'open'], true)) {
                        return 'skipped_already_final';
                    }

                    if (Carbon::parse((string) $round->result_compute_at)->gt(now())) {
                        return 'skipped_not_due';
                    }

                    $draw = LottoDraw::query()->lockForUpdate()->find((int) $round->lotto_draw_id);
                    if (! $draw instanceof LottoDraw) {
                        return 'skipped_already_final';
                    }

                    if ((string) $draw->status === 'resulted') {
                        if (! $dryRun) {
                            $round->status = 'resulted';
                            $round->save();
                        }

                        return 'skipped_already_final';
                    }

                    $activeBetCount = LottoTicket::query()
                        ->where('draw_id', (int) $draw->id)
                        ->where('status', 'active')
                        ->count();

                    $shootCount = YeekeeShoot::query()
                        ->where('yeekee_round_id', (int) $round->id)
                        ->count();

                    if ($shootCount > 0) {
                        if (! $dryRun) {
                            $result = $yeekeeResultEngineService->computeFromRound((int) $round->id);
                            $settlementService->settleDraw($draw, [
                                'top_3' => (string) ($result['top_3'] ?? ''),
                                'bottom_2' => (string) ($result['bottom_2'] ?? ''),
                            ], 'settlement');
                            $round->status = 'resulted';
                            $round->save();
                        }

                        return 'computed_settled';
                    }

                    if ($activeBetCount <= 0) {
                        if (! $dryRun) {
                            $draw->forceFill([
                                'status' => 'resulted',
                                'result_number' => null,
                                'result_at' => $draw->result_at ?? now(),
                                'result_fetch_status' => 'NO_ACTIVITY',
                                'result_applied_at' => now(),
                            ])->save();

                            $round->status = 'voided';
                            $round->save();
                        }

                        return 'void_no_activity';
                    }

                    if (! $dryRun) {
                        $drawCancelAllRefundService->cancelAllActiveTickets(
                            $draw,
                            reason: 'Yeekee no shoot: void and refund',
                            createdByType: 'system',
                            createdById: null,
                            groupCode: 'YEEKEE_VOID_REFUND_'.$draw->id.'_'.now()->format('YmdHis')
                        );

                        $draw->forceFill([
                            'status' => 'resulted',
                            'result_number' => null,
                            'result_at' => $draw->result_at ?? now(),
                            'result_fetch_status' => 'VOID_REFUND',
                            'result_applied_at' => now(),
                        ])->save();

                        $round->status = 'voided';
                        $round->save();
                    }

                    return 'void_refund';
                });

                if (! isset($summary[$result])) {
                    $summary[$result] = 0;
                }
                $summary[$result]++;
                $summary['processed']++;
            } catch (\Throwable $exception) {
                $summary['errors']++;
                $this->error(sprintf('round_id=%d error=%s', (int) $roundId, $exception->getMessage()));
            }
        }

        $this->line(json_encode($summary, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
