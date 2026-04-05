<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

class LottoTicketBadgeLoadcntTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_dashboard_loadcnt_includes_lotto_ticket_count(): void
    {
        $controller = file_get_contents($this->rootPath . '/packages/Gametech/Admin/src/Http/Controllers/DashboardController.php');

        $this->assertNotFalse($controller);
        $this->assertStringContainsString("\$result['lotto_tickets'] = \$this->countActiveLottoTickets();", $controller);
        $this->assertStringContainsString("private function countActiveLottoTickets(): int", $controller);
        $this->assertStringContainsString("Schema::hasTable('lotto_tickets')", $controller);
        $this->assertStringContainsString("->where('status', 'active')", $controller);
    }

    public function test_lotto_ticket_badge_is_no_longer_fetched_from_ticket_datatable_xhr(): void
    {
        $ticketTableView = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/tickets/table.blade.php');

        $this->assertNotFalse($ticketTableView);
        $this->assertStringNotContainsString('xhr.dt.lottoTicketsBadge', $ticketTableView);
        $this->assertStringNotContainsString('menuBadgeKey', $ticketTableView);
    }

    public function test_loadcnt_consumers_update_lotto_ticket_badge_from_shared_payload(): void
    {
        $files = [
            '/packages/Gametech/Admin/src/Resources/views/layouts/datatables_js.blade.php',
            '/packages/Gametech/Admin/src/Resources/views/module/dashboard/index.blade.php',
            '/packages/Gametech/Admin/src/Resources/views/module/jobs/index.blade.php',
            '/packages/Gametech/Admin/src/Resources/views/module/fix/index.blade.php',
            '/packages/Gametech/Admin/src/Resources/views/module/setting/addedit.blade.php',
            '/packages/Gametech/Admin/src/Resources/views/module/setting/table.blade.php',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($this->rootPath . $file);

            $this->assertNotFalse($contents, $file);
            $this->assertStringContainsString('lotto_tickets', $contents, $file);
        }
    }

    public function test_lotto_datatable_pages_still_use_shared_loadcnt_flow(): void
    {
        $datatableJs = file_get_contents($this->rootPath . '/packages/Gametech/Admin/src/Resources/views/layouts/datatables_js.blade.php');

        $this->assertNotFalse($datatableJs);
        $this->assertStringContainsString('this.loadCnt();', $datatableJs);
        $this->assertStringContainsString('async loadCnt()', $datatableJs);
        $this->assertStringNotContainsString("request()->routeIs('admin.lotto.*')", $datatableJs);
    }

    public function test_lotto_custom_admin_pages_include_shared_loadcnt_partial(): void
    {
        $files = [
            '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/switches/index.blade.php',
            '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/group_packages/index.blade.php',
            '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/rate_plans/index.blade.php',
            '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/bet_limits/index.blade.php',
            '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/reports/results_by_date.blade.php',
            '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/profit_loss_forecast_report/index.blade.php',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($this->rootPath . $file);

            $this->assertNotFalse($contents, $file);
            $this->assertStringContainsString("@include('admin::layouts.loadcnt_js')", $contents, $file);
        }
    }
}
