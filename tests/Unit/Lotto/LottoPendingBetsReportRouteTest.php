<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

class LottoPendingBetsReportRouteTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_pending_bets_report_uses_real_ticket_controller_routes(): void
    {
        $routes = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Routes/admin.php');
        $ticketController = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Http/Controllers/Admin/LottoTicketController.php');
        $ticketAddEditView = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/tickets/addedit.blade.php');
        $ticketTableView = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/tickets/table.blade.php');

        $this->assertNotFalse($routes);
        $this->assertNotFalse($ticketController);
        $this->assertNotFalse($ticketAddEditView);
        $this->assertNotFalse($ticketTableView);

        $this->assertStringContainsString("Route::get('reports/pending-bets', 'Gametech\\\\Lotto\\\\Http\\\\Controllers\\\\Admin\\\\LottoTicketController@index')", $routes);
        $this->assertStringContainsString("Route::post('reports/pending-bets/loaddata', 'Gametech\\\\Lotto\\\\Http\\\\Controllers\\\\Admin\\\\LottoTicketController@loadData')", $routes);
        $this->assertStringContainsString("'loadDataRouteName' => \$this->_config['load_data_route'] ?? 'admin.lotto.tickets.loaddata'", $ticketController);
        $this->assertStringContainsString("route(\$loadDataRouteName ?? 'admin.lotto.tickets.loaddata')", $ticketAddEditView);
        $this->assertStringNotContainsString('menuBadgeKey', $ticketController);
        $this->assertStringNotContainsString('xhr.dt.lottoTicketsBadge', $ticketTableView);
    }
}
