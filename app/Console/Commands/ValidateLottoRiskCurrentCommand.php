<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ValidateLottoRiskCurrentCommand extends Command
{
    protected $signature = 'dashboard:lotto-risk-current-validate
        {--web-code= : Filter by web code}
        {--market-id= : Filter by market ID}
        {--round-id= : Filter by round ID}
        {--limit=100 : Max sample rows to display per check}
        {--compare-snapshot : Opt-in scoped snapshot comparison (requires --web-code, --market-id, or --round-id)}
        {--tolerance=0.01 : Decimal tolerance for snapshot amount comparison}';

    protected $description = 'Current Exposure Validation Gate — validates lotto_dashboard_risk_current directly';

    public function handle(): int
    {
        if (! Schema::hasTable('lotto_dashboard_risk_current')) {
            $this->error('table lotto_dashboard_risk_current not found');

            return 1;
        }

        if ($this->option('compare-snapshot') && ! $this->hasScopeFilter()) {
            $this->error('--compare-snapshot requires at least one scope filter: --web-code, --market-id, or --round-id');

            return 1;
        }

        if (! Schema::hasTable('lotto_draws')) {
            $this->error('table lotto_draws not found');

            return 1;
        }

        $limit = max(1, (int) $this->option('limit'));
        $failed = false;

        // 1. Duplicate dimension keys
        $duplicates = $this->countDuplicateCurrentRows();
        $this->line("duplicate_current_keys = {$duplicates}");

        if ($duplicates > 0) {
            $failed = true;
            $this->warn('Sample duplicate current keys:');
            $this->displayDuplicateSamples($limit);
        }

        // 2. Invalid draw rows (missing draw, resulted draw, result_at set)
        $invalidDrawRows = $this->countInvalidDrawRows();
        $this->line("invalid_draw_rows = {$invalidDrawRows}");

        if ($invalidDrawRows > 0) {
            $failed = true;
            $this->warn('Sample invalid draw rows:');
            $this->displayInvalidDrawSamples($limit);
        }

        // 3. Zero-risk rows
        $zeroRiskRows = $this->countZeroRiskRows();
        $this->line("zero_risk_rows = {$zeroRiskRows}");

        if ($zeroRiskRows > 0) {
            $failed = true;
            $this->warn('Sample zero-risk rows:');
            $this->displayZeroRiskSamples($limit);
        }

        // 4. Optional scoped snapshot comparison
        $snapshotChecked = $this->runSnapshotComparison($limit, $failed);
        $this->line("snapshot_compare_checked = {$snapshotChecked}");

        // Summary
        $result = $failed ? 'failed' : 'passed';
        $this->line("validation_result = {$result}");

        if ($failed) {
            $this->error('lotto risk current validation failed');

            return 1;
        }

        $this->info('lotto risk current validation passed');

        return 0;
    }

    private function hasScopeFilter(): bool
    {
        return $this->normalizedOption('web-code') !== null
            || $this->normalizedOption('market-id') !== null
            || $this->normalizedOption('round-id') !== null;
    }

    private function countDuplicateCurrentRows(): int
    {
        $sub = DB::table('lotto_dashboard_risk_current');
        $this->applyFilters($sub, null);

        $sub = $sub
            ->selectRaw('COUNT(*) as c')
            ->groupBy('web_code', 'market_id', 'round_id', 'bet_type', 'number')
            ->havingRaw('COUNT(*) > 1');

        return (int) DB::table(DB::raw("({$sub->toSql()}) as dup"))
            ->mergeBindings($sub)
            ->count();
    }

    private function countInvalidDrawRows(): int
    {
        return $this->invalidDrawQuery()->count();
    }

    private function countZeroRiskRows(): int
    {
        return $this->zeroRiskQuery()->count();
    }

    private function invalidDrawQuery(): Builder
    {
        $query = DB::table('lotto_dashboard_risk_current as c')
            ->leftJoin('lotto_draws as d', 'd.id', '=', 'c.round_id')
            ->where(function ($q): void {
                $q->whereNull('d.id')
                    ->orWhereNotNull('d.result_at')
                    ->orWhere('d.status', 'resulted');
            });

        $this->applyFilters($query, 'c');

        return $query;
    }

    private function zeroRiskQuery(): Builder
    {
        $query = DB::table('lotto_dashboard_risk_current')
            ->whereRaw('COALESCE(stake_total, 0) = 0')
            ->whereRaw('COALESCE(payout_if_hit, 0) = 0')
            ->whereRaw('COALESCE(liability, 0) = 0');

        $this->applyFilters($query, null);

        return $query;
    }

    /**
     * Run optional scoped snapshot comparison. Returns string label for output line.
     */
    private function runSnapshotComparison(int $limit, bool &$failed): string
    {
        if (! $this->option('compare-snapshot')) {
            return 'skipped';
        }

        if (! Schema::hasTable('lotto_dashboard_risk_snapshot')) {
            $this->warn('--compare-snapshot requested but lotto_dashboard_risk_snapshot table not found; skipping');

            return 'skipped';
        }

        $tolerance = max(0.0, (float) $this->option('tolerance'));

        // Set-based: find latest snapshot per key without N+1 loop
        $latestSnap = DB::table('lotto_dashboard_risk_snapshot as s1')
            ->select('s1.web_code', 's1.market_id', 's1.round_id', 's1.bet_type', 's1.number',
                's1.stake_total', 's1.payout_if_hit', 's1.liability')
            ->whereNotExists(function ($q): void {
                $q->select(DB::raw(1))
                    ->from('lotto_dashboard_risk_snapshot as s2')
                    ->whereColumn('s2.web_code', 's1.web_code')
                    ->whereColumn('s2.market_id', 's1.market_id')
                    ->whereColumn('s2.round_id', 's1.round_id')
                    ->whereColumn('s2.bet_type', 's1.bet_type')
                    ->whereColumn('s2.number', 's1.number')
                    ->whereColumn('s2.snapshot_at', '>', 's1.snapshot_at');
            });

        $this->applyFilters($latestSnap, 's1');

        $mismatches = DB::table(DB::raw("({$latestSnap->toSql()}) as snap"))
            ->mergeBindings($latestSnap)
            ->leftJoin('lotto_dashboard_risk_current as c', function ($join): void {
                $join->on('c.web_code', '=', 'snap.web_code')
                    ->on('c.market_id', '=', 'snap.market_id')
                    ->on('c.round_id', '=', 'snap.round_id')
                    ->on('c.bet_type', '=', 'snap.bet_type')
                    ->on('c.number', '=', 'snap.number');
            })
            ->where(function ($q) use ($tolerance): void {
                $q->whereNull('c.round_id')
                    ->orWhereRaw('ABS(COALESCE(snap.stake_total, 0) - COALESCE(c.stake_total, 0)) > ?', [$tolerance])
                    ->orWhereRaw('ABS(COALESCE(snap.payout_if_hit, 0) - COALESCE(c.payout_if_hit, 0)) > ?', [$tolerance])
                    ->orWhereRaw('ABS(COALESCE(snap.liability, 0) - COALESCE(c.liability, 0)) > ?', [$tolerance]);
            });

        $count = $mismatches->count();

        if ($count > 0) {
            $failed = true;
            $this->warn("Snapshot comparison found {$count} mismatch(es). Sample:");
            $samples = $mismatches
                ->select('snap.web_code', 'snap.market_id', 'snap.round_id', 'snap.bet_type', 'snap.number',
                    'snap.stake_total as snap_stake', 'c.stake_total as cur_stake')
                ->limit($limit)
                ->get();

            $this->table(
                ['web_code', 'market_id', 'round_id', 'bet_type', 'number', 'snap_stake', 'cur_stake'],
                $samples->map(fn ($r) => [
                    $r->web_code, $r->market_id, $r->round_id, $r->bet_type, $r->number,
                    $r->snap_stake ?? 'NULL', $r->cur_stake ?? 'NULL',
                ])->toArray()
            );
        }

        return (string) $count;
    }

    /**
     * @param  Builder  $query
     */
    private function applyFilters($query, ?string $alias): void
    {
        $prefix = $alias ? "{$alias}." : '';

        if (($webCode = $this->normalizedOption('web-code')) !== null) {
            $query->where("{$prefix}web_code", $webCode);
        }

        if (($marketId = $this->normalizedOption('market-id')) !== null) {
            $query->where("{$prefix}market_id", $marketId);
        }

        if (($roundId = $this->normalizedOption('round-id')) !== null) {
            $query->where("{$prefix}round_id", $roundId);
        }
    }

    private function normalizedOption(string $name): ?string
    {
        $value = trim((string) $this->option($name));

        return $value === '' ? null : $value;
    }

    private function displayDuplicateSamples(int $limit): void
    {
        $rows = DB::table('lotto_dashboard_risk_current');
        $this->applyFilters($rows, null);

        $rows = $rows
            ->select('web_code', 'market_id', 'round_id', 'bet_type', 'number', DB::raw('COUNT(*) as c'))
            ->groupBy('web_code', 'market_id', 'round_id', 'bet_type', 'number')
            ->havingRaw('COUNT(*) > 1')
            ->limit($limit)
            ->get();

        $this->table(
            ['web_code', 'market_id', 'round_id', 'bet_type', 'number', 'count'],
            $rows->map(fn ($r) => [
                (string) $r->web_code, $r->market_id, $r->round_id, $r->bet_type, $r->number, $r->c,
            ])->toArray()
        );
    }

    private function displayInvalidDrawSamples(int $limit): void
    {
        $rows = $this->invalidDrawQuery()
            ->select('c.web_code', 'c.market_id', 'c.round_id', 'c.bet_type', 'c.number', 'd.status', 'd.result_at')
            ->limit($limit)
            ->get();

        $this->table(
            ['web_code', 'market_id', 'round_id', 'bet_type', 'number', 'draw_status', 'result_at'],
            $rows->map(fn ($r) => [
                $r->web_code, $r->market_id, $r->round_id, $r->bet_type, $r->number,
                $r->status ?? 'NULL', $r->result_at ?? 'NULL',
            ])->toArray()
        );
    }

    private function displayZeroRiskSamples(int $limit): void
    {
        $rows = $this->zeroRiskQuery()
            ->select('web_code', 'market_id', 'round_id', 'bet_type', 'number')
            ->limit($limit)
            ->get();

        $this->table(
            ['web_code', 'market_id', 'round_id', 'bet_type', 'number'],
            $rows->map(fn ($r) => [
                $r->web_code, $r->market_id, $r->round_id, $r->bet_type, $r->number,
            ])->toArray()
        );
    }
}
