<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

class LottoGlobalConfigMigrationGuardTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_admin_routes_no_longer_expose_member_rate_plan_endpoints(): void
    {
        $routes = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Routes/admin.php');

        $this->assertNotFalse($routes);
        $this->assertStringNotContainsString('member-rate-plans', $routes);
        $this->assertStringNotContainsString('MemberLottoSettingController', $routes);
        $this->assertStringContainsString('rate-plans', $routes);
        $this->assertStringContainsString('LottoRatePlanController@index', $routes);
    }

    public function test_admin_menu_no_longer_contains_member_rate_plans_item(): void
    {
        $menu = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Config/admin-menu.php');

        $this->assertNotFalse($menu);
        $this->assertStringNotContainsString("'lotto.member_rate_plans'", $menu);
        $this->assertStringNotContainsString('admin.lotto.member_rate_plans.index', $menu);
        $this->assertStringContainsString('admin.lotto.rate_plans.index', $menu);
    }
}
