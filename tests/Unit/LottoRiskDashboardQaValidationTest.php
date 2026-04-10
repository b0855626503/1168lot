<?php

namespace Tests\Unit;

use App\Services\Dashboard\DashboardWebCodeResolver;
use Gametech\Admin\Services\DashboardService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class LottoRiskDashboardQaValidationTest extends TestCase
{
    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        config()->set('dashboard.lotto_risk.threshold', 1000000);
        Cache::flush();

        Schema::create('configs', function (Blueprint $table): void {
            $table->unsignedInteger('code')->primary();
            $table->string('seamless')->nullable();
            $table->string('freecredit_open')->nullable();
            $table->timestamp('date_create')->nullable();
            $table->timestamp('date_update')->nullable();
        });
        DB::table('configs')->insert([
            'code' => 1,
            'seamless' => 'N',
            'freecredit_open' => 'N',
            'date_create' => now(),
            'date_update' => now(),
        ]);

        Schema::create('lotto_dashboard_risk_aggregates', function (Blueprint $table): void {
            $table->id();
            $table->string('web_code', 64);
            $table->date('summary_date');
            $table->string('bet_type', 64);
            $table->string('number', 32);
            $table->decimal('stake_total', 18, 2)->default(0);
            $table->decimal('exposure_total', 18, 2)->default(0);
            $table->decimal('liability_total', 18, 2)->default(0);
            $table->unsignedInteger('market_count')->default(0);
            $table->unsignedInteger('round_count')->default(0);
            $table->longText('market_ids_json')->nullable();
            $table->longText('round_ids_json')->nullable();
            $table->timestamp('snapshot_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_dashboard_summary_daily', function (Blueprint $table): void {
            $table->id();
            $table->date('summary_date');
            $table->string('web_code', 64);
            $table->decimal('total_sales', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('lotto_dashboard_bet_type_summary_daily', function (Blueprint $table): void {
            $table->id();
            $table->date('summary_date');
            $table->string('bet_type', 64);
            $table->unsignedInteger('item_count')->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->unsignedInteger('unique_players')->default(0);
            $table->timestamps();
        });

        Schema::create('lotto_dashboard_bet_type_number_daily', function (Blueprint $table): void {
            $table->id();
            $table->date('summary_date');
            $table->string('bet_type', 64);
            $table->string('number', 32);
            $table->unsignedInteger('item_count')->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->timestamps();
        });

        $this->seedQaData();
        $this->service = new DashboardService;
    }

    protected function tearDown(): void
    {
        foreach ([
            'lotto_dashboard_bet_type_number_daily',
            'lotto_dashboard_bet_type_summary_daily',
            'lotto_dashboard_summary_daily',
            'lotto_dashboard_risk_aggregates',
            'configs',
        ] as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
            }
        }

        parent::tearDown();
    }

    public function test_qa_validation_critical_cases_for_lotto_risk_dashboard(): void
    {
        $riskSummaryMethod = new ReflectionMethod(DashboardService::class, 'lottoRiskSummaryMetrics');
        $riskSummaryMethod->setAccessible(true);
        $riskSummary = $riskSummaryMethod->invoke($this->service, '2026-04-09', '2026-04-10');

        $this->assertSame('587', $riskSummary['max_risk_number']);
        $this->assertSame(1040000.0, (float) $riskSummary['max_risk_per_number']);
        $this->assertSame(2, $riskSummary['markets']);
        $this->assertSame(2, $riskSummary['rounds']);
        $this->assertTrue((bool) $riskSummary['liability_total_same_as_exposure']);

        $topRiskyMethod = new ReflectionMethod(DashboardService::class, 'lottoTopRiskyNumbersSummary');
        $topRiskyMethod->setAccessible(true);
        $topRisky = $topRiskyMethod->invoke($this->service, '2026-04-09', '2026-04-10', 5);
        $this->assertSame('587', $topRisky[0]['number']);
        $this->assertSame(1040000.0, (float) $topRisky[0]['exposure_total_raw']);

        $betTypeMethod = new ReflectionMethod(DashboardService::class, 'lottoBetTypeInsightsSummary');
        $betTypeMethod->setAccessible(true);
        $betTypeInsights = $betTypeMethod->invoke($this->service, '2026-04-10', '2026-04-10');
        $this->assertSame('111', $betTypeInsights[0]['hottest_number']);
        $this->assertSame('587', $betTypeInsights[0]['max_risk_number']);

        $alertsMethod = new ReflectionMethod(DashboardService::class, 'lottoRiskThresholdAlerts');
        $alertsMethod->setAccessible(true);
        $alerts = $alertsMethod->invoke($this->service, $riskSummary);
        $this->assertCount(1, $alerts);
        $this->assertSame('risk_threshold_exceeded', $alerts[0]['type']);
    }

    private function seedQaData(): void
    {
        $webCode = app(DashboardWebCodeResolver::class)->resolve();

        DB::table('lotto_dashboard_risk_aggregates')->insert([
            [
                'web_code' => $webCode,
                'summary_date' => '2026-04-09',
                'bet_type' => 'top_3',
                'number' => '111',
                'stake_total' => 300,
                'exposure_total' => 300000,
                'liability_total' => 300000,
                'market_count' => 1,
                'round_count' => 1,
                'market_ids_json' => '[1]',
                'round_ids_json' => '[10]',
                'snapshot_at' => '2026-04-09 10:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'web_code' => $webCode,
                'summary_date' => '2026-04-10',
                'bet_type' => 'top_3',
                'number' => '587',
                'stake_total' => 800,
                'exposure_total' => 1040000,
                'liability_total' => 1040000,
                'market_count' => 2,
                'round_count' => 2,
                'market_ids_json' => '[1,2]',
                'round_ids_json' => '[10,20]',
                'snapshot_at' => '2026-04-10 10:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'web_code' => $webCode,
                'summary_date' => '2026-04-10',
                'bet_type' => 'top_3',
                'number' => '111',
                'stake_total' => 1000,
                'exposure_total' => 500000,
                'liability_total' => 500000,
                'market_count' => 1,
                'round_count' => 1,
                'market_ids_json' => '[1]',
                'round_ids_json' => '[10]',
                'snapshot_at' => '2026-04-10 10:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('lotto_dashboard_summary_daily')->insert([
            [
                'summary_date' => '2026-04-09',
                'web_code' => $webCode,
                'total_sales' => 40000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'summary_date' => '2026-04-10',
                'web_code' => $webCode,
                'total_sales' => 50000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('lotto_dashboard_bet_type_summary_daily')->insert([
            'summary_date' => '2026-04-10',
            'bet_type' => 'top_3',
            'item_count' => 120,
            'total_amount' => 50000,
            'unique_players' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_dashboard_bet_type_number_daily')->insert([
            [
                'summary_date' => '2026-04-10',
                'bet_type' => 'top_3',
                'number' => '111',
                'item_count' => 35,
                'total_amount' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'summary_date' => '2026-04-10',
                'bet_type' => 'top_3',
                'number' => '587',
                'item_count' => 30,
                'total_amount' => 800,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
