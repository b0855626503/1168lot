<?php

namespace Tests\Feature\Marketing;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminCampaignDashboardRouteContractTest extends TestCase
{
    public function test_dashboard_summary_route_name_and_path_match_contract(): void
    {
        $route = Route::getRoutes()->getByName('admin.marketing_campaign.dashboard.summary');

        $this->assertNotNull($route);
        $this->assertSame('marketing_campaign/{campaign}/dashboard/summary', $route->uri());
    }

    public function test_dashboard_summary_route_is_protected_by_admin_auth_middleware(): void
    {
        $route = Route::getRoutes()->getByName('admin.marketing_campaign.dashboard.summary');
        $middleware = $route ? $route->gatherMiddleware() : [];

        $this->assertContains('admin', $middleware);
        $this->assertContains('auth', $middleware);
    }

    public function test_unauthorized_request_is_blocked_by_middleware_stack(): void
    {
        $response = $this->get('http://admin.localhost/marketing_campaign/1/dashboard/summary');

        $this->assertTrue(
            in_array($response->status(), [302, 401, 403], true),
            'Expected middleware to block unauthenticated access.'
        );
    }
}
