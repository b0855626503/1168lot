<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

class LottoProfitLossForecastFilterUiTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_profit_loss_forecast_market_filter_uses_grouped_select2_options(): void
    {
        $this->assertGroupedMarketFilterUi(
            '/packages/Gametech/Lotto/src/Http/Controllers/Admin/LottoProfitLossForecastReportController.php',
            '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/profit_loss_forecast_report/create.blade.php',
            '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/profit_loss_forecast_report/table.blade.php',
            'syncProfitLossForecastFilterUi'
        );
    }

    public function test_all_lotto_reports_with_market_filter_use_grouped_select2_options(): void
    {
        $pages = [
            [
                'controller' => '/packages/Gametech/Lotto/src/Http/Controllers/Admin/LottoTicketController.php',
                'create' => '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/tickets/create.blade.php',
                'table' => '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/tickets/table.blade.php',
                'sync' => null,
            ],
            [
                'controller' => '/packages/Gametech/Lotto/src/Http/Controllers/Admin/LottoProfitLossForecastReportController.php',
                'create' => '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/profit_loss_forecast_report/create.blade.php',
                'table' => '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/profit_loss_forecast_report/table.blade.php',
                'sync' => 'syncProfitLossForecastFilterUi',
            ],
            [
                'controller' => '/packages/Gametech/Lotto/src/Http/Controllers/Admin/LottoMemberBetTypesReportController.php',
                'create' => '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/member_bet_types_report/create.blade.php',
                'table' => '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/member_bet_types_report/table.blade.php',
                'sync' => 'syncMemberBetTypesFilterUi',
            ],
            [
                'controller' => '/packages/Gametech/Lotto/src/Http/Controllers/Admin/LottoTicketsCancelReportController.php',
                'create' => '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/tickets_cancel_report/create.blade.php',
                'table' => '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/tickets_cancel_report/table.blade.php',
                'sync' => 'syncTicketsCancelFilterUi',
            ],
            [
                'controller' => '/packages/Gametech/Lotto/src/Http/Controllers/Admin/LottoBlockedNumbersReportController.php',
                'create' => '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/blocked_numbers_report/create.blade.php',
                'table' => '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/blocked_numbers_report/table.blade.php',
                'sync' => 'syncBlockedNumbersReportFilterUi',
            ],
            [
                'controller' => '/packages/Gametech/Lotto/src/Http/Controllers/Admin/LottoExposureReportController.php',
                'create' => '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/exposure_report/create.blade.php',
                'table' => '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/exposure_report/table.blade.php',
                'sync' => 'syncExposureFilterUi',
            ],
        ];

        foreach ($pages as $page) {
            $this->assertGroupedMarketFilterUi(
                $page['controller'],
                $page['create'],
                $page['table'],
                $page['sync']
            );
        }
    }

    private function assertGroupedMarketFilterUi(
        string $controllerPath,
        string $createViewPath,
        string $tableViewPath,
        ?string $syncHelperName
    ): void {
        $controller = file_get_contents($this->rootPath . $controllerPath);
        $view = file_get_contents($this->rootPath . $createViewPath);
        $tableView = file_get_contents($this->rootPath . $tableViewPath);

        $this->assertNotFalse($controller, $controllerPath);
        $this->assertNotFalse($view, $createViewPath);
        $this->assertNotFalse($tableView, $tableViewPath);

        $this->assertStringContainsString("with('group:id,name,sort')", $controller, $controllerPath);
        $this->assertStringContainsString("->groupBy(static function (LotteryMarket \$market): string", $controller, $controllerPath);
        $this->assertStringContainsString("'label' => (string) \$groupName", $controller, $controllerPath);
        $this->assertStringContainsString("<optgroup label=\"{{ \$group['label'] ?? '-' }}\">", $view, $createViewPath);
        $this->assertStringContainsString("data-logo=\"{{ \$option['logo'] ?? '' }}\"", $view, $createViewPath);
        $combinedViewScripts = $view . "\n" . $tableView;
        $this->assertStringContainsString("typeof \$marketSelect.select2 !== 'function'", $combinedViewScripts, $createViewPath);
        $this->assertStringContainsString("\$marketSelect.select2({", $combinedViewScripts, $createViewPath);

        if ($syncHelperName !== null) {
            $this->assertStringContainsString($syncHelperName, $tableView, $tableViewPath);
            $this->assertStringContainsString("\$element.trigger('change.select2')", $tableView, $tableViewPath);
        }
    }
}
