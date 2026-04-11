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
        $this->dropTableIfExists('lotto_number_exposures');
        $this->dropTableIfExists('lotto_draw_bet_settings');
        $this->dropTableIfExists('lotto_ticket_items');

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
        $this->assertStringContainsString('เลขเสี่ยง (Top 10 Risky Numbers)', $contents);
        $this->assertStringContainsString("lottoRiskTab: 'today'", $contents);
        $this->assertStringContainsString('ยอดเสี่ยงวันนี้ หรือช่วงวันที่ที่เลือก', $contents);
        $this->assertStringContainsString('ยอดเสี่ยงสูงสุดแบบเดิม', $contents);
        $this->assertStringContainsString("lottoRiskTab === 'today'", $contents);
        $this->assertStringContainsString("lottoRiskTab === 'highest'", $contents);
        $this->assertStringContainsString('วันนี้', $contents);
        $this->assertStringContainsString('เสี่ยงสูงสุด', $contents);
        $this->assertStringContainsString('activeLottoRiskRows()', $contents);
        $this->assertStringContainsString('summary.lotto_top_risky_numbers', $contents);
        $this->assertStringContainsString('formatLottoRiskBetType(row.bet_type)', $contents);
        $this->assertStringContainsString("top_3: '3 ตัวบน'", $contents);
        $this->assertStringContainsString('จำนวนงวด (เสี่ยง)', $contents);
        $this->assertStringContainsString("openTopRiskyDetail('markets', row)", $contents);
        $this->assertStringContainsString("openTopRiskyDetail('rounds', row)", $contents);
        $this->assertStringContainsString('id="top-risky-detail-modal"', $contents);
        $this->assertStringContainsString('รหัสงวด', $contents);
        $this->assertStringContainsString('topRiskyRoundResultTime(round)', $contents);
        $this->assertStringContainsString('ผู้เล่นเสี่ยงสูงสุด (Top Risk Users)', $contents);
        $this->assertStringContainsString('summary.lotto_top_risk_users', $contents);
        $this->assertStringContainsString('ยอดจ่ายถ้าถูกทั้งหมด', $contents);
        $this->assertStringContainsString('กำไร/ขาดทุนสุทธิ', $contents);
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
        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('result_at')->nullable();
            $table->text('result_number')->nullable();
            $table->timestamp('result_applied_at')->nullable();
            $table->timestamp('close_at')->nullable();
            $table->timestamp('open_at')->nullable();
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

        DB::table('lotto_markets')->insert([
            ['id' => 1, 'name' => 'หวยรัฐบาล'],
            ['id' => 2, 'name' => 'หวยลาว'],
            ['id' => 3, 'name' => 'หวยฮานอย'],
            ['id' => 4, 'name' => 'หวยหุ้น'],
        ]);

        DB::table('lotto_draws')->insert([
            [
                'id' => 10,
                'market_id' => 1,
                'draw_date' => '2026-04-10',
                'status' => 'resulted',
                'result_at' => '2026-04-10 12:00:00',
                'result_number' => '{"top_3":"587","top_2":"87","bottom_2":"12"}',
                'result_applied_at' => '2026-04-10 12:00:01',
                'close_at' => '2026-04-10 11:55:00',
                'open_at' => '2026-04-10 00:00:00',
            ],
            [
                'id' => 11,
                'market_id' => 2,
                'draw_date' => '2026-04-10',
                'status' => 'closed',
                'result_at' => null,
                'result_number' => null,
                'result_applied_at' => null,
                'close_at' => '2026-04-10 13:00:00',
                'open_at' => '2026-04-10 01:00:00',
            ],
            [
                'id' => 12,
                'market_id' => 3,
                'draw_date' => '2026-04-10',
                'status' => 'open',
                'result_at' => null,
                'result_number' => null,
                'result_applied_at' => null,
                'close_at' => '2026-04-10 14:00:00',
                'open_at' => '2026-04-10 02:00:00',
            ],
            [
                'id' => 13,
                'market_id' => 4,
                'draw_date' => '2026-04-10',
                'status' => 'draft',
                'result_at' => null,
                'result_number' => null,
                'result_applied_at' => null,
                'close_at' => null,
                'open_at' => null,
            ],
            [
                'id' => 14,
                'market_id' => 1,
                'draw_date' => '2026-04-10',
                'status' => 'resulted',
                'result_at' => '2026-04-10 12:30:00',
                'result_number' => '{"top_2":"11","bottom_2":"22"}',
                'result_applied_at' => '2026-04-10 12:30:01',
                'close_at' => '2026-04-10 12:20:00',
                'open_at' => '2026-04-10 03:00:00',
            ],
            [
                'id' => 15,
                'market_id' => 2,
                'draw_date' => '2026-04-10',
                'status' => 'closed',
                'result_at' => null,
                'result_number' => null,
                'result_applied_at' => null,
                'close_at' => '2026-04-10 13:30:00',
                'open_at' => '2026-04-10 04:00:00',
            ],
            [
                'id' => 16,
                'market_id' => 3,
                'draw_date' => '2026-04-10',
                'status' => 'open',
                'result_at' => null,
                'result_number' => null,
                'result_applied_at' => null,
                'close_at' => '2026-04-10 15:00:00',
                'open_at' => '2026-04-10 05:00:00',
            ],
        ]);

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
                'market_ids_json' => '[1,2,3,4]',
                'round_ids_json' => '[10,11,12,13]',
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
                'market_ids_json' => '[1,2]',
                'round_ids_json' => '[14,15]',
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
                'market_ids_json' => '[3]',
                'round_ids_json' => '[16]',
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
        $this->assertCount(4, $rows[0]['markets']);
        $this->assertCount(4, $rows[0]['rounds']);
        $this->assertSame('หวยรัฐบาล', $rows[0]['markets'][0]['name']);
        $this->assertSame('resulted', $rows[0]['rounds'][0]['status']);
        $this->assertSame('2026-04-10 12:00:00', $rows[0]['rounds'][0]['result_at']);
        $this->assertSame('587', $rows[0]['rounds'][0]['result_number_display']);
        $this->assertSame('2026-04-10 11:55:00', $rows[0]['rounds'][0]['close_at']);
        $this->assertFalse($rows[0]['rounds'][0]['actual_settlement_pending']);
        $this->assertSame(800000.0, (float) $rows[1]['exposure_total_raw']);
    }

    public function test_top_risky_numbers_summary_fallbacks_to_number_exposures_when_snapshot_missing(): void
    {
        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
        });

        Schema::create('lotto_number_exposures', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('draw_id');
            $table->string('bet_type', 64);
            $table->string('number', 32);
            $table->decimal('sold_amount', 18, 2)->default(0);
        });

        Schema::create('lotto_draw_bet_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('draw_id');
            $table->string('bet_type', 64);
            $table->decimal('payout', 18, 2)->default(0);
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

        DB::table('lotto_markets')->insert([
            'id' => 11,
            'name' => 'หวยทดลอง',
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 501,
            'market_id' => 11,
            'draw_date' => '2026-04-09',
        ]);

        DB::table('lotto_number_exposures')->insert([
            'draw_id' => 501,
            'bet_type' => 'top_3',
            'number' => '123',
            'sold_amount' => 100,
        ]);

        DB::table('lotto_draw_bet_settings')->insert([
            'draw_id' => 501,
            'bet_type' => 'top_3',
            'payout' => 3000,
        ]);

        DB::table('lotto_dashboard_risk_aggregates')->insert([
            'web_code' => $webCode,
            'summary_date' => '2026-04-09',
            'bet_type' => 'top_3',
            'number' => '123',
            'stake_total' => 100,
            'exposure_total' => 300000,
            'liability_total' => 300000,
            'market_count' => 1,
            'round_count' => 1,
            'market_ids_json' => '[11]',
            'round_ids_json' => '[501]',
            'snapshot_at' => '2026-04-09 23:59:59',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $method = new ReflectionMethod(DashboardService::class, 'lottoTopRiskyNumbersSummary');
        $method->setAccessible(true);
        $rows = $method->invoke($this->service, '2026-04-09', '2026-04-09', 5);

        $this->assertCount(1, $rows);
        $this->assertCount(1, $rows[0]['markets']);
        $this->assertCount(1, $rows[0]['rounds']);
        $this->assertSame(100.0, (float) $rows[0]['markets'][0]['total_stake_raw']);
        $this->assertSame(300000.0, (float) $rows[0]['markets'][0]['total_risk_raw']);
        $this->assertSame(300000.0, (float) $rows[0]['rounds'][0]['total_risk_raw']);
        $this->assertSame(300000.0, (float) $rows[0]['rounds'][0]['potential_payout_raw']);
    }

    public function test_highest_risky_numbers_summary_uses_peak_snapshot_per_number(): void
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
                'number' => '123',
                'stake_total' => 100,
                'exposure_total' => 100000,
                'liability_total' => 100000,
                'market_count' => 1,
                'round_count' => 1,
                'market_ids_json' => '[1]',
                'round_ids_json' => '[11]',
                'snapshot_at' => '2026-04-10 10:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'web_code' => $webCode,
                'summary_date' => '2026-04-11',
                'bet_type' => 'top_3',
                'number' => '123',
                'stake_total' => 150,
                'exposure_total' => 150000,
                'liability_total' => 150000,
                'market_count' => 1,
                'round_count' => 1,
                'market_ids_json' => '[2]',
                'round_ids_json' => '[22]',
                'snapshot_at' => '2026-04-11 11:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'web_code' => $webCode,
                'summary_date' => '2026-04-11',
                'bet_type' => 'top_3',
                'number' => '456',
                'stake_total' => 120,
                'exposure_total' => 120000,
                'liability_total' => 120000,
                'market_count' => 1,
                'round_count' => 1,
                'market_ids_json' => '[3]',
                'round_ids_json' => '[33]',
                'snapshot_at' => '2026-04-11 11:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $rangeMethod = new ReflectionMethod(DashboardService::class, 'lottoTopRiskyNumbersSummary');
        $rangeMethod->setAccessible(true);
        $rangeRows = $rangeMethod->invoke($this->service, '2026-04-10', '2026-04-11', 10);

        $highestMethod = new ReflectionMethod(DashboardService::class, 'lottoHighestRiskNumbersSummary');
        $highestMethod->setAccessible(true);
        $highestRows = $highestMethod->invoke($this->service, '2026-04-10', '2026-04-11', 10);

        $this->assertCount(2, $rangeRows);
        $this->assertCount(2, $highestRows);
        $this->assertSame('123', $rangeRows[0]['number']);
        $this->assertSame(250000.0, (float) $rangeRows[0]['exposure_total_raw']);
        $this->assertSame(2, (int) $rangeRows[0]['round_count']);
        $this->assertSame('123', $highestRows[0]['number']);
        $this->assertSame(150000.0, (float) $highestRows[0]['exposure_total_raw']);
        $this->assertSame(1, (int) $highestRows[0]['round_count']);
    }

    public function test_top_risky_numbers_summary_formats_no_result_payload_as_label(): void
    {
        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
            $table->string('status')->nullable();
            $table->text('result_number')->nullable();
            $table->timestamp('result_at')->nullable();
            $table->timestamp('result_applied_at')->nullable();
            $table->timestamp('close_at')->nullable();
            $table->timestamp('open_at')->nullable();
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

        DB::table('lotto_markets')->insert([
            'id' => 1,
            'name' => 'หวยฮานอย',
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 676,
            'market_id' => 1,
            'draw_date' => '2026-04-06',
            'status' => 'resulted',
            'result_number' => '{"no_result":true,"status":"no_result","label":"งดออกผล","no_result_reason":"งดออกผล","manual_cancelled_all_tickets":true}',
            'result_at' => '2026-04-06 14:13:02',
            'result_applied_at' => '2026-04-06 14:13:03',
            'close_at' => '2026-04-06 13:30:00',
            'open_at' => '2026-04-06 00:00:00',
        ]);

        DB::table('lotto_dashboard_risk_aggregates')->insert([
            'web_code' => $webCode,
            'summary_date' => '2026-04-06',
            'bet_type' => 'top_3',
            'number' => '123',
            'stake_total' => 100,
            'exposure_total' => 80000,
            'liability_total' => 80000,
            'market_count' => 1,
            'round_count' => 1,
            'market_ids_json' => '[1]',
            'round_ids_json' => '[676]',
            'snapshot_at' => '2026-04-06 14:13:03',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $method = new ReflectionMethod(DashboardService::class, 'lottoTopRiskyNumbersSummary');
        $method->setAccessible(true);
        $rows = $method->invoke($this->service, '2026-04-06', '2026-04-06', 1);

        $this->assertCount(1, $rows);
        $this->assertSame('งดออกผล', $rows[0]['rounds'][0]['result_number_display']);
    }

    public function test_with_boa_155_contract_envelope_adds_required_metadata(): void
    {
        $wrapped = $this->service->withBoa155Contract(
            ['foo' => 'bar'],
            [
                'date_start' => '2026-04-15',
                'date_end' => '2026-04-10',
                'time_scope' => 'draw_time',
            ]
        );

        $this->assertSame('bar', $wrapped['foo']);
        $this->assertSame('BOA-155-2026-04-11', $wrapped['contract_version']);
        $this->assertSame('draw_time', $wrapped['time_scope_used']);
        $this->assertSame('2026-04-10', $wrapped['date_scope_start']);
        $this->assertSame('2026-04-15', $wrapped['date_scope_end']);
        $this->assertSame('Asia/Bangkok', $wrapped['contract']['timezone']);
        $this->assertSame('BOA-155-2026-04-11', $wrapped['contract']['contract_version']);
    }

    public function test_sync_summary_range_returns_status_and_last_sync_time_when_table_missing(): void
    {
        $result = $this->service->syncSummaryRange([
            'date_start' => '2026-04-10',
            'date_end' => '2026-04-10',
        ]);

        $this->assertSame('failed', $result['sync_status']);
        $this->assertSame('2026-04-10', $result['sync_scope']['date_scope_start']);
        $this->assertSame('2026-04-10', $result['sync_scope']['date_scope_end']);
        $this->assertSame(0, $result['requested_days']);
        $this->assertSame(0, $result['synced_days']);
        $this->assertSame(0, $result['failed_days']);
        $this->assertNotEmpty($result['last_sync_time']);
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

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('market_id')->nullable();
            $table->date('draw_date')->nullable();
        });

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('draw_id');
            $table->string('status')->nullable();
        });

        Schema::create('lotto_ticket_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('bet_type', 64);
            $table->string('number', 32);
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('payout_at_time', 18, 2)->default(0);
            $table->decimal('win_amount', 18, 2)->default(0);
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

        DB::table('lotto_draws')->insert([
            ['id' => 101, 'market_id' => 1, 'draw_date' => '2026-04-10'],
            ['id' => 102, 'market_id' => 2, 'draw_date' => '2026-04-10'],
        ]);

        DB::table('lotto_tickets')->insert([
            ['id' => 1001, 'draw_id' => 101, 'status' => 'active'],
            ['id' => 1002, 'draw_id' => 102, 'status' => 'active'],
        ]);

        DB::table('lotto_ticket_items')->insert([
            ['ticket_id' => 1001, 'bet_type' => 'top_3', 'number' => '587', 'amount' => 100, 'payout_at_time' => 1000, 'win_amount' => 0],
            ['ticket_id' => 1002, 'bet_type' => 'top_3', 'number' => '587', 'amount' => 100, 'payout_at_time' => 1000, 'win_amount' => 0],
            ['ticket_id' => 1001, 'bet_type' => 'top_3', 'number' => '111', 'amount' => 50, 'payout_at_time' => 1000, 'win_amount' => 0],
        ]);

        $method = new ReflectionMethod(DashboardService::class, 'lottoBetTypeInsightsSummary');
        $method->setAccessible(true);
        $rows = $method->invoke($this->service, '2026-04-10', '2026-04-10');

        $this->assertCount(1, $rows);
        $this->assertSame('111', $rows[0]['hottest_number']);
        $this->assertSame(1000.0, (float) $rows[0]['hottest_number_amount_raw']);
        $this->assertSame('587', $rows[0]['max_risk_number']);
        $this->assertSame(1040000.0, (float) $rows[0]['max_risk_value_raw']);
        $this->assertSame(1040000.0, (float) $rows[0]['risk_exposure_total_raw']);
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

    public function test_top_risk_users_summary_returns_ranked_users_with_main_market(): void
    {
        Schema::create('members', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
            $table->string('user_name')->nullable();
        });
        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
        });
        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date');
        });
        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('draw_id');
            $table->string('status')->nullable();
        });
        Schema::create('lotto_ticket_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('bet_type');
            $table->string('number');
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('payout_at_time', 12, 2)->default(0);
            $table->decimal('win_amount', 12, 2)->nullable();
        });

        DB::table('members')->insert([
            ['code' => 101, 'user_name' => 'alpha'],
            ['code' => 102, 'user_name' => 'beta'],
        ]);
        DB::table('lotto_markets')->insert([
            ['id' => 1, 'name' => 'หวยรัฐบาล'],
            ['id' => 2, 'name' => 'หวยลาว'],
        ]);
        DB::table('lotto_draws')->insert([
            ['id' => 11, 'market_id' => 1, 'draw_date' => '2026-04-10'],
            ['id' => 12, 'market_id' => 2, 'draw_date' => '2026-04-10'],
        ]);
        DB::table('lotto_tickets')->insert([
            ['id' => 1001, 'member_id' => 101, 'draw_id' => 11, 'status' => 'resulted'],
            ['id' => 1002, 'member_id' => 101, 'draw_id' => 11, 'status' => 'active'],
            ['id' => 1003, 'member_id' => 102, 'draw_id' => 12, 'status' => 'active'],
        ]);
        DB::table('lotto_ticket_items')->insert([
            ['ticket_id' => 1001, 'bet_type' => 'top_3', 'number' => '587', 'amount' => 100, 'payout_at_time' => 1000, 'win_amount' => 5000],
            ['ticket_id' => 1002, 'bet_type' => 'top_3', 'number' => '587', 'amount' => 50, 'payout_at_time' => 900, 'win_amount' => 0],
            ['ticket_id' => 1002, 'bet_type' => 'top_3', 'number' => '999', 'amount' => 200, 'payout_at_time' => 100, 'win_amount' => 0],
            ['ticket_id' => 1003, 'bet_type' => 'top_2', 'number' => '11', 'amount' => 20, 'payout_at_time' => 500, 'win_amount' => 0],
        ]);

        $method = new ReflectionMethod(DashboardService::class, 'lottoTopRiskUsersSummary');
        $method->setAccessible(true);
        $rows = $method->invoke($this->service, '2026-04-10', '2026-04-10', 5);

        $this->assertCount(2, $rows);
        $this->assertSame('101', $rows[0]['member_id']);
        $this->assertSame('alpha', $rows[0]['member_username']);
        $this->assertSame('หวยรัฐบาล', $rows[0]['main_market']);
        $this->assertSame('587', $rows[0]['risk_number']);
        $this->assertSame('top_3', $rows[0]['risk_bet_type']);
        $this->assertSame(2, $rows[0]['bet_count']);
        $this->assertSame(145000.0, (float) $rows[0]['total_exposure_raw']);
        $this->assertSame(1, $rows[0]['rank']);
    }

    private function dropTableIfExists(string $table): void
    {
        if (Schema::hasTable($table)) {
            Schema::drop($table);
        }
    }
}
