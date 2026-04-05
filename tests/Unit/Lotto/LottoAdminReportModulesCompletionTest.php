<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

class LottoAdminReportModulesCompletionTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_mock_report_routes_no_longer_use_section_controller_or_mockup_view(): void
    {
        $routes = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Routes/admin.php');

        $this->assertNotFalse($routes);
        $this->assertStringContainsString("LottoProfitLossForecastReportController@index", $routes);
        $this->assertStringContainsString("LottoMemberBetTypesReportController@index", $routes);
        $this->assertStringContainsString("LottoTicketsCancelReportController@index", $routes);
        $this->assertStringContainsString("LottoBlockedNumbersReportController@index", $routes);
        $this->assertStringNotContainsString("section' => 'reports.profit_loss_forecast'", $routes);
        $this->assertStringNotContainsString("section' => 'reports.member_bet_types'", $routes);
        $this->assertStringNotContainsString("section' => 'reports.tickets_cancel'", $routes);
        $this->assertStringNotContainsString("section' => 'reports.blocked_numbers'", $routes);
        $this->assertStringNotContainsString("view' => 'admin::module.lotto.reports.mockup'", $routes);
    }
}
