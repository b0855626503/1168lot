<?php

namespace Tests\Feature\Admin;

use Gametech\Admin\DataTables\WalletTxnDataTable;
use Gametech\Lotto\Models\WalletTransaction;
use Tests\TestCase;

class WalletTxnDataTableTest extends TestCase
{
    public function test_wallet_transaction_model_exists(): void
    {
        $model = new WalletTransaction;

        $this->assertSame('wallet_transactions', $model->getTable());
    }

    public function test_wallet_txn_route_is_registered(): void
    {
        $routes = app('router')->getRoutes();

        $found = false;
        foreach ($routes as $route) {
            if ($route->getName() === 'admin.wallet_txn.index') {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Route admin.wallet_txn.index is not registered');
    }

    public function test_wallet_txn_datatable_can_be_instantiated(): void
    {
        $dataTable = app(WalletTxnDataTable::class);

        $this->assertInstanceOf(WalletTxnDataTable::class, $dataTable);
    }

    public function test_wallet_txn_route_uses_correct_controller(): void
    {
        $routes = app('router')->getRoutes();
        $action = null;

        foreach ($routes as $route) {
            if ($route->getName() === 'admin.wallet_txn.index') {
                $action = $route->getAction();
                break;
            }
        }

        $this->assertStringContainsString('ReportController@wallet_txn', $action['controller'] ?? '');
    }

    public function test_wallet_txn_menu_entry_in_all_configs(): void
    {
        $menuFiles = [
            base_path('packages/Gametech/Admin/src/Config/admin-menu.php'),
            base_path('packages/Gametech/Admin/src/Config/admin-menu-seamless.php'),
            base_path('packages/Gametech/Admin/src/Config/admin-menu-single.php'),
        ];

        foreach ($menuFiles as $file) {
            $content = file_get_contents($file);
            $this->assertStringContainsString('wallet.wallet_txn', $content, "Missing menu entry in " . basename($file));
            $this->assertStringContainsString('admin.wallet_txn.index', $content, "Missing route in menu " . basename($file));
        }
    }

    public function test_wallet_txn_acl_entry_in_all_configs(): void
    {
        $aclFiles = [
            base_path('packages/Gametech/Admin/src/Config/acl.php'),
            base_path('packages/Gametech/Admin/src/Config/acl-seamless.php'),
            base_path('packages/Gametech/Admin/src/Config/acl-single.php'),
        ];

        foreach ($aclFiles as $file) {
            $content = file_get_contents($file);
            $this->assertStringContainsString('wallet.wallet_txn', $content, "Missing ACL entry in " . basename($file));
            $this->assertStringContainsString('admin.wallet_txn.index', $content, "Missing route in ACL " . basename($file));
        }
    }
}
