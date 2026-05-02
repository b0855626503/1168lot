<?php

namespace Tests\Feature\Marketing;

use Tests\TestCase;

class MarketingCampaignDashboardUiContractTest extends TestCase
{
    public function test_campaign_dashboard_view_contains_required_phase4_controls(): void
    {
        $view = file_get_contents(base_path('packages/Gametech/Marketing/src/Resources/views/admin/module/marketing_campaign/view.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('id="phase4_refresh_btn"', $view);
        $this->assertStringContainsString('id="phase4_search_date"', $view);
        $this->assertStringContainsString('setInterval(fetchDashboard, pollingMs);', $view);
        $this->assertStringContainsString('id="phase4_dashboard_error"', $view);
        $this->assertStringContainsString('data-kpi="financial.bonus_amount"', $view);
        $this->assertStringContainsString("route('admin.marketing_campaign.dashboard.summary'", $view);
    }

    public function test_campaign_dashboard_view_does_not_include_main_dashboard_risky_widgets(): void
    {
        $view = file_get_contents(base_path('packages/Gametech/Marketing/src/Resources/views/admin/module/marketing_campaign/view.blade.php'));

        $this->assertIsString($view);
        $this->assertStringNotContainsString('risky number', strtolower($view));
        $this->assertStringNotContainsString('lotto-risky', strtolower($view));
    }
}
