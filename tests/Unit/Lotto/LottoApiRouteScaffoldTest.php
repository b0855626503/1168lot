<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

class LottoApiRouteScaffoldTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_lotto_service_provider_loads_api_routes(): void
    {
        $content = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Providers/LottoServiceProvider.php');

        $this->assertNotFalse($content);
        $this->assertStringContainsString("loadRoutesFrom(__DIR__ . '/../Routes/api.php')", $content);
    }

    public function test_api_route_file_contains_member_endpoints(): void
    {
        $content = file_get_contents($this->rootPath . '/packages/Gametech/Lotto/src/Routes/api.php');

        $this->assertNotFalse($content);
        $this->assertStringContainsString("Route::middleware(['api', 'authuser:customer'])->prefix('api/lotto')->group(function () {", $content);
        $this->assertStringContainsString("Gametech\\Lotto\\Http\\Controllers\\Api\\DrawController@index", $content);
        $this->assertStringContainsString("Gametech\\Lotto\\Http\\Controllers\\Api\\BetController@store", $content);
        $this->assertStringContainsString("Gametech\\Lotto\\Http\\Controllers\\Api\\PackageController@available", $content);
        $this->assertStringContainsString("Gametech\\Lotto\\Http\\Controllers\\Api\\PackageController@select", $content);
        $this->assertStringContainsString("Gametech\\Lotto\\Http\\Controllers\\Api\\PackageController@selected", $content);
        $this->assertStringContainsString("Gametech\\Lotto\\Http\\Controllers\\Api\\TicketController@cancel", $content);
        $this->assertStringContainsString("prefix('internal/lottery/results')", $content);
        $this->assertStringContainsString("Gametech\\Lotto\\Http\\Controllers\\Api\\InternalResultController@exphuay", $content);
        $this->assertStringContainsString("Gametech\\Lotto\\Http\\Controllers\\Api\\InternalResultController@dowjonesMidnight", $content);
        $this->assertStringContainsString("Gametech\\Lotto\\Http\\Controllers\\Api\\InternalResultController@dowjonesExtra", $content);
    }
}
