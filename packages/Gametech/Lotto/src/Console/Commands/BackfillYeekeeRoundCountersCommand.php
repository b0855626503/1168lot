<?php

namespace Gametech\Lotto\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillYeekeeRoundCountersCommand extends Command
{
    protected $signature = 'lotto:yeekee:backfill-round-counters
        {--market_id= : Backfill only rounds in one market}
        {--round_id= : Backfill only one round}
        {--chunk= : Chunk size for round scan}';

    protected $description = 'Backfill yeekee_rounds.last_shoot_position and shoot_count from yeekee_shoots';

    public function handle(): int
    {
        $marketId = (int) ($this->option('market_id') ?: 0);
        $roundId = (int) ($this->option('round_id') ?: 0);
        $chunkSize = max(1, (int) ($this->option('chunk') ?: (int) config('yeekee.round_backfill_chunk_size', 500)));

        $baseQuery = DB::table('yeekee_rounds')->select('id');
        if ($marketId > 0) {
            $baseQuery->where('market_id', $marketId);
        }
        if ($roundId > 0) {
            $baseQuery->where('id', $roundId);
        }

        $processed = 0;
        $updated = 0;

        $baseQuery
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use (&$processed, &$updated): void {
                $roundIds = collect($rows)->pluck('id')->map(static fn ($id): int => (int) $id)->all();
                $processed += count($roundIds);

                if (empty($roundIds)) {
                    return;
                }

                $aggregates = DB::table('yeekee_shoots')
                    ->whereIn('yeekee_round_id', $roundIds)
                    ->selectRaw('yeekee_round_id, MAX(position) as max_position, COUNT(*) as total_shoots')
                    ->groupBy('yeekee_round_id')
                    ->get()
                    ->keyBy('yeekee_round_id');

                foreach ($roundIds as $currentRoundId) {
                    $aggregate = $aggregates->get($currentRoundId);
                    $lastShootPosition = (int) ($aggregate->max_position ?? 0);
                    $shootCount = (int) ($aggregate->total_shoots ?? 0);

                    $affected = DB::table('yeekee_rounds')
                        ->where('id', $currentRoundId)
                        ->where(function ($query) use ($lastShootPosition, $shootCount): void {
                            $query->where('last_shoot_position', '!=', $lastShootPosition)
                                ->orWhere('shoot_count', '!=', $shootCount);
                        })
                        ->update([
                            'last_shoot_position' => $lastShootPosition,
                            'shoot_count' => $shootCount,
                            'updated_at' => now(),
                        ]);

                    if ($affected > 0) {
                        $updated++;
                    }
                }
            }, 'id');

        $summary = [
            'processed' => $processed,
            'updated' => $updated,
            'market_id' => $marketId > 0 ? $marketId : null,
            'round_id' => $roundId > 0 ? $roundId : null,
            'chunk' => $chunkSize,
        ];

        $this->line(json_encode($summary, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
