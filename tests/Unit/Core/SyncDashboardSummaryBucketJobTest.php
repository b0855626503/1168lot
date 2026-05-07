<?php

namespace Tests\Unit\Core;

use App\Jobs\SyncDashboardSummaryBucket;
use App\Services\Dashboard\DashboardSummarySyncService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Mockery;
use PHPUnit\Framework\TestCase;

class SyncDashboardSummaryBucketJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_job_is_unique_per_bucket_until_finished(): void
    {
        $job = new SyncDashboardSummaryBucket('2026-04-04', 'main', ['net'], 'lotto', '10');

        $this->assertInstanceOf(ShouldBeUnique::class, $job);
        $this->assertSame('dashboard-summary:main:2026-04-04', $job->uniqueId());
        $this->assertSame(600, $job->uniqueFor);
    }

    public function test_handle_consumes_pending_payload_before_syncing_bucket(): void
    {
        $job = new SyncDashboardSummaryBucket('2026-04-04', 'main', ['lotto_cash'], 'lotto', '10');

        $service = Mockery::mock(DashboardSummarySyncService::class);
        $service->shouldReceive('consumePendingBucketPayload')
            ->once()
            ->with('2026-04-04', 'main', ['lotto_cash'], 'lotto', '10', [])
            ->andReturn([
                'summary_date' => '2026-04-04',
                'web_code' => 'main',
                'updated_sections' => ['lotto_cash', 'net'],
                'source_type' => 'wallet',
                'source_id' => '20',
                'audit_context' => ['reason' => 'test reason', 'actor_id' => 123],
            ]);

        $service->shouldReceive('syncBucket')
            ->once()
            ->withArgs(function (string $summaryDate, string $webCode, array $updatedSections, ?string $sourceType, ?string $sourceId, array $auditContext): bool {
                return $summaryDate === '2026-04-04'
                    && $webCode === 'main'
                    && $updatedSections === ['lotto_cash', 'net']
                    && $sourceType === 'wallet'
                    && $sourceId === '20'
                    && $auditContext === ['reason' => 'test reason', 'actor_id' => 123];
            });

        $job->handle($service);

        $this->assertTrue(true);
    }
}
