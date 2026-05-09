<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

class LottoResultCorrectionAdminScaffoldTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_acl_menu_and_routes_include_result_correction_keys(): void
    {
        $acl = file_get_contents($this->rootPath.'/packages/Gametech/Lotto/src/Config/acl.php');
        $menu = file_get_contents($this->rootPath.'/packages/Gametech/Lotto/src/Config/admin-menu.php');
        $routes = file_get_contents($this->rootPath.'/packages/Gametech/Lotto/src/Routes/admin.php');
        $dataTable = file_get_contents($this->rootPath.'/packages/Gametech/Lotto/src/DataTables/LottoResultCorrectionDataTable.php');

        $this->assertIsString($acl);
        $this->assertIsString($menu);
        $this->assertIsString($routes);
        $this->assertIsString($dataTable);

        $this->assertStringContainsString("'lotto_settings.draws.correct_result'", $acl);
        $this->assertStringContainsString("'lotto_result_corrections.view'", $acl);
        $this->assertStringContainsString("'lotto_reports.result_corrections'", $acl);
        $this->assertStringContainsString("'lotto_result_corrections.view_detail'", $acl);
        $this->assertStringContainsString("'lotto_result_corrections.debit_remaining'", $acl);
        $this->assertStringContainsString("'lotto_reports.result_corrections'", $menu);
        $this->assertStringContainsString('draws/correct-result-preview', $routes);
        $this->assertStringContainsString('reports/result-corrections', $routes);
        $this->assertStringContainsString("->where('status', '!=', 'previewed')", $dataTable);
    }
}
