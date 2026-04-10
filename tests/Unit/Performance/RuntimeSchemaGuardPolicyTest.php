<?php

namespace Tests\Unit\Performance;

use PHPUnit\Framework\TestCase;

class RuntimeSchemaGuardPolicyTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_frontend_lotto_controller_avoids_runtime_schema_guards(): void
    {
        $contents = file_get_contents($this->rootPath.'/packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/LottoController.php');

        $this->assertNotFalse($contents);
        $this->assertStringNotContainsString('Schema::hasTable', $contents);
        $this->assertStringNotContainsString('Schema::hasColumn', $contents);
        $this->assertStringNotContainsString('getColumnListing', $contents);
    }

    public function test_admin_lotto_ticket_controller_avoids_runtime_schema_guards(): void
    {
        $contents = file_get_contents($this->rootPath.'/packages/Gametech/Lotto/src/Http/Controllers/Admin/LottoTicketController.php');

        $this->assertNotFalse($contents);
        $this->assertStringNotContainsString('Schema::hasTable', $contents);
        $this->assertStringNotContainsString('Schema::hasColumn', $contents);
    }

    public function test_dashboard_runtime_paths_use_eager_load_and_assumed_schema_policy(): void
    {
        $controller = file_get_contents($this->rootPath.'/packages/Gametech/Admin/src/Http/Controllers/DashboardController.php');
        $service = file_get_contents($this->rootPath.'/packages/Gametech/Admin/src/Services/DashboardService.php');

        $this->assertNotFalse($controller);
        $this->assertNotFalse($service);
        $this->assertStringContainsString("->with('admin')", $controller);
        $this->assertStringContainsString('ASSUMED_RUNTIME_TABLES', $service);
        $this->assertStringContainsString('ASSUMED_RUNTIME_COLUMNS', $service);
        $this->assertStringContainsString('app()->runningUnitTests()', $service);
    }
}
