<?php

namespace App\Console\Commands;

use App\Services\Dashboard\LottoDashboardMetricConfig;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanupLottoRiskSnapshotsCommand extends Command
{
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

        $days = (int) ($this->option('days') ?: LottoDashboardMetricConfig::riskSnapshotRetentionDays());
        if ($days < 1) {
            $this->error('--days ต้องมากกว่า 0');

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

        $this->line(sprintf(
            'cleanup cutoff=%s days=%d chunk=%d max_runtime=%ds sleep_ms=%d dry_run=%s',
            $cutoffText,
            $days,
            $chunkSize,
            $maxRuntime,
            $sleepMs,
            (bool) $this->option('dry-run') ? 'yes' : 'no'
        ));

        if ((bool) $this->option('dry-run')) {
            $sampleCount = (int) DB::table('lotto_dashboard_risk_snapshot')
                ->where('snapshot_at', '<', $cutoffText)
                ->limit($chunkSize)
                ->count();

            $this->line(sprintf(
                '[dry-run] first batch would delete up to %d rows before %s',
                $sampleCount,
                $cutoffText
            ));

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
