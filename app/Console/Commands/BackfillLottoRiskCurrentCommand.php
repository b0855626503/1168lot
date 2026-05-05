<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillLottoRiskCurrentCommand extends Command
{
    private const DEFAULT_CHUNK_SIZE = 1000;
    private const DEFAULT_LIMIT_KEYS = 0;

    protected $signature = 'dashboard:lotto-risk-current-backfill
        {--web-code= : Limit backfill by web code}
        {--market-id= : Limit backfill by market ID}
        {--round-id= : Limit backfill by round ID}
        {--since= : Only include snapshots from this datetime/date}
        {--chunk= : Upsert batch size}
        {--limit-keys= : Limit number of distinct risk keys processed, 0 means no limit}
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

        $limitKeys = (int) ($this->option('limit-keys') ?: self::DEFAULT_LIMIT_KEYS);
        if ($limitKeys < 0) {
            $this->error('--limit-keys ต้องมากกว่าหรือเท่ากับ 0');

            return 1;
        }

        $latestRowsQuery = $this->latestSnapshotRowsQuery();
        if ($limitKeys > 0) {
            $latestRowsQuery->limit($limitKeys);
        }

        $totalProcessed = 0;
        $totalWritten = 0;
        $rows = [];

        foreach ($latestRowsQuery->cursor() as $latest) {
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

    private function latestSnapshotRowsQuery(): Builder
    {
        $latestIdSubquery = DB::query()
            ->fromSub($this->latestSnapshotAtQuery(), 'latest_snapshot_at')
            ->join('lotto_dashboard_risk_snapshot as rs2', function ($join): void {
                $join->on('rs2.web_code', '=', 'latest_snapshot_at.web_code')
                    ->on('rs2.market_id', '=', 'latest_snapshot_at.market_id')
                    ->on('rs2.round_id', '=', 'latest_snapshot_at.round_id')
                    ->on('rs2.bet_type', '=', 'latest_snapshot_at.bet_type')
                    ->on('rs2.number', '=', 'latest_snapshot_at.number')
                    ->on('rs2.snapshot_at', '=', 'latest_snapshot_at.latest_snapshot_at');
            })
            ->select([
                'latest_snapshot_at.web_code',
                'latest_snapshot_at.market_id',
                'latest_snapshot_at.round_id',
                'latest_snapshot_at.bet_type',
                'latest_snapshot_at.number',
                DB::raw('MAX(rs2.id) as latest_id'),
            ])
            ->groupBy(
                'latest_snapshot_at.web_code',
                'latest_snapshot_at.market_id',
                'latest_snapshot_at.round_id',
                'latest_snapshot_at.bet_type',
                'latest_snapshot_at.number'
            );

        $query = DB::query()
            ->fromSub($latestIdSubquery, 'latest_ids')
            ->join('lotto_dashboard_risk_snapshot as rs', 'rs.id', '=', 'latest_ids.latest_id')
            ->select([
                'rs.web_code',
                'rs.market_id',
                'rs.round_id',
                'rs.bet_type',
                'rs.number',
                'rs.snapshot_at',
                'rs.stake_total',
                'rs.payout_if_hit',
                'rs.liability',
            ])
            ->orderBy('rs.web_code')
            ->orderBy('rs.market_id')
            ->orderBy('rs.round_id')
            ->orderBy('rs.bet_type')
            ->orderBy('rs.number');

        return $query;
    }

    private function latestSnapshotAtQuery(): Builder
    {
        $query = DB::table('lotto_dashboard_risk_snapshot as rs')
            ->select([
                'rs.web_code',
                'rs.market_id',
                'rs.round_id',
                'rs.bet_type',
                'rs.number',
                DB::raw('MAX(rs.snapshot_at) as latest_snapshot_at'),
            ])
            ->groupBy('rs.web_code', 'rs.market_id', 'rs.round_id', 'rs.bet_type', 'rs.number');

        $this->applyFilters($query, 'rs');

        return $query;
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
