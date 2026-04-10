<?php

namespace Tests\Unit\Admin;

use App\Services\Dashboard\DashboardWebCodeResolver;
use Gametech\Admin\Services\DashboardService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class DashboardServiceLottoDashboardTest extends TestCase
{
    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
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

        $this->service = new DashboardService;
    }

    protected function tearDown(): void
    {
        $this->dropTableIfExists('lotto_tickets');
        $this->dropTableIfExists('lotto_draws');
        $this->dropTableIfExists('lotto_markets');
        $this->dropTableIfExists('lotto_groups');
        $this->dropTableIfExists('members');
        $this->dropTableIfExists('configs');
        $this->dropTableIfExists('dashboard_summary_daily');
        $this->dropTableIfExists('lotto_dashboard_bet_type_number_daily');
        $this->dropTableIfExists('lotto_dashboard_bet_type_summary_daily');
        $this->dropTableIfExists('lotto_dashboard_risk_aggregates');
        $this->dropTableIfExists('lotto_dashboard_risk_snapshot');
        $this->dropTableIfExists('lotto_dashboard_summary_daily');

        parent::tearDown();
    }

    public function test_recent_lotto_bets_activity_prefers_member_user_name(): void
    {
        Schema::create('members', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->nullable();
            $table->unsignedBigInteger('code')->primary();
            $table->string('user_name')->nullable();
        });
        Schema::create('lotto_groups', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
        });
        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('group_id');
            $table->string('name');
        });
        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
        });
        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('draw_id');
            $table->string('status')->nullable();
            $table->string('bet_type_summary')->nullable();
            $table->decimal('total_net_amount', 12, 2)->default(0);
            $table->decimal('total_win_amount', 12, 2)->default(0);
            $table->timestamp('created_at')->nullable();
        });

        DB::table('members')->insert([
            'id' => 999,
            'code' => 52,
            'user_name' => 'member52',
        ]);
        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'หวยรายวัน',
        ]);
        DB::table('lotto_markets')->insert([
            'id' => 11,
            'group_id' => 1,
            'name' => 'หวยรัฐบาล',
        ]);
        DB::table('lotto_draws')->insert([
            'id' => 101,
            'market_id' => 11,
            'draw_date' => '2026-04-05',
        ]);
        DB::table('lotto_tickets')->insert([
            'id' => 1001,
            'member_id' => 52,
            'draw_id' => 101,
            'status' => 'active',
            'bet_type_summary' => '2 ตัวบน',
            'total_net_amount' => 200,
            'total_win_amount' => 0,
            'created_at' => '2026-04-05 10:15:00',
        ]);

        $method = new ReflectionMethod(DashboardService::class, 'getRecentLottoBetsActivity');
        $method->setAccessible(true);
        $rows = $method->invoke($this->service, 20, null);

        $this->assertCount(1, $rows);
        $this->assertSame('member52', $rows[0]['member_username']);
        $this->assertSame('52', $rows[0]['member_code']);
    }

    public function test_recent_lotto_bets_activity_respects_selected_date_range(): void
    {
        Schema::create('members', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->nullable();
            $table->unsignedBigInteger('code')->primary();
            $table->string('user_name')->nullable();
        });
        Schema::create('lotto_groups', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
        });
        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('group_id');
            $table->string('name');
        });
        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
        });
        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('draw_id');
            $table->string('status')->nullable();
            $table->string('bet_type_summary')->nullable();
            $table->decimal('total_net_amount', 12, 2)->default(0);
            $table->decimal('total_win_amount', 12, 2)->default(0);
            $table->timestamp('created_at')->nullable();
        });

        DB::table('members')->insert([
            'id' => 999,
            'code' => 52,
            'user_name' => 'member52',
        ]);
        DB::table('lotto_groups')->insert([
            'id' => 1,
            'name' => 'หวยรายวัน',
        ]);
        DB::table('lotto_markets')->insert([
            'id' => 11,
            'group_id' => 1,
            'name' => 'หวยรัฐบาล',
        ]);
        DB::table('lotto_draws')->insert([
            'id' => 101,
            'market_id' => 11,
            'draw_date' => '2026-04-09',
        ]);
        DB::table('lotto_tickets')->insert([
            [
                'id' => 1001,
                'member_id' => 52,
                'draw_id' => 101,
                'status' => 'active',
                'bet_type_summary' => '3 ตัวบน',
                'total_net_amount' => 120,
                'total_win_amount' => 0,
                'created_at' => '2026-04-09 10:15:00',
            ],
            [
                'id' => 1002,
                'member_id' => 52,
                'draw_id' => 101,
                'status' => 'active',
                'bet_type_summary' => '2 ตัวบน',
                'total_net_amount' => 220,
                'total_win_amount' => 0,
                'created_at' => '2026-04-10 09:20:00',
            ],
        ]);

        $method = new ReflectionMethod(DashboardService::class, 'getRecentLottoBetsActivity');
        $method->setAccessible(true);
        $rows = $method->invoke($this->service, 20, null, '2026-04-09', '2026-04-09');

        $this->assertCount(1, $rows);
        $this->assertSame(1001, $rows[0]['ticket_id']);
        $this->assertSame('2026-04-09 10:15', $rows[0]['bet_at']);
    }

    public function test_summary_uses_deposit_minus_withdraw_only_for_net_balance(): void
    {
        $migration = require base_path('database/migrations/2026_03_09_120000_create_dashboard_summary_daily_table.php');
        $migration->up();

        Schema::table('dashboard_summary_daily', function (Blueprint $table): void {
            $table->decimal('withdraw_main_total_amount', 18, 2)->default(0);
            $table->unsignedInteger('withdraw_main_total_count')->default(0);
            $table->unsignedInteger('withdraw_main_total_users')->default(0);
            $table->decimal('withdraw_main_pending_amount', 18, 2)->default(0);
            $table->unsignedInteger('withdraw_main_pending_count')->default(0);
            $table->decimal('withdraw_free_total_amount', 18, 2)->default(0);
            $table->unsignedInteger('withdraw_free_total_count')->default(0);
            $table->unsignedInteger('withdraw_free_total_users')->default(0);
            $table->decimal('withdraw_free_pending_amount', 18, 2)->default(0);
            $table->unsignedInteger('withdraw_free_pending_count')->default(0);
            $table->decimal('lotto_sales_cash', 18, 2)->default(0);
            $table->decimal('lotto_payout_cash', 18, 2)->default(0);
            $table->decimal('lotto_refund_cash', 18, 2)->default(0);
            $table->decimal('lotto_net_cash', 18, 2)->default(0);
        });

        $webCode = app(DashboardWebCodeResolver::class)->resolve();

        DB::table('dashboard_summary_daily')->insert([
            [
                'summary_date' => '2026-04-05',
                'web_code' => $webCode,
                'deposit_success_amount' => 100,
                'withdraw_total_amount' => 40,
                'lotto_net_cash' => 25,
                'net_amount' => 85,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'summary_date' => '2026-04-04',
                'web_code' => $webCode,
                'deposit_success_amount' => 200,
                'withdraw_total_amount' => 100,
                'lotto_net_cash' => 50,
                'net_amount' => 150,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $summaryWarmCache = new \ReflectionProperty(DashboardService::class, 'summaryWarmCache');
        $summaryWarmCache->setAccessible(true);
        $summaryWarmCache->setValue($this->service, [
            $webCode.'|2026-04-05|2026-04-05' => true,
            $webCode.'|2026-04-04|2026-04-04' => true,
        ]);

        $summary = $this->service->getSummary([
            'date_start' => '2026-04-05',
            'date_end' => '2026-04-05',
        ]);

        $this->assertSame(60.0, (float) $summary['net']['amount_raw']);
        $this->assertSame(-40.0, (float) $summary['net']['change_pct']);
        $this->assertSame(25.0, (float) $summary['lotto']['net_cash_raw']);
    }

    public function test_dashboard_view_describes_net_without_lotto_and_shows_thai_risk_copy(): void
    {
        $contents = file_get_contents(base_path('packages/Gametech/Admin/src/Resources/views/module/dashboard/index.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('ฝากสำเร็จ - ถอนสำเร็จ', $contents);
        $this->assertStringNotContainsString('ฝากสำเร็จ - ถอนสำเร็จ + Lotto Net', $contents);
        $this->assertStringContainsString('Lotto Risk (ความเสี่ยงหวย)', $contents);
        $this->assertStringContainsString('เลขเสี่ยงสูงสุด (Top Risky Numbers)', $contents);
        $this->assertStringContainsString('formatLottoRiskBetType(row.bet_type)', $contents);
        $this->assertStringContainsString("top_3: '3 ตัวบน'", $contents);
        $this->assertStringContainsString("total_sales: '0.00', total_sales_raw: 0", $contents);
        $this->assertStringContainsString("total_payout: '0.00', total_payout_raw: 0", $contents);
        $this->assertStringContainsString("exposure_total: '0.00', exposure_total_raw: 0", $contents);
        $this->assertStringContainsString("max_risk_per_number: '0.00', max_risk_per_number_raw: 0", $contents);
        $this->assertStringContainsString("risk_delta: '0.00', risk_delta_raw: 0", $contents);
        $this->assertStringContainsString("sales_delta: '0.00', sales_delta_raw: 0", $contents);
        $this->assertStringContainsString('ยอดเสี่ยงสูงสุดต่อเลข', $contents);
        $this->assertStringContainsString('member_username', $contents);

        $projector = file_get_contents(base_path('app/Services/Dashboard/DashboardSummaryProjector.php'));
        $this->assertNotFalse($projector);
        $this->assertStringContainsString("(float) \$deposit['deposit_success_amount']", $projector);
        $this->assertStringContainsString("- (float) \$withdraw['withdraw_total_amount']", $projector);
        $this->assertStringNotContainsString("+ (float) \$lottoCash['lotto_net_cash']", $projector);
    }

    public function test_lotto_risk_summary_uses_latest_aggregate_layer_and_validates_587_case(): void
    {
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
            $table->timestamp('snapshot_at');
            $table->timestamps();
        });

        $webCode = app(DashboardWebCodeResolver::class)->resolve();

        DB::table('lotto_dashboard_risk_aggregates')->insert([
            [
                'web_code' => $webCode,
                'summary_date' => '2026-04-10',
                'bet_type' => 'top_2',
                'number' => '587',
                'snapshot_at' => '2026-04-10 09:00:00',
                'stake_total' => 800,
                'exposure_total' => 1040000,
                'liability_total' => 1040000,
                'market_count' => 2,
                'round_count' => 2,
                'market_ids_json' => '[1,2]',
                'round_ids_json' => '[10,20]',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'web_code' => $webCode,
                'summary_date' => '2026-04-10',
                'bet_type' => 'top_2',
                'number' => '22',
                'snapshot_at' => '2026-04-10 09:00:00',
                'stake_total' => 200,
                'exposure_total' => 2000,
                'liability_total' => 2000,
                'market_count' => 1,
                'round_count' => 1,
                'market_ids_json' => '[1]',
                'round_ids_json' => '[10]',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'web_code' => $webCode,
                'summary_date' => '2026-04-10',
                'bet_type' => 'bottom_2',
                'number' => '33',
                'snapshot_at' => '2026-04-10 10:00:00',
                'stake_total' => 400,
                'exposure_total' => 4000,
                'liability_total' => 4000,
                'market_count' => 1,
                'round_count' => 1,
                'market_ids_json' => '[2]',
                'round_ids_json' => '[20]',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $method = new ReflectionMethod(DashboardService::class, 'lottoRiskSummaryMetrics');
        $method->setAccessible(true);
        $summary = $method->invoke($this->service, '2026-04-10', '2026-04-10');

        $this->assertSame(3, $summary['numbers']);
        $this->assertSame(2, $summary['markets']);
        $this->assertSame(2, $summary['rounds']);
        $this->assertSame(1046000.0, (float) $summary['exposure_total']);
        $this->assertSame(1046000.0, (float) $summary['liability_total']);
        $this->assertSame(1040000.0, (float) $summary['liability_max']);
        $this->assertSame(1040000.0, (float) $summary['max_risk_per_number']);
        $this->assertSame('587', $summary['max_risk_number']);
        $this->assertTrue((bool) $summary['liability_total_deprecated']);
        $this->assertTrue((bool) $summary['liability_total_same_as_exposure']);
        $this->assertSame('2026-04-10 10:00:00', $summary['last_snapshot_at']);
    }

    public function test_lotto_top_risky_numbers_sorted_by_exposure_and_validates_587_case(): void
    {
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
            $table->timestamp('snapshot_at');
            $table->timestamps();
        });

        $webCode = app(DashboardWebCodeResolver::class)->resolve();

        DB::table('lotto_dashboard_risk_aggregates')->insert([
            [
                'web_code' => $webCode,
                'summary_date' => '2026-04-10',
                'bet_type' => 'top_3',
                'number' => '587',
                'stake_total' => 800,
                'exposure_total' => 1040000,
                'liability_total' => 1040000,
                'market_count' => 4,
                'round_count' => 4,
                'snapshot_at' => '2026-04-10 10:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'web_code' => $webCode,
                'summary_date' => '2026-04-10',
                'bet_type' => 'top_2',
                'number' => '111',
                'stake_total' => 600,
                'exposure_total' => 800000,
                'liability_total' => 800000,
                'market_count' => 2,
                'round_count' => 2,
                'snapshot_at' => '2026-04-10 10:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'web_code' => $webCode,
                'summary_date' => '2026-04-10',
                'bet_type' => 'bottom_2',
                'number' => '222',
                'stake_total' => 500,
                'exposure_total' => 350000,
                'liability_total' => 350000,
                'market_count' => 1,
                'round_count' => 1,
                'snapshot_at' => '2026-04-10 10:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $method = new ReflectionMethod(DashboardService::class, 'lottoTopRiskyNumbersSummary');
        $method->setAccessible(true);
        $rows = $method->invoke($this->service, '2026-04-10', '2026-04-10', 2);

        $this->assertCount(2, $rows);
        $this->assertSame('587', $rows[0]['number']);
        $this->assertSame('top_3', $rows[0]['bet_type']);
        $this->assertSame(1040000.0, (float) $rows[0]['exposure_total_raw']);
        $this->assertSame(800000.0, (float) $rows[1]['exposure_total_raw']);
    }

    public function test_lotto_bet_type_insights_separate_hottest_and_riskiest_numbers(): void
    {
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
            $table->timestamp('snapshot_at');
            $table->timestamps();
        });

        $webCode = app(DashboardWebCodeResolver::class)->resolve();

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

        DB::table('lotto_dashboard_risk_aggregates')->insert([
            [
                'web_code' => $webCode,
                'summary_date' => '2026-04-10',
                'bet_type' => 'top_3',
                'number' => '111',
                'stake_total' => 1000,
                'exposure_total' => 500000,
                'liability_total' => 500000,
                'market_count' => 2,
                'round_count' => 2,
                'snapshot_at' => '2026-04-10 10:00:00',
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
                'market_count' => 4,
                'round_count' => 4,
                'snapshot_at' => '2026-04-10 10:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $method = new ReflectionMethod(DashboardService::class, 'lottoBetTypeInsightsSummary');
        $method->setAccessible(true);
        $rows = $method->invoke($this->service, '2026-04-10', '2026-04-10');

        $this->assertCount(1, $rows);
        $this->assertSame('111', $rows[0]['hottest_number']);
        $this->assertSame(1000.0, (float) $rows[0]['hottest_number_amount_raw']);
        $this->assertSame('587', $rows[0]['max_risk_number']);
        $this->assertSame(1040000.0, (float) $rows[0]['max_risk_value_raw']);
        $this->assertSame(1540000.0, (float) $rows[0]['risk_exposure_total_raw']);
    }

    public function test_lotto_risk_threshold_alerts_only_when_threshold_exceeded(): void
    {
        config()->set('dashboard.lotto_risk.threshold', 1000000);

        $method = new ReflectionMethod(DashboardService::class, 'lottoRiskThresholdAlerts');
        $method->setAccessible(true);

        $alerts = $method->invoke($this->service, [
            'max_risk_number' => '587',
            'max_risk_per_number' => 1040000,
        ]);
        $this->assertCount(1, $alerts);
        $this->assertSame('risk_threshold_exceeded', $alerts[0]['type']);
        $this->assertSame('high', $alerts[0]['severity']);
        $this->assertSame('587', $alerts[0]['number']);

        $alertsNotExceeded = $method->invoke($this->service, [
            'max_risk_number' => '111',
            'max_risk_per_number' => 999999,
        ]);
        $this->assertSame([], $alertsNotExceeded);
    }

    public function test_lotto_risk_trend_compares_latest_and_previous_snapshot(): void
    {
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
            $table->timestamp('snapshot_at');
            $table->timestamps();
        });
        Schema::create('lotto_dashboard_summary_daily', function (Blueprint $table): void {
            $table->id();
            $table->date('summary_date');
            $table->string('web_code', 64);
            $table->decimal('total_sales', 18, 2)->default(0);
            $table->timestamps();
        });

        $webCode = app(DashboardWebCodeResolver::class)->resolve();
        DB::table('lotto_dashboard_risk_aggregates')->insert([
            [
                'web_code' => $webCode,
                'summary_date' => '2026-04-09',
                'bet_type' => 'top_3',
                'number' => '587',
                'stake_total' => 700,
                'exposure_total' => 900000,
                'liability_total' => 900000,
                'market_count' => 3,
                'round_count' => 3,
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
                'market_count' => 4,
                'round_count' => 4,
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

        $method = new ReflectionMethod(DashboardService::class, 'lottoRiskTrendSummary');
        $method->setAccessible(true);
        $trend = $method->invoke($this->service, '2026-04-09', '2026-04-10');

        $this->assertSame('2026-04-10', $trend['current_date']);
        $this->assertSame('2026-04-09', $trend['previous_date']);
        $this->assertSame(140000.0, (float) $trend['risk_delta_raw']);
        $this->assertSame('up', $trend['risk_direction']);
        $this->assertSame(10000.0, (float) $trend['sales_delta_raw']);
        $this->assertSame('up', $trend['sales_direction']);
    }

    private function dropTableIfExists(string $table): void
    {
        if (Schema::hasTable($table)) {
            Schema::drop($table);
        }
    }
}
