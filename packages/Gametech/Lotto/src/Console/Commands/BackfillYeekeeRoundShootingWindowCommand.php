<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LotteryMarket;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BackfillYeekeeRoundShootingWindowCommand extends Command
{
    protected $signature = 'lotto:yeekee:backfill-shooting-window
        {--apply : Apply update to database (default is dry-run)}
        {--market-id= : Backfill only one market}
        {--draw-id= : Backfill only one draw}
        {--round-id= : Backfill only one round}
        {--chunk= : Chunk size for scan}';

    protected $description = 'Backfill yeekee round shooting window contract and counters';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $marketId = (int) ($this->option('market-id') ?: 0);
        $drawId = (int) ($this->option('draw-id') ?: 0);
        $roundId = (int) ($this->option('round-id') ?: 0);
        $chunkSize = max(1, (int) ($this->option('chunk') ?: (int) config('yeekee.round_backfill_chunk_size', 500)));

        $baseQuery = DB::table('yeekee_rounds as r')
            ->join('lotto_markets as m', 'm.id', '=', 'r.market_id')
            ->leftJoin('yeekee_market_settings as ys', 'ys.market_id', '=', 'r.market_id')
            ->where('m.result_mode', LotteryMarket::RESULT_MODE_YEEKEE)
            ->select([
                'r.id as id',
                'r.market_id',
                'r.status',
                'r.bet_open_at',
                'r.bet_close_at',
                'r.shoot_open_at',
                'r.shoot_close_at',
                'r.shoot_closed_at',
                'r.shoot_snapshot_json',
                'r.shoot_snapshot_hash',
                'r.shoot_count',
                'r.last_shoot_position',
                'ys.round_config',
            ]);

        if ($marketId > 0) {
            $baseQuery->where('r.market_id', $marketId);
        }
        if ($drawId > 0) {
            $baseQuery->where('r.lotto_draw_id', $drawId);
        }
        if ($roundId > 0) {
            $baseQuery->where('r.id', $roundId);
        }

        $summary = [
            'mode' => $apply ? 'apply' : 'dry-run',
            'filters' => [
                'market_id' => $marketId > 0 ? $marketId : null,
                'draw_id' => $drawId > 0 ? $drawId : null,
                'round_id' => $roundId > 0 ? $roundId : null,
                'chunk' => $chunkSize,
            ],
            'scanned' => 0,
            'updatable' => 0,
            'would_update' => 0,
            'updated' => 0,
            'skipped' => [
                'status_final' => 0,
                'frozen' => 0,
                'snapshot_exists' => 0,
                'missing_base_window' => 0,
                'already_aligned' => 0,
            ],
        ];

        $baseQuery
            ->orderBy('r.id')
            ->chunkById($chunkSize, function ($rows) use (&$summary, $apply): void {
                $roundIds = collect($rows)->pluck('id')->map(static fn ($id): int => (int) $id)->all();
                if (empty($roundIds)) {
                    return;
                }

                $shootAgg = DB::table('yeekee_shoots')
                    ->whereIn('yeekee_round_id', $roundIds)
                    ->selectRaw('yeekee_round_id, COUNT(*) AS shoot_count, MAX(position) AS max_position')
                    ->groupBy('yeekee_round_id')
                    ->get()
                    ->keyBy('yeekee_round_id');

                foreach ($rows as $row) {
                    $summary['scanned']++;

                    $status = (string) ($row->status ?? '');
                    if (in_array($status, ['resulted', 'settled', 'voided'], true)) {
                        $summary['skipped']['status_final']++;

                        continue;
                    }

                    if ($row->shoot_closed_at !== null) {
                        $summary['skipped']['frozen']++;

                        continue;
                    }

                    if ($row->shoot_snapshot_json !== null || $row->shoot_snapshot_hash !== null) {
                        $summary['skipped']['snapshot_exists']++;

                        continue;
                    }

                    if ($row->bet_open_at === null || $row->bet_close_at === null) {
                        $summary['skipped']['missing_base_window']++;

                        continue;
                    }

                    $summary['updatable']++;

                    $aggregate = $shootAgg->get((int) $row->id);
                    $targetShootCount = (int) ($aggregate->shoot_count ?? 0);
                    $targetLastPosition = (int) ($aggregate->max_position ?? 0);
                    $shootWindowSeconds = $this->resolveShootWindowSeconds($row->round_config ?? null);
                    $targetShootOpenAt = (string) $row->bet_open_at;
                    $targetShootCloseAt = Carbon::parse((string) $row->bet_close_at)->addSeconds($shootWindowSeconds)->format('Y-m-d H:i:s');

                    $needsWindowFix = (string) $row->shoot_open_at !== $targetShootOpenAt
                        || (string) $row->shoot_close_at !== $targetShootCloseAt;
                    $needsCounterFix = (int) ($row->shoot_count ?? 0) !== $targetShootCount
                        || (int) ($row->last_shoot_position ?? 0) !== $targetLastPosition;

                    if (! $needsWindowFix && ! $needsCounterFix) {
                        $summary['skipped']['already_aligned']++;

                        continue;
                    }

                    $summary['would_update']++;

                    if (! $apply) {
                        continue;
                    }

                    $affected = DB::table('yeekee_rounds')
                        ->where('id', (int) $row->id)
                        ->update([
                            'shoot_open_at' => $targetShootOpenAt,
                            'shoot_close_at' => $targetShootCloseAt,
                            'shoot_count' => $targetShootCount,
                            'last_shoot_position' => $targetLastPosition,
                            'updated_at' => now(),
                        ]);

                    if ($affected > 0) {
                        $summary['updated']++;
                    }
                }
            }, 'r.id', 'id');

        $this->line(json_encode($summary, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    private function resolveShootWindowSeconds($roundConfig): int
    {
        $decoded = [];
        if (is_array($roundConfig)) {
            $decoded = $roundConfig;
        } elseif (is_string($roundConfig) && $roundConfig !== '') {
            $parsed = json_decode($roundConfig, true);
            if (is_array($parsed)) {
                $decoded = $parsed;
            }
        }

        return max(0, (int) ($decoded['shoot_window_after_bet_close_seconds'] ?? 0));
    }
}
