<?php

namespace App\Console\Commands;

use App\Services\Dashboard\LottoDashboardMetricConfig;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanupLottoRiskSnapshotsCommand extends Command
{
    private const MIN_RETENTION_DAYS = 1;
    private const MAX_RETENTION_DAYS = 90;
    private const DEFAULT_CHUNK_SIZE = 5000;
    private const DEFAULT_MAX_RUNTIME_SECONDS = 60;
    private const DEFAULT_SLEEP_MS = 100;

    protected $signature = 'dashboard:lotto-risk-retention
        {--days= : Keep detailed snapshots in last N days}
        {--chunk= : Delete batch size per iteration}
        {--max-runtime= : Max runtime in seconds}
        {--sleep-ms= : Sleep between batches in milliseconds}
        {--dry-run : Preview only, do not delete}';

    protected $description = 'Cleanup lotto risk snapshots by retention policy (non hot-path)';

    public function handle(): int
    {
        if (! Schema::hasTable('lotto_dashboard_risk_snapshot')) {
            $this->warn('table lotto_dashboard_risk_snapshot not found');

            return 0;
        }

        $daysOption = $this->option('days');
        $days = LottoDashboardMetricConfig::riskSnapshotRetentionDays();
        if ($daysOption !== null) {
            $normalizedDaysOption = trim((string) $daysOption);
            if ($normalizedDaysOption !== '') {
                if (! preg_match('/^-?\d+$/', $normalizedDaysOption)) {
                    $this->error('--days must be an integer between 1 and 90');

                    return 1;
                }
                $days = (int) $normalizedDaysOption;
            }
        }

        if ($days < self::MIN_RETENTION_DAYS || $days > self::MAX_RETENTION_DAYS) {
            $this->error(sprintf(
                '--days must be between %d and %d',
                self::MIN_RETENTION_DAYS,
                self::MAX_RETENTION_DAYS
            ));

            return 1;
        }

        $chunkSize = (int) ($this->option('chunk') ?: self::DEFAULT_CHUNK_SIZE);
        if ($chunkSize < 1) {
            $this->error('--chunk ต้องมากกว่า 0');

            return 1;
        }

        $maxRuntime = (int) ($this->option('max-runtime') ?: self::DEFAULT_MAX_RUNTIME_SECONDS);
        if ($maxRuntime < 1) {
            $this->error('--max-runtime ต้องมากกว่า 0');

            return 1;
        }

        $sleepMs = (int) ($this->option('sleep-ms') ?: self::DEFAULT_SLEEP_MS);
        if ($sleepMs < 0) {
            $this->error('--sleep-ms ต้องมากกว่าหรือเท่ากับ 0');

            return 1;
        }

        $cutoff = Carbon::now()->subDays($days)->startOfSecond();
        $cutoffText = $cutoff->toDateTimeString();
        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $sampleCount = (int) DB::table('lotto_dashboard_risk_snapshot')
                ->where('snapshot_at', '<', $cutoffText)
                ->limit($chunkSize)
                ->count();

            $this->line('retention_days='.$days);
            $this->line('cutoff='.$cutoffText);
            $this->line('dry_run=yes');
            $this->line('first_batch_would_delete='.$sampleCount);
            $this->line('chunk='.$chunkSize);
            $this->line('max_runtime='.$maxRuntime);
            $this->line('sleep_ms='.$sleepMs);

            return 0;
        }

        $startedAt = microtime(true);
        $totalDeleted = 0;
        $batchNumber = 0;

        while (true) {
            if ((microtime(true) - $startedAt) >= $maxRuntime) {
                $this->warn(sprintf(
                    'stopped by max-runtime after deleting %d rows before %s',
                    $totalDeleted,
                    $cutoffText
                ));

                break;
            }

            $ids = DB::table('lotto_dashboard_risk_snapshot')
                ->where('snapshot_at', '<', $cutoffText)
                ->orderBy('id')
                ->limit($chunkSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted = (int) DB::table('lotto_dashboard_risk_snapshot')
                ->whereIn('id', $ids->all())
                ->delete();

            $totalDeleted += $deleted;
            $batchNumber++;

            $this->line(sprintf(
                'batch=%d deleted=%d total_deleted=%d cutoff=%s',
                $batchNumber,
                $deleted,
                $totalDeleted,
                $cutoffText
            ));

            if ($deleted === 0 || $deleted < $chunkSize) {
                break;
            }

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        $this->info(sprintf('deleted %d rows before %s', $totalDeleted, $cutoffText));

        return 0;
    }
}
