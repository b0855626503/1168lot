<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillLottoRiskCurrentCommand extends Command
{
    private const DEFAULT_CHUNK_SIZE = 1000;

    protected $signature = 'dashboard:lotto-risk-current-backfill
        {--web-code= : Limit backfill by web code}
        {--market-id= : Limit backfill by market ID}
        {--round-id= : Limit backfill by round ID}
        {--since= : Only include snapshots from this datetime/date}
        {--chunk= : Upsert batch size}
        {--dry-run : Preview only, do not write}';

    protected $description = 'Backfill lotto dashboard current risk rows from latest detailed risk snapshots';

    public function handle(): int
    {
        if (! Schema::hasTable('lotto_dashboard_risk_snapshot')) {
            $this->warn('table lotto_dashboard_risk_snapshot not found');

            return 0;
        }

        if (! Schema::hasTable('lotto_dashboard_risk_current')) {
            $this->warn('table lotto_dashboard_risk_current not found');

            return 0;
        }

        $chunkSize = (int) ($this->option('chunk') ?: self::DEFAULT_CHUNK_SIZE);
        if ($chunkSize < 1) {
            $this->error('--chunk ต้องมากกว่า 0');

            return 1;
        }

        $baseQuery = DB::table('lotto_dashboard_risk_snapshot as rs');
        $this->applyFilters($baseQuery, 'rs');

        $distinctKeys = (clone $baseQuery)
            ->select([
                'rs.web_code',
                'rs.market_id',
                'rs.round_id',
                'rs.bet_type',
                'rs.number',
            ])
            ->groupBy('rs.web_code', 'rs.market_id', 'rs.round_id', 'rs.bet_type', 'rs.number')
            ->orderBy('rs.web_code')
            ->orderBy('rs.market_id')
            ->orderBy('rs.round_id')
            ->orderBy('rs.bet_type')
            ->orderBy('rs.number');

        $totalProcessed = 0;
        $totalWritten = 0;

        foreach ($distinctKeys->cursor() as $key) {
            $latest = DB::table('lotto_dashboard_risk_snapshot')
                ->where('web_code', $key->web_code)
                ->where('market_id', $key->market_id)
                ->where('round_id', $key->round_id)
                ->where('bet_type', $key->bet_type)
                ->where('number', $key->number)
                ->orderByDesc('snapshot_at')
                ->orderByDesc('id')
                ->first();

            if ($latest === null) {
                continue;
            }

            $rows[] = [
                'web_code' => $latest->web_code,
                'market_id' => $latest->market_id,
                'round_id' => $latest->round_id,
                'bet_type' => $latest->bet_type,
                'number' => $latest->number,
                'snapshot_at' => $latest->snapshot_at,
                'stake_total' => $latest->stake_total,
                'payout_if_hit' => $latest->payout_if_hit,
                'liability' => $latest->liability,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ];

            $totalProcessed++;

            if (count($rows) >= $chunkSize) {
                $totalWritten += $this->flushRows($rows);
                $rows = [];
            }
        }

        if (! empty($rows)) {
            $totalWritten += $this->flushRows($rows);
        }

        $this->info(sprintf(
            'processed=%d written=%d dry_run=%s',
            $totalProcessed,
            $totalWritten,
            (bool) $this->option('dry-run') ? 'yes' : 'no'
        ));

        return 0;
    }

    private function applyFilters($query, string $alias): void
    {
        $webCode = trim((string) ($this->option('web-code') ?? ''));
        if ($webCode !== '') {
            $query->where("{$alias}.web_code", $webCode);
        }

        $marketId = trim((string) ($this->option('market-id') ?? ''));
        if ($marketId !== '') {
            $query->where("{$alias}.market_id", (int) $marketId);
        }

        $roundId = trim((string) ($this->option('round-id') ?? ''));
        if ($roundId !== '') {
            $query->where("{$alias}.round_id", (int) $roundId);
        }

        $since = trim((string) ($this->option('since') ?? ''));
        if ($since !== '') {
            $query->where("{$alias}.snapshot_at", '>=', $since);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function flushRows(array $rows): int
    {
        if ((bool) $this->option('dry-run')) {
            $this->line(sprintf('[dry-run] would upsert %d rows', count($rows)));

            return count($rows);
        }

        DB::table('lotto_dashboard_risk_current')->upsert(
            $rows,
            ['web_code', 'market_id', 'round_id', 'bet_type', 'number'],
            ['snapshot_at', 'stake_total', 'payout_if_hit', 'liability', 'updated_at']
        );

        $this->line(sprintf('upserted %d rows', count($rows)));

        return count($rows);
    }
}
