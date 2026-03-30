<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Services\InternalResultSources\InternalResultSourceMigrationPlanner;
use PHPUnit\Framework\TestCase;

class InternalResultSourceMigrationPlannerTest extends TestCase
{
    private InternalResultSourceMigrationPlanner $planner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->planner = new InternalResultSourceMigrationPlanner();
    }

    public function test_plans_exphuay_endpoint_to_internal_route(): void
    {
        $plan = $this->planner->plan(
            'https://exphuay.com/backward/list/__data.json?page=1&x-sveltekit-invalidated=01',
            'https://example.test'
        );

        $this->assertNotNull($plan);
        $this->assertSame('exphuay', $plan['source_key']);
        $this->assertSame('https://example.test/internal/lottery/results/exphuay/list', $plan['target_endpoint_url']);
        $this->assertSame('{{lookup_date}}', $plan['recommended_query_template']['date']);
    }

    public function test_plans_dowjones_midnight_endpoint_to_internal_route(): void
    {
        $plan = $this->planner->plan(
            'https://api.dowjones-midnight.com/result?date=2026-03-30',
            'https://example.test'
        );

        $this->assertNotNull($plan);
        $this->assertSame('dowjones-midnight', $plan['source_key']);
        $this->assertSame('https://example.test/internal/lottery/results/dowjones-midnight', $plan['target_endpoint_url']);
    }

    public function test_returns_null_for_unsupported_endpoint(): void
    {
        $plan = $this->planner->plan(
            'https://unsupported.example.com/results',
            'https://example.test'
        );

        $this->assertNull($plan);
    }
}

