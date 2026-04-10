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
        $this->assertStringContainsString('ยอดเสี่ยงสูงสุดต่อเลข', $contents);
        $this->assertStringContainsString('member_username', $contents);

        $projector = file_get_contents(base_path('app/Services/Dashboard/DashboardSummaryProjector.php'));
        $this->assertNotFalse($projector);
        $this->assertStringContainsString("(float) \$deposit['deposit_success_amount']", $projector);
        $this->assertStringContainsString("- (float) \$withdraw['withdraw_total_amount']", $projector);
        $this->assertStringNotContainsString("+ (float) \$lottoCash['lotto_net_cash']", $projector);
    }

    public function test_lotto_risk_summary_uses_latest_snapshot_only_within_selected_day(): void
    {
        Schema::create('lotto_dashboard_risk_snapshot', function (Blueprint $table): void {
            $table->id();
            $table->string('web_code', 64);
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('round_id');
            $table->string('bet_type', 64);
            $table->string('number', 32);
            $table->timestamp('snapshot_at');
            $table->decimal('stake_total', 18, 2)->default(0);
            $table->decimal('payout_if_hit', 18, 2)->default(0);
            $table->decimal('liability', 18, 2)->default(0);
            $table->timestamps();
        });

        $webCode = app(DashboardWebCodeResolver::class)->resolve();

        DB::table('lotto_dashboard_risk_snapshot')->insert([
            [
                'web_code' => $webCode,
                'market_id' => 1,
                'round_id' => 10,
                'bet_type' => 'top_2',
                'number' => '11',
                'snapshot_at' => '2026-04-10 09:00:00',
                'stake_total' => 100,
                'payout_if_hit' => 1000,
                'liability' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'web_code' => $webCode,
                'market_id' => 1,
                'round_id' => 10,
                'bet_type' => 'top_2',
                'number' => '22',
                'snapshot_at' => '2026-04-10 09:00:00',
                'stake_total' => 200,
                'payout_if_hit' => 2000,
                'liability' => 2000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'web_code' => $webCode,
                'market_id' => 1,
                'round_id' => 10,
                'bet_type' => 'top_2',
                'number' => '11',
                'snapshot_at' => '2026-04-10 10:00:00',
                'stake_total' => 300,
                'payout_if_hit' => 3000,
                'liability' => 3000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'web_code' => $webCode,
                'market_id' => 2,
                'round_id' => 20,
                'bet_type' => 'bottom_2',
                'number' => '33',
                'snapshot_at' => '2026-04-10 10:00:00',
                'stake_total' => 400,
                'payout_if_hit' => 4000,
                'liability' => 4000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $method = new ReflectionMethod(DashboardService::class, 'lottoRiskSummaryMetrics');
        $method->setAccessible(true);
        $summary = $method->invoke($this->service, '2026-04-10', '2026-04-10');

        $this->assertSame(2, $summary['numbers']);
        $this->assertSame(2, $summary['markets']);
        $this->assertSame(2, $summary['rounds']);
        $this->assertSame(7000.0, (float) $summary['exposure_total']);
        $this->assertSame(7000.0, (float) $summary['liability_total']);
        $this->assertSame(4000.0, (float) $summary['liability_max']);
        $this->assertSame('2026-04-10 10:00:00', $summary['last_snapshot_at']);
    }

    private function dropTableIfExists(string $table): void
    {
        if (Schema::hasTable($table)) {
            Schema::drop($table);
        }
    }
}
