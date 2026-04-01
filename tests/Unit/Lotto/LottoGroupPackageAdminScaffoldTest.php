<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

class LottoGroupPackageAdminScaffoldTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_admin_routes_include_group_package_endpoints(): void
    {
        $content = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Routes/admin.php');

        $this->assertNotFalse($content);
        $this->assertStringContainsString("group-packages/list", $content);
        $this->assertStringContainsString("group-packages/create", $content);
        $this->assertStringContainsString("group-packages/update", $content);
        $this->assertStringContainsString("group-package-bet-settings/list", $content);
        $this->assertStringContainsString("group-package-bet-settings/create", $content);
        $this->assertStringContainsString("group-package-bet-settings/update", $content);
    }

    public function test_admin_menu_and_acl_include_group_packages_key(): void
    {
        $menu = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Config/admin-menu.php');
        $acl = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Config/acl.php');

        $this->assertNotFalse($menu);
        $this->assertNotFalse($acl);
        $this->assertStringContainsString("'lotto_settings.group_packages'", $menu);
        $this->assertStringContainsString("'lotto_settings.group_packages'", $acl);
    }

    public function test_market_bet_setting_controller_blocks_payout_override(): void
    {
        $content = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Http/Controllers/Admin/LottoMarketBetSettingController.php');

        $this->assertNotFalse($content);
        $this->assertStringContainsString('DEPRECATED_PAYOUT_OVERRIDE', $content);
    }
}

