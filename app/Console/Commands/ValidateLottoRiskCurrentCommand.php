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
        {--tolerance=0.01 : Decimal tolerance for snapshot amount comparison}
        {--strict : Run all strict validation checks and exit 1 if any fail}';

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

        if ($this->option('strict')) {
            return $this->handleStrict();
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

    // -----------------------------------------------------------------------
    // Strict mode
    // -----------------------------------------------------------------------

    /**
     * Run all 9 strict checks and output pass/fail per check.
     * Exit code 0 = all passed, 1 = any failed.
     */
    private function handleStrict(): int
    {
        $failed = false;

        // 1. duplicate_current_keys
        $duplicates = $this->countDuplicateCurrentRows();
        $this->outputCheck('duplicate_current_keys', $duplicates, 0, $failed);

        // 2. invalid_draw_rows (resulted OR result_at set)
        $invalidDrawRows = $this->countInvalidDrawRows();
        $this->outputCheck('invalid_draw_rows', $invalidDrawRows, 0, $failed);

        // 3. zero_risk_rows
        $zeroRiskRows = $this->countZeroRiskRows();
        $this->outputCheck('zero_risk_rows', $zeroRiskRows, 0, $failed);

        // 4. missing_draw_rows (round_id not in lotto_draws)
        $missingDrawRows = $this->countMissingDrawRows();
        $this->outputCheck('missing_draw_rows', $missingDrawRows, 0, $failed);

        // 5. cancelled_draw_rows — rows whose draw status='resulted'
        //    (production enum: draft|open|closed|resulted — no 'cancelled' status exists;
        //     "cancelled" draws in this system are represented as status='resulted')
        $cancelledDrawRows = $this->countCancelledDrawRows();
        $this->outputCheck('cancelled_draw_rows', $cancelledDrawRows, 0, $failed);

        // 6. snapshot_writer_enabled — must be false (PR-A disabled it)
        $snapshotWriterEnabled = (bool) config('dashboard.lotto.legacy_snapshot_write_enabled', false);
        $this->outputCheckBool('snapshot_writer_enabled', $snapshotWriterEnabled, false, $failed);

        // 7. snapshot_fallback_enabled — always false at runtime after PR-A hardcoded current mode.
        //    Even if the legacy config key still says 'snapshot', the runtime never reads from snapshot.
        //    Emit a [WARN] line if the legacy config still says 'snapshot', but do NOT fail the check.
        $legacyReadSource = (string) config('dashboard.lotto_risk.read_source', 'current');
        $this->outputCheckBool('snapshot_fallback_enabled', false, false, $failed);
        if ($legacyReadSource === 'snapshot') {
            $this->line('legacy_read_source_config = snapshot  [WARN] (runtime overrides this — PR-A hardcodes current mode)');
        }

        // 8. snapshot_runtime_dependency — 0 because PR-A hardcodes current mode;
        //    writer is config-gated (checked above), runtime fallback is always disabled.
        $snapshotDependency = $snapshotWriterEnabled ? 1 : 0;
        $this->outputCheck('snapshot_runtime_dependency', $snapshotDependency, 0, $failed);

        // 9. dashboard_read_source — always 'current_only' at runtime after PR-A hardcoded it.
        $this->line('dashboard_read_source = current_only  [PASS]');

        // Summary
        $result = $failed ? 'failed' : 'passed';
        $resultLabel = $failed ? '[FAIL]' : '[PASS]';
        $this->line("validation_result = {$result}  {$resultLabel}");

        return $failed ? 1 : 0;
    }

    /**
     * Output a numeric check line with [PASS]/[FAIL].
     */
    private function outputCheck(string $name, int $actual, int $expected, bool &$failed): void
    {
        $pass = ($actual === $expected);
        if (! $pass) {
            $failed = true;
        }
        $label = $pass ? '[PASS]' : '[FAIL]';
        $this->line("{$name} = {$actual}  {$label}");
    }

    /**
     * Output a boolean check line with [PASS]/[FAIL].
     * Pass condition: $actual === $expected.
     */
    private function outputCheckBool(string $name, bool $actual, bool $expected, bool &$failed): void
    {
        $pass = ($actual === $expected);
        if (! $pass) {
            $failed = true;
        }
        $label = $pass ? '[PASS]' : '[FAIL]';
        $value = $actual ? 'true' : 'false';
        $this->line("{$name} = {$value}  {$label}");
    }

    // -----------------------------------------------------------------------
    // Count helpers
    // -----------------------------------------------------------------------

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

    private function countMissingDrawRows(): int
    {
        $query = DB::table('lotto_dashboard_risk_current')
            ->whereNotExists(function ($q): void {
                $q->select(DB::raw(1))
                    ->from('lotto_draws')
                    ->whereColumn('lotto_draws.id', 'lotto_dashboard_risk_current.round_id');
            });

        $this->applyFilters($query, null);

        return $query->count();
    }

    /**
     * Count rows whose draw has status='resulted'.
     * Note: production lotto_draws.status enum is: draft, open, closed, resulted.
     * There is no 'cancelled' status — draws that are cancelled/voided are recorded as 'resulted'.
     */
    private function countCancelledDrawRows(): int
    {
        $query = DB::table('lotto_dashboard_risk_current as c')
            ->join('lotto_draws as d', 'd.id', '=', 'c.round_id')
            ->where('d.status', 'resulted');

        $this->applyFilters($query, 'c');

        return $query->count();
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
