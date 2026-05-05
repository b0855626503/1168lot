<?php

namespace Tests\Unit\Core;

use App\Services\Dashboard\LottoDashboardMetricConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CleanupLottoRiskSnapshotsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->recreateSnapshotTable();
    }

    protected function tearDown(): void
    {
        if (Schema::hasTable('lotto_dashboard_risk_snapshot')) {
            Schema::drop('lotto_dashboard_risk_snapshot');
        }

        parent::tearDown();
    }

    public function test_default_retention_is_7_days(): void
    {
        config()->set('dashboard.lotto.risk_snapshot_retention_days', 7);

        $this->assertSame(7, LottoDashboardMetricConfig::riskSnapshotRetentionDays());
    }

    public function test_config_override_for_retention_days_works(): void
    {
        config()->set('dashboard.lotto.risk_snapshot_retention_days', 30);

        $this->assertSame(30, LottoDashboardMetricConfig::riskSnapshotRetentionDays());
    }

    public function test_invalid_config_retention_days_falls_back_to_7(): void
    {
        config()->set('dashboard.lotto.risk_snapshot_retention_days', 0);
        $this->assertSame(7, LottoDashboardMetricConfig::riskSnapshotRetentionDays());

        config()->set('dashboard.lotto.risk_snapshot_retention_days', -5);
        $this->assertSame(7, LottoDashboardMetricConfig::riskSnapshotRetentionDays());

        config()->set('dashboard.lotto.risk_snapshot_retention_days', 999);
        $this->assertSame(7, LottoDashboardMetricConfig::riskSnapshotRetentionDays());
    }

    public function test_dry_run_uses_default_retention_and_prints_resolved_values(): void
    {
        config()->set('dashboard.lotto.risk_snapshot_retention_days', 7);
        $this->seedSnapshotRows();

        $this->artisan('dashboard:lotto-risk-retention --dry-run')
            ->expectsOutputToContain('retention_days=7')
            ->expectsOutputToContain('cutoff=')
            ->expectsOutputToContain('dry_run=yes')
            ->expectsOutputToContain('would_delete=')
            ->assertExitCode(0);
    }

    public function test_days_option_overrides_config_value(): void
    {
        config()->set('dashboard.lotto.risk_snapshot_retention_days', 7);
        $this->seedSnapshotRows();

        $this->artisan('dashboard:lotto-risk-retention --dry-run --days=30')
            ->expectsOutputToContain('retention_days=30')
            ->assertExitCode(0);
    }

    public function test_invalid_days_zero_is_rejected(): void
    {
        $this->artisan('dashboard:lotto-risk-retention --dry-run --days=0')
            ->expectsOutputToContain('--days must be between 1 and 90')
            ->assertExitCode(1);
    }

    public function test_invalid_days_negative_is_rejected(): void
    {
        $this->artisan('dashboard:lotto-risk-retention --dry-run --days=-2')
            ->expectsOutputToContain('--days must be between 1 and 90')
            ->assertExitCode(1);
    }

    private function recreateSnapshotTable(): void
    {
        if (Schema::hasTable('lotto_dashboard_risk_snapshot')) {
            Schema::drop('lotto_dashboard_risk_snapshot');
        }

        Schema::create('lotto_dashboard_risk_snapshot', function (Blueprint $table): void {
            $table->increments('id');
            $table->dateTime('snapshot_at');
        });
    }

    private function seedSnapshotRows(): void
    {
        DB::table('lotto_dashboard_risk_snapshot')->insert([
            ['snapshot_at' => now()->subDays(10)->toDateTimeString()],
            ['snapshot_at' => now()->subDays(1)->toDateTimeString()],
        ]);
    }
}
