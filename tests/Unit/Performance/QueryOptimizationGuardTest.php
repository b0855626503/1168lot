<?php

namespace Tests\Unit\Performance;

use PHPUnit\Framework\TestCase;

class QueryOptimizationGuardTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_member_repository_get_user_groups_or_lookup_under_active_scope(): void
    {
        $contents = file_get_contents($this->rootPath.'/packages/Gametech/Member/src/Repositories/MemberRepository.php');

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("->active()\n            ->where(function (Builder \$query) use (\$search) {", $contents);
        $this->assertStringContainsString("->orWhereHas('user', function (Builder \$userQuery) use (\$search) {", $contents);
    }

    public function test_dashboard_loadsum_uses_sargable_datetime_ranges_for_primary_metrics(): void
    {
        $contents = file_get_contents($this->rootPath.'/packages/Gametech/Admin/src/Http/Controllers/DashboardController.php');

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('private function resolveDateTimeRange(string $startDate, string $endDate): array', $contents);
        $this->assertStringContainsString("->whereBetween('date_create', [\$startDateTime, \$endDateTimeExclusive])", $contents);
        $this->assertStringContainsString("->whereBetween('date_create', [\$monthStartDateTime, \$monthEndDateTimeExclusive])", $contents);
        $this->assertStringContainsString("->whereBetween('date_approve', [\$startDateTime, \$endDateTimeExclusive])", $contents);
        $this->assertStringContainsString("->whereBetween('date_approve', [\$monthStartDateTime, \$monthEndDateTimeExclusive])", $contents);
        $loadSumSegment = substr(
            $contents,
            strpos($contents, 'public function loadSum'),
            strpos($contents, 'public function loadSumAll') - strpos($contents, 'public function loadSum')
        );
        $normalizedSegment = preg_replace('/\/\/.*$/m', '', $loadSumSegment) ?? $loadSumSegment;
        $this->assertStringNotContainsString("DB::raw('DATE(", $normalizedSegment);
        $this->assertStringNotContainsString('whereDate(', $normalizedSegment);
    }

    public function test_bank_payment_observer_uses_cached_sargable_pending_count(): void
    {
        $contents = file_get_contents($this->rootPath.'/packages/Gametech/Payment/src/Observers/BankPaymentObserver.php');

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('PENDING_DEPOSIT_CACHE_KEY_PREFIX', $contents);
        $this->assertStringContainsString('resolvePendingDepositCount', $contents);
        $this->assertStringContainsString("->where('date_create', '>=', \$rangeStart)", $contents);
        $this->assertStringContainsString("->where('date_create', '<', \$rangeEndExclusive)", $contents);
        $this->assertStringNotContainsString('whereDate(\'date_create\'', $contents);
    }

    public function test_dashboard_activity_uses_tight_recent_feed_limits(): void
    {
        $contents = file_get_contents($this->rootPath.'/packages/Gametech/Admin/src/Services/DashboardService.php');

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('private const ACTIVITY_FEED_LIMIT = 10;', $contents);
        $activitySegment = substr(
            $contents,
            strpos($contents, 'public function getActivity'),
            strpos($contents, 'private function getRecentLottoBetsActivity') - strpos($contents, 'public function getActivity')
        );
        $this->assertStringNotContainsString('take(100)', $activitySegment);
        $this->assertStringContainsString('take(self::ACTIVITY_FEED_LIMIT)', $activitySegment);
    }

    public function test_marketing_campaign_report_uses_range_queries_and_sql_aggregation(): void
    {
        $contents = file_get_contents($this->rootPath.'/packages/Gametech/Marketing/src/Http/Controllers/Admin/MarketingCampaignController.php');

        $this->assertNotFalse($contents);
        $loadReportSegment = substr(
            $contents,
            strpos($contents, 'public function loadReport'),
            strpos($contents, 'private function normalizeDateRange') - strpos($contents, 'public function loadReport')
        );
        $this->assertStringContainsString('joinSub($firstDepositDates', $loadReportSegment);
        $this->assertStringContainsString("->whereBetween('date_regis', [\$startDate, \$endDate])", $loadReportSegment);
        $this->assertStringContainsString("->where('date_approve', '>=', \$reportStartAt)", $loadReportSegment);
        $this->assertStringNotContainsString("DB::raw('DATE(", $loadReportSegment);
        $this->assertStringNotContainsString('whereDate(', $loadReportSegment);
    }

    public function test_bank_payment_repository_uses_cached_account_daily_totals_without_where_date(): void
    {
        $contents = file_get_contents($this->rootPath.'/packages/Gametech/Payment/src/Repositories/BankPaymentRepository.php');

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('resolveAccountDailyDepositTotal', $contents);
        $this->assertStringContainsString('accountDailyDepositTotalCacheKey', $contents);
        $this->assertStringContainsString("->where('date_topup', '>=', \$rangeStart)", $contents);
        $this->assertStringContainsString("->where('date_topup', '<', \$rangeEndExclusive)", $contents);
        $this->assertStringNotContainsString('whereDate(\'date_topup\'', $contents);
    }

    public function test_realtime_counters_are_shared_and_sargable(): void
    {
        $dashboardController = file_get_contents($this->rootPath.'/packages/Gametech/Admin/src/Http/Controllers/DashboardController.php');
        $realtimeUpdate = file_get_contents($this->rootPath.'/app/Services/RealtimeUpdate.php');
        $realtimeCounterService = file_get_contents($this->rootPath.'/app/Services/RealtimeCounterService.php');

        $this->assertNotFalse($dashboardController);
        $this->assertNotFalse($realtimeUpdate);
        $this->assertNotFalse($realtimeCounterService);
        $this->assertStringContainsString('RealtimeCounterService', $dashboardController);
        $this->assertStringContainsString('RealtimeCounterService::class', $realtimeUpdate);
        $this->assertStringContainsString("->where('date_create', '>=', \$todayStartAt)", $realtimeCounterService);
        $this->assertStringContainsString("->where('date_create', '<', \$tomorrowStartAt)", $realtimeCounterService);
        $this->assertStringNotContainsString('whereDate(', $realtimeCounterService);
    }

    public function test_dashboard_summary_projector_lotto_risk_snapshot_uses_direct_date_comparison(): void
    {
        $contents = file_get_contents($this->rootPath.'/app/Services/Dashboard/DashboardSummaryProjector.php');

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("->where('d.draw_date', '<=', \$summaryDate)", $contents);
        $this->assertStringNotContainsString("whereDate('d.draw_date'", $contents);
    }

    public function test_dashboard_lotto_risk_summary_aggregates_latest_snapshot_only(): void
    {
        $contents = file_get_contents($this->rootPath.'/packages/Gametech/Admin/src/Services/DashboardService.php');

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("\$latestSnapshotAt = DB::table('lotto_dashboard_risk_snapshot')", $contents);
        $this->assertStringContainsString("->where('snapshot_at', \$latestSnapshotAt)", $contents);
        $this->assertStringNotContainsString("->where('snapshot_at', '>=', \$startAt)\n            ->where('snapshot_at', '<', \$endAt)\n            ->selectRaw", $contents);
    }

    public function test_lotto_risk_snapshot_migration_disables_auto_update_timestamp(): void
    {
        $contents = file_get_contents($this->rootPath.'/database/migrations/2026_04_10_193231_alter_lotto_dashboard_risk_snapshot_timestamp.php');

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('MODIFY `snapshot_at` TIMESTAMP NOT NULL', $contents);
        $this->assertStringContainsString('DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', $contents);
    }
}
