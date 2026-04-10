<?php

namespace Tests\Unit\Core;

use App\Services\Dashboard\DashboardBucketResolver;
use App\Services\Dashboard\DashboardSummaryBroadcastNotifier;
use App\Services\Dashboard\DashboardSummaryProjector;
use App\Services\Dashboard\DashboardSummarySyncService;
use App\Services\Dashboard\DashboardWebCodeResolver;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class DashboardSummarySyncServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_pending_bucket_payload_merges_sections_and_uses_latest_source_context(): void
    {
        $service = $this->makeService();

        $service->mergePendingBucketPayload(
            summaryDate: '2026-04-04',
            webCode: 'main',
            updatedSections: ['lotto_cash'],
            sourceType: 'lotto',
            sourceId: '100'
        );

        $service->mergePendingBucketPayload(
            summaryDate: '2026-04-04',
            webCode: 'main',
            updatedSections: ['net', 'lotto_product'],
            sourceType: 'wallet',
            sourceId: '200'
        );

        $payload = $service->consumePendingBucketPayload(
            summaryDate: '2026-04-04',
            webCode: 'main',
            fallbackUpdatedSections: ['lotto_cash'],
            fallbackSourceType: 'fallback',
            fallbackSourceId: 'fallback-id'
        );

        $this->assertSame('2026-04-04', $payload['summary_date']);
        $this->assertSame('main', $payload['web_code']);
        $this->assertSame(['lotto_cash', 'lotto_product', 'net'], $payload['updated_sections']);
        $this->assertSame('wallet', $payload['source_type']);
        $this->assertSame('200', $payload['source_id']);

        $nextPayload = $service->consumePendingBucketPayload(
            summaryDate: '2026-04-04',
            webCode: 'main',
            fallbackUpdatedSections: ['lotto_risk'],
            fallbackSourceType: 'fallback',
            fallbackSourceId: 'fallback-id'
        );

        $this->assertSame(['lotto_risk'], $nextPayload['updated_sections']);
        $this->assertSame('fallback', $nextPayload['source_type']);
        $this->assertSame('fallback-id', $nextPayload['source_id']);
    }

    public function test_sync_bucket_chunks_risk_snapshot_upserts(): void
    {
        $content = file_get_contents(base_path('app/Services/Dashboard/DashboardSummarySyncService.php'));

        $this->assertStringContainsString('RISK_SNAPSHOT_UPSERT_CHUNK_SIZE', $content);
        $this->assertStringContainsString('array_chunk($rows, self::RISK_SNAPSHOT_UPSERT_CHUNK_SIZE)', $content);
        $this->assertStringContainsString("DB::table('lotto_dashboard_risk_snapshot')->upsert", $content);
    }

    private function makeService(): DashboardSummarySyncService
    {
        return new DashboardSummarySyncService(
            new DashboardBucketResolver(new DashboardWebCodeResolver),
            new DashboardWebCodeResolver,
            Mockery::mock(DashboardSummaryProjector::class),
            Mockery::mock(DashboardSummaryBroadcastNotifier::class),
        );
    }
}
