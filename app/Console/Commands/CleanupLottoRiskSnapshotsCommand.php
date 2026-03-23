<?php

namespace App\Console\Commands;

use App\Services\Dashboard\LottoDashboardMetricConfig;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanupLottoRiskSnapshotsCommand extends Command
{
    protected $signature = 'dashboard:lotto-risk-retention
        {--days= : Keep detailed snapshots in last N days}
        {--dry-run : Preview only, do not delete}';

    protected $description = 'Cleanup lotto risk snapshots by retention policy (non hot-path)';

    public function handle(): int
    {
        if (!Schema::hasTable('lotto_dashboard_risk_snapshot')) {
            $this->warn('table lotto_dashboard_risk_snapshot not found');
            return 0;
        }

        $days = (int) ($this->option('days') ?: LottoDashboardMetricConfig::riskSnapshotRetentionDays());
        if ($days < 1) {
            $this->error('--days ต้องมากกว่า 0');
            return 1;
        }

        $cutoff = Carbon::now()->subDays($days)->startOfSecond();
        $query = DB::table('lotto_dashboard_risk_snapshot')
            ->where('snapshot_at', '<', $cutoff->toDateTimeString());

        $count = (int) (clone $query)->count();
        if ((bool) $this->option('dry-run')) {
            $this->line(sprintf('[dry-run] will delete %d rows before %s', $count, $cutoff->toDateTimeString()));
            return 0;
        }

        $deleted = (int) $query->delete();
        $this->info(sprintf('deleted %d rows before %s', $deleted, $cutoff->toDateTimeString()));

        return 0;
    }
}

