<?php

namespace App\Console\Commands\Dashboard;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LottoRiskCurrentCleanupCommand extends Command
{
    protected $signature = 'dashboard:lotto-risk-current-cleanup
        {--dry-run : Count rows that would be deleted without deleting}
        {--chunk=5000 : Batch delete chunk size}
        {--max-runtime=120 : Maximum runtime in seconds before stopping}
        {--sleep-ms=100 : Milliseconds to sleep between chunks to reduce lock pressure}
        {--web-code= : Optional filter: only clean rows matching this web_code}';

    protected $description = 'Clean invalid/zero/missing-draw rows from lotto_dashboard_risk_current (batch, bounded, resumable)';

    private int $deletedInvalidDraw = 0;

    private int $deletedMissingDraw = 0;

    private int $deletedZeroRisk = 0;

    public function handle(): int
    {
        if (! Schema::hasTable('lotto_dashboard_risk_current')) {
            $this->error('table lotto_dashboard_risk_current not found');

            return 1;
        }

        if (! Schema::hasTable('lotto_draws')) {
            $this->error('table lotto_draws not found');

            return 1;
        }

        $isDryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));
        $maxRuntime = max(0, (int) $this->option('max-runtime'));
        $sleepMs = max(0, (int) $this->option('sleep-ms'));
        $webCode = $this->normalizedOption('web-code');

        $startedAt = microtime(true);

        $this->line(sprintf(
            'message=lotto_risk_current_cleanup_started options=dry_run=%s,chunk=%d,max_runtime=%d,sleep_ms=%d,web_code=%s',
            $isDryRun ? 'true' : 'false',
            $chunk,
            $maxRuntime,
            $sleepMs,
            $webCode ?? 'null'
        ));

        $stoppedBy = 'complete';

        // ----------------------------------------------------------------
        // Target 1: Invalid draw rows (resulted status or result_at set)
        // ----------------------------------------------------------------
        if ($maxRuntime > 0 && (microtime(true) - $startedAt) >= $maxRuntime) {
            $stoppedBy = 'max_runtime';
        } else {
            $stoppedBy = $this->cleanInvalidDrawRows($isDryRun, $chunk, $maxRuntime, $sleepMs, $webCode, $startedAt);
        }

        // ----------------------------------------------------------------
        // Target 2: Missing draw rows (round_id not in lotto_draws)
        // ----------------------------------------------------------------
        // Dry-run always continues to count all targets — never short-circuit on 'dry_run'.
        if ($stoppedBy !== 'max_runtime') {
            $stoppedBy = $this->cleanMissingDrawRows($isDryRun, $chunk, $maxRuntime, $sleepMs, $webCode, $startedAt);
        }

        // ----------------------------------------------------------------
        // Target 3: Zero-risk rows
        // ----------------------------------------------------------------
        if ($stoppedBy !== 'max_runtime') {
            $stoppedBy = $this->cleanZeroRiskRows($isDryRun, $chunk, $maxRuntime, $sleepMs, $webCode, $startedAt);
        }

        $totalDeleted = $this->deletedInvalidDraw + $this->deletedMissingDraw + $this->deletedZeroRisk;

        $this->line(sprintf(
            'message=lotto_risk_current_cleanup_finished invalid_draw_rows=%d missing_draw_rows=%d zero_risk_rows=%d deleted_rows=%d stopped_by=%s',
            $this->deletedInvalidDraw,
            $this->deletedMissingDraw,
            $this->deletedZeroRisk,
            $totalDeleted,
            $stoppedBy
        ));

        return 0;
    }

    /**
     * Delete rows whose draw is resulted (status='resulted') or already has result_at set.
     */
    private function cleanInvalidDrawRows(
        bool $isDryRun,
        int $chunk,
        int $maxRuntime,
        int $sleepMs,
        ?string $webCode,
        float $startedAt
    ): string {
        if ($isDryRun) {
            $count = $this->buildInvalidDrawQuery($webCode)->count();
            $this->line("message=lotto_risk_current_cleanup_chunk deleted={$count} target=invalid_draw_rows");
            $this->deletedInvalidDraw = $count;

            return 'dry_run';
        }

        do {
            if ($maxRuntime > 0 && (microtime(true) - $startedAt) >= $maxRuntime) {
                return 'max_runtime';
            }

            $ids = $this->buildInvalidDrawQuery($webCode)
                ->select('c.id')
                ->limit($chunk)
                ->pluck('c.id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted = DB::table('lotto_dashboard_risk_current')
                ->whereIn('id', $ids)
                ->delete();

            $this->deletedInvalidDraw += $deleted;
            $this->line("message=lotto_risk_current_cleanup_chunk deleted={$deleted} target=invalid_draw_rows");

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        } while ($ids->count() === $chunk);

        return 'complete';
    }

    /**
     * Delete rows whose round_id does not exist in lotto_draws.
     */
    private function cleanMissingDrawRows(
        bool $isDryRun,
        int $chunk,
        int $maxRuntime,
        int $sleepMs,
        ?string $webCode,
        float $startedAt
    ): string {
        if ($isDryRun) {
            $count = $this->buildMissingDrawQuery($webCode)->count();
            $this->line("message=lotto_risk_current_cleanup_chunk deleted={$count} target=missing_draw_rows");
            $this->deletedMissingDraw = $count;

            return 'dry_run';
        }

        do {
            if ($maxRuntime > 0 && (microtime(true) - $startedAt) >= $maxRuntime) {
                return 'max_runtime';
            }

            $ids = $this->buildMissingDrawQuery($webCode)
                ->select('lotto_dashboard_risk_current.id')
                ->limit($chunk)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted = DB::table('lotto_dashboard_risk_current')
                ->whereIn('id', $ids)
                ->delete();

            $this->deletedMissingDraw += $deleted;
            $this->line("message=lotto_risk_current_cleanup_chunk deleted={$deleted} target=missing_draw_rows");

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        } while ($ids->count() === $chunk);

        return 'complete';
    }

    /**
     * Delete rows where all risk amounts are zero/null.
     */
    private function cleanZeroRiskRows(
        bool $isDryRun,
        int $chunk,
        int $maxRuntime,
        int $sleepMs,
        ?string $webCode,
        float $startedAt
    ): string {
        if ($isDryRun) {
            $count = $this->buildZeroRiskQuery($webCode)->count();
            $this->line("message=lotto_risk_current_cleanup_chunk deleted={$count} target=zero_risk_rows");
            $this->deletedZeroRisk = $count;

            return 'dry_run';
        }

        do {
            if ($maxRuntime > 0 && (microtime(true) - $startedAt) >= $maxRuntime) {
                return 'max_runtime';
            }

            $ids = $this->buildZeroRiskQuery($webCode)
                ->select('id')
                ->limit($chunk)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted = DB::table('lotto_dashboard_risk_current')
                ->whereIn('id', $ids)
                ->delete();

            $this->deletedZeroRisk += $deleted;
            $this->line("message=lotto_risk_current_cleanup_chunk deleted={$deleted} target=zero_risk_rows");

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        } while ($ids->count() === $chunk);

        return 'complete';
    }

    private function buildInvalidDrawQuery(?string $webCode): Builder
    {
        $query = DB::table('lotto_dashboard_risk_current as c')
            ->join('lotto_draws as d', 'd.id', '=', 'c.round_id')
            ->where(function ($q): void {
                $q->whereNotNull('d.result_at')
                    ->orWhere('d.status', 'resulted');
            });

        if ($webCode !== null) {
            $query->where('c.web_code', $webCode);
        }

        return $query;
    }

    private function buildMissingDrawQuery(?string $webCode): Builder
    {
        $query = DB::table('lotto_dashboard_risk_current')
            ->whereNotExists(function ($q): void {
                $q->select(DB::raw(1))
                    ->from('lotto_draws')
                    ->whereColumn('lotto_draws.id', 'lotto_dashboard_risk_current.round_id');
            });

        if ($webCode !== null) {
            $query->where('web_code', $webCode);
        }

        return $query;
    }

    private function buildZeroRiskQuery(?string $webCode): Builder
    {
        $query = DB::table('lotto_dashboard_risk_current')
            ->whereRaw('COALESCE(stake_total, 0) <= 0')
            ->whereRaw('COALESCE(payout_if_hit, 0) <= 0')
            ->whereRaw('COALESCE(liability, 0) <= 0');

        if ($webCode !== null) {
            $query->where('web_code', $webCode);
        }

        return $query;
    }

    private function normalizedOption(string $name): ?string
    {
        $value = trim((string) $this->option($name));

        return $value === '' ? null : $value;
    }
}
