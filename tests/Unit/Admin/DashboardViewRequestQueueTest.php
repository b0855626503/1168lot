<?php

namespace Tests\Unit\Admin;

use Tests\TestCase;

class DashboardViewRequestQueueTest extends TestCase
{
    public function test_dashboard_uses_limited_background_request_queue(): void
    {
        $view = file_get_contents(base_path('packages/Gametech/Admin/src/Resources/views/module/dashboard/index.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('settleDashboardQueue(requestFactories, concurrency = 2)', $view);
        $this->assertStringContainsString('X-Dashboard-Background', $view);
        $this->assertStringContainsString('scheduleSecondaryRefresh()', $view);
        $this->assertStringNotContainsString('Promise.allSettled', $view);
    }

    public function test_dashboard_aborts_background_requests_when_navigating_to_another_menu(): void
    {
        $view = file_get_contents(base_path('packages/Gametech/Admin/src/Resources/views/module/dashboard/index.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('setupDashboardNavigationAbort()', $view);
        $this->assertStringContainsString("document.addEventListener('click', this.dashboardNavigationAbortHandler, true)", $view);
        $this->assertStringContainsString('prepareDashboardNavigation()', $view);
        $this->assertStringContainsString('AbortController', $view);
        $this->assertStringContainsString('config.signal = controller.signal', $view);
        $this->assertStringContainsString('controller.abort()', $view);
    }

    public function test_dashboard_initializes_date_filter_without_scheduling_duplicate_refresh(): void
    {
        $view = file_get_contents(base_path('packages/Gametech/Admin/src/Resources/views/module/dashboard/index.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('suppressFilterRefresh: false', $view);
        $this->assertStringContainsString('if (this.suppressFilterRefresh)', $view);
        $this->assertStringContainsString('self.suppressFilterRefresh = true', $view);
        $this->assertStringContainsString('self.suppressFilterRefresh = false', $view);
        $this->assertStringContainsString('const { start, end } = getHiddenDates();', $view);
    }
}
