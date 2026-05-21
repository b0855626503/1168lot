<?php

namespace Tests\Unit\Performance;

use Gametech\Admin\Services\DashboardService;
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

    public function test_dashboard_assumed_schema_covers_existing_runtime_columns(): void
    {
        $reflection = new \ReflectionClass(DashboardService::class);
        $assumedColumns = $reflection->getConstant('ASSUMED_RUNTIME_COLUMNS');
        $this->assertIsArray($assumedColumns);

        // Columns that hasColumn() guards in DashboardService and which DO exist
        // in production. Missing whitelist entries here mean shouldAssumeCurrentSchema()
        // silently returns false at runtime, disabling the corresponding filter or
        // code path (see lotto_markets.result_mode regression where the Recent Lotto
        // Bets dropdown stopped filtering yeekee/normal markets).
        $expected = [
            'lotto_markets' => ['result_mode'],
            'lotto_dashboard_risk_snapshot' => ['round_id', 'market_id', 'bet_type', 'number'],
        ];

        $missing = [];
        foreach ($expected as $table => $columns) {
            $whitelisted = $assumedColumns[$table] ?? [];
            $isWildcard = in_array('*', $whitelisted, true);
            foreach ($columns as $column) {
                if (! $isWildcard && ! in_array($column, $whitelisted, true)) {
                    $missing[] = $table.'.'.$column;
                }
            }
        }

        $this->assertSame(
            [],
            $missing,
            "Production schema columns must be in ASSUMED_RUNTIME_COLUMNS so hasColumn() does not silently disable filters. Missing:\n - "
            .implode("\n - ", $missing)
        );
    }
}
