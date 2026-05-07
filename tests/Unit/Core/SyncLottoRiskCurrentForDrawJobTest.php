<?php

namespace Tests\Unit\Core;

use App\Jobs\SyncLottoRiskCurrentForDrawJob;
use App\Services\Dashboard\DashboardSummarySyncService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Mockery;
use PHPUnit\Framework\TestCase;

class SyncLottoRiskCurrentForDrawJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_job_is_unique_per_draw_and_web_code(): void
    {
        $job = new SyncLottoRiskCurrentForDrawJob(501, 'main');

        $this->assertInstanceOf(ShouldBeUnique::class, $job);
        $this->assertSame('lotto-risk-current:draw:501:main', $job->uniqueId());
    }

    public function test_handle_calls_draw_level_sync_service(): void
    {
        $job = new SyncLottoRiskCurrentForDrawJob(888, 'main', 'lotto', '42', ['reason' => 'test']);

        $service = Mockery::mock(DashboardSummarySyncService::class);
        $service->shouldReceive('syncRiskCurrentForDraw')
            ->once()
            ->with(888, 'main', 'lotto', '42', ['reason' => 'test']);

        $job->handle($service);

        $this->assertTrue(true);
    }
}
