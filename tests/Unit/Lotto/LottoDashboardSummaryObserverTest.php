<?php

namespace Tests\Unit\Lotto;

use App\Services\Dashboard\DashboardSummarySyncService;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Observers\LottoDashboardSummaryObserver;
use Mockery;
use Tests\TestCase;

class LottoDashboardSummaryObserverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_updated_draw_closed_dispatches_draw_closed_source_override(): void
    {
        $service = Mockery::mock(DashboardSummarySyncService::class);
        $service->shouldReceive('dispatchForModelChange')
            ->once()
            ->with('lotto', Mockery::type('array'), Mockery::type('array'), 'draw_closed');
        $this->app->instance(DashboardSummarySyncService::class, $service);

        $draw = Mockery::mock(LottoDraw::class)->makePartial();
        $draw->status = 'closed';
        $draw->updated_at = now();
        $draw->result_at = null;
        $draw->shouldReceive('wasChanged')->with(['status', 'result_at', 'result_number'])->andReturn(true);
        $draw->shouldReceive('getOriginal')->with('updated_at')->andReturn(now()->subMinute());
        $draw->shouldReceive('getOriginal')->with('result_at')->andReturn(null);
        $draw->shouldReceive('getOriginal')->with('status')->andReturn('open');
        $draw->shouldReceive('getKey')->andReturn(101);

        (new LottoDashboardSummaryObserver)->updated($draw);
        $this->addToAssertionCount(1);
    }

    public function test_updated_draw_resulted_dispatches_draw_resulted_source_override(): void
    {
        $service = Mockery::mock(DashboardSummarySyncService::class);
        $service->shouldReceive('dispatchForModelChange')
            ->once()
            ->with('lotto', Mockery::type('array'), Mockery::type('array'), 'draw_resulted');
        $this->app->instance(DashboardSummarySyncService::class, $service);

        $draw = Mockery::mock(LottoDraw::class)->makePartial();
        $draw->status = 'resulted';
        $draw->updated_at = now();
        $draw->result_at = now();
        $draw->shouldReceive('wasChanged')->with(['status', 'result_at', 'result_number'])->andReturn(true);
        $draw->shouldReceive('getOriginal')->with('updated_at')->andReturn(now()->subMinute());
        $draw->shouldReceive('getOriginal')->with('result_at')->andReturn(null);
        $draw->shouldReceive('getOriginal')->with('status')->andReturn('closed');
        $draw->shouldReceive('getKey')->andReturn(102);

        (new LottoDashboardSummaryObserver)->updated($draw);
        $this->addToAssertionCount(1);
    }
}
