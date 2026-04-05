<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

class LottoAdminModulePatternCompletionTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_member_permissions_is_no_longer_placeholder_section(): void
    {
        $routes = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Routes/admin.php');
        $indexView = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/member_permissions/index.blade.php');
        $addEditView = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/member_permissions/addedit.blade.php');

        $this->assertNotFalse($routes);
        $this->assertNotFalse($indexView);
        $this->assertNotFalse($addEditView);

        $this->assertStringContainsString('MemberLottoPermissionController@index', $routes);
        $this->assertStringContainsString("member-permissions/create", $routes);
        $this->assertStringContainsString("member-permissions/update", $routes);
        $this->assertStringContainsString("member-permissions/loaddata", $routes);
        $this->assertStringNotContainsString('admin::module.lotto._shared.page', $indexView);
        $this->assertStringContainsString("admin.lotto.member_permissions.create", $addEditView);
        $this->assertStringContainsString("admin.lotto.member_permissions.edit", $addEditView);
    }

    public function test_exposure_report_uses_real_controller_and_filters(): void
    {
        $routes = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Routes/admin.php');
        $controller = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Http/Controllers/Admin/LottoExposureReportController.php');
        $createView = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/exposure_report/create.blade.php');
        $tableView = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/exposure_report/table.blade.php');
        $dataTable = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/DataTables/LottoExposureReportDataTable.php');

        $this->assertNotFalse($routes);
        $this->assertNotFalse($controller);
        $this->assertNotFalse($createView);
        $this->assertNotFalse($tableView);
        $this->assertNotFalse($dataTable);

        $this->assertStringContainsString('LottoExposureReportController@index', $routes);
        $this->assertStringContainsString('drawOptions', $controller);
        $this->assertStringContainsString('marketOptions', $controller);
        $this->assertStringContainsString('betTypeOptions', $controller);
        $this->assertStringContainsString('filter_draw_id', $createView);
        $this->assertStringContainsString('filter_market_id', $createView);
        $this->assertStringContainsString('filter_bet_type', $createView);
        $this->assertStringContainsString('redrawExposureTable', $tableView);
        $this->assertStringContainsString('resetExposureFilters', $tableView);
        $this->assertStringContainsString("request('draw_id')", $dataTable);
        $this->assertStringContainsString("request('market_id')", $dataTable);
        $this->assertStringContainsString("request('bet_type')", $dataTable);
    }
}
