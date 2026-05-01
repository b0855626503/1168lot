<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Models\YeekeeShoot;
use Gametech\Lotto\Services\DrawCancelAllRefundService;
use Gametech\Lotto\Services\DrawService;
use Gametech\Lotto\Services\SettlementService;
use Gametech\Lotto\Services\Yeekee\Exceptions\YeekeeFormulaInputException;
use Gametech\Lotto\Services\YeekeeResultEngineService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettleYeekeeRoundsCommand extends Command
{
    private const RESULT_FETCH_STATUS_VOID_REFUND_FORMULA_INPUT_INSUFFICIENT = 'VOID_REFUND_FORMULA_INPUT_INSUFFICIENT';

    private const RESULT_FETCH_STATUS_NO_ACTIVITY_FORMULA_INPUT_INSUFFICIENT = 'NO_ACTIVITY_FORMULA_INPUT_INSUFFICIENT';

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
                            try {
                                $result = $yeekeeResultEngineService->computeFromRound((int) $round->id);
                                $settlementService->settleDraw($draw, [
                                    'top_3' => (string) ($result['top_3'] ?? ''),
                                    'bottom_2' => (string) ($result['bottom_2'] ?? ''),
                                ], 'settlement');
                                $round->status = 'resulted';
                                $round->save();
                            } catch (YeekeeFormulaInputException $exception) {
                                $formulaPreset = $this->resolveFormulaPresetFromRound($round);
                                if ($activeBetCount > 0) {
                                    $drawCancelAllRefundService->cancelAllActiveTickets(
                                        $draw,
                                        reason: 'Yeekee formula input insufficient: void and refund',
                                        createdByType: 'system',
                                        createdById: null,
                                        groupCode: 'YEEKEE_FORMULA_INPUT_VOID_REFUND_'.$draw->id.'_'.now()->format('YmdHis')
                                    );

                                    $draw->forceFill([
                                        'status' => 'resulted',
                                        'result_number' => null,
                                        'result_at' => $draw->result_at ?? now(),
                                        'result_fetch_status' => self::RESULT_FETCH_STATUS_VOID_REFUND_FORMULA_INPUT_INSUFFICIENT,
                                        'result_applied_at' => now(),
                                    ])->save();

                                    $round->status = 'voided';
                                    $round->save();

                                    Log::warning('yeekee.formula_failure_policy.recoverable', [
                                        'yeekee_round_id' => (int) $round->id,
                                        'lotto_draw_id' => (int) $draw->id,
                                        'formula_preset' => $formulaPreset,
                                        'failure_code' => $exception->failureCode(),
                                        'result_fetch_status' => self::RESULT_FETCH_STATUS_VOID_REFUND_FORMULA_INPUT_INSUFFICIENT,
                                        'ticket_count' => (int) $activeBetCount,
                                        'shoot_count' => (int) $shootCount,
                                        'message' => $exception->getMessage(),
                                    ]);

                                    return 'void_refund';
                                }

                                $draw->forceFill([
                                    'status' => 'resulted',
                                    'result_number' => null,
                                    'result_at' => $draw->result_at ?? now(),
                                    'result_fetch_status' => self::RESULT_FETCH_STATUS_NO_ACTIVITY_FORMULA_INPUT_INSUFFICIENT,
                                    'result_applied_at' => now(),
                                ])->save();

                                $round->status = 'voided';
                                $round->save();

                                Log::warning('yeekee.formula_failure_policy.recoverable', [
                                    'yeekee_round_id' => (int) $round->id,
                                    'lotto_draw_id' => (int) $draw->id,
                                    'formula_preset' => $formulaPreset,
                                    'failure_code' => $exception->failureCode(),
                                    'result_fetch_status' => self::RESULT_FETCH_STATUS_NO_ACTIVITY_FORMULA_INPUT_INSUFFICIENT,
                                    'ticket_count' => (int) $activeBetCount,
                                    'shoot_count' => (int) $shootCount,
                                    'message' => $exception->getMessage(),
                                ]);

                                return 'void_no_activity';
                            }
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

    private function resolveFormulaPresetFromRound(YeekeeRound $round): string
    {
        $snapshot = is_array($round->config_snapshot_json) ? $round->config_snapshot_json : [];
        $formulaConfig = is_array($snapshot['formula_config'] ?? null) ? $snapshot['formula_config'] : [];

        return trim((string) ($formulaConfig['preset'] ?? 'SHOOTS_SUM_MINUS_POSITION'));
    }
}
