<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\DataTables\LottoBlockedNumbersReportDataTable;
use Gametech\Lotto\DataTables\LottoMemberBetTypesReportDataTable;
use Gametech\Lotto\DataTables\LottoProfitLossForecastReportDataTable;
use Gametech\Lotto\DataTables\LottoRevenueReportDataTable;
use Gametech\Lotto\DataTables\LottoTicketsCancelReportDataTable;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoDrawBetSetting;
use Gametech\Lotto\Models\LottoNumberBlock;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LottoTicketItem;
use Gametech\Lotto\Services\LottoProfitLossForecastReportService;
use Gametech\Lotto\Transformers\LottoTicketsCancelReportTransformer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminLottoReportsDataTableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        $this->seedBaseData();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('lotto_ticket_items');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_number_blocks');
        Schema::dropIfExists('lotto_number_exposures');
        Schema::dropIfExists('lotto_draw_bet_settings');
        Schema::dropIfExists('lotto_group_package_bet_settings');
        Schema::dropIfExists('lotto_group_packages');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');
        Schema::dropIfExists('members');
        Schema::dropIfExists('employees');

        parent::tearDown();
    }

    public function test_profit_loss_forecast_report_service_builds_summary_and_number_rows_from_real_data(): void
    {
        DB::table('lotto_group_package_bet_settings')->insert([
            'id' => 1,
            'package_id' => 301,
            'bet_type' => 'top_2',
            'payout' => 90,
            'discount_percent' => 0,
            'is_enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_tickets')->insert([
            'id' => 1001,
            'member_id' => 52,
            'draw_id' => 101,
            'total_amount' => 200,
            'total_bet_amount' => 200,
            'total_discount_amount' => 0,
            'total_net_amount' => 200,
            'total_win_amount' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_ticket_items')->insert([
            'id' => 5001,
            'ticket_id' => 1001,
            'bet_type' => 'top_2',
            'number' => '46',
            'amount' => 200,
            'package_id_at_time' => 301,
            'payout_at_time' => 90,
            'discount_amount_at_time' => 0,
            'payable_amount_at_time' => 200,
            'result_status' => null,
            'win_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_group_package_bet_settings')->insert([
            'id' => 2,
            'package_id' => 302,
            'bet_type' => 'top_2',
            'payout' => 80,
            'discount_percent' => 10,
            'is_enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_tickets')->insert([
            'id' => 1004,
            'member_id' => 52,
            'draw_id' => 101,
            'total_amount' => 50,
            'total_bet_amount' => 50,
            'total_discount_amount' => 5,
            'total_net_amount' => 45,
            'total_win_amount' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_ticket_items')->insert([
            'id' => 5006,
            'ticket_id' => 1004,
            'bet_type' => 'top_2',
            'number' => '47',
            'amount' => 50,
            'package_id_at_time' => 302,
            'payout_at_time' => 80,
            'discount_amount_at_time' => 5,
            'payable_amount_at_time' => 45,
            'result_status' => null,
            'win_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = app(LottoProfitLossForecastReportService::class)->build(1, 101, 301);

        $this->assertSame('หวยมาเลเซีย', $payload['draw']['market_name']);
        $this->assertSame('2026-04-05', $payload['draw']['draw_date']);
        $this->assertSame('แพกเกจหลัก', $payload['package']['name']);
        $this->assertCount(1, $payload['columns']);
        $this->assertSame('top_2', $payload['columns'][0]['bet_type']);
        $this->assertEquals(200.0, (float) $payload['columns'][0]['total_bet_amount']);
        $this->assertEquals(200.0, (float) $payload['columns'][0]['total_receive_amount']);
        $this->assertEquals(0.0, (float) $payload['columns'][0]['total_payout_amount']);
        $this->assertEquals(200.0, (float) $payload['columns'][0]['total_profit_amount']);

        $summaryRows = collect($payload['summary_rows'])->keyBy('metric');
        $this->assertEquals(200.0, (float) $summaryRows['total_bet_amount']['overall']);
        $this->assertEquals(0.0, (float) $summaryRows['total_discount_amount']['overall']);
        $this->assertEquals(200.0, (float) $summaryRows['total_receive_amount']['overall']);
        $this->assertEquals(0.0, (float) $summaryRows['total_payout_amount']['overall']);
        $this->assertEquals(200.0, (float) $summaryRows['total_profit_amount']['overall']);

        $numberRow = collect($payload['number_rows'])->first(static function (array $row): bool {
            return (string) ($row['cells']['top_2']['number'] ?? '') === '46';
        });

        $this->assertNotNull($numberRow);
        $this->assertEquals(200.0, (float) $numberRow['cells']['top_2']['amount']);
    }

    public function test_member_bet_types_report_aggregates_by_member_market_and_bet_type(): void
    {
        DB::table('lotto_tickets')->insert([
            'id' => 1002,
            'member_id' => 52,
            'draw_id' => 101,
            'total_amount' => 50,
            'total_bet_amount' => 50,
            'total_discount_amount' => 0,
            'total_net_amount' => 50,
            'total_win_amount' => 0,
            'status' => 'resulted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_ticket_items')->insert([
            [
                'id' => 5002,
                'ticket_id' => 1002,
                'bet_type' => 'top_2',
                'number' => '46',
                'amount' => 20,
                'payout_at_time' => 90,
                'result_status' => 'win',
                'win_amount' => 1800,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5003,
                'ticket_id' => 1002,
                'bet_type' => 'top_2',
                'number' => '47',
                'amount' => 30,
                'payout_at_time' => 90,
                'result_status' => 'lose',
                'win_amount' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $row = app(LottoMemberBetTypesReportDataTable::class)
            ->query(new LottoTicketItem)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(52, (int) $row->member_id);
        $this->assertSame('หวยมาเลเซีย', $row->market_name);
        $this->assertSame('top_2', $row->bet_type);
        $this->assertSame(1, (int) $row->ticket_count);
        $this->assertEquals(50.0, (float) $row->total_bet_amount);
        $this->assertEquals(1750.0, (float) $row->net_result);
    }

    public function test_tickets_cancel_report_reads_financial_package_and_member_canceller_columns(): void
    {
        $createdAt = now()->subMinutes(5);
        $cancelledAt = now()->subMinute();
        $updatedAt = now();

        DB::table('lotto_tickets')->insert([
            'id' => 1003,
            'member_id' => 52,
            'draw_id' => 101,
            'total_amount' => 120,
            'total_bet_amount' => 120,
            'total_discount_amount' => 20,
            'total_net_amount' => 100,
            'total_win_amount' => 450,
            'status' => 'cancelled',
            'reason' => 'งดออกผล',
            'cancelled_at' => $cancelledAt,
            'refund_amount' => 100,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);

        DB::table('lotto_ticket_items')->insert([
            'id' => 5004,
            'ticket_id' => 1003,
            'bet_type' => 'top_2',
            'number' => '48',
            'amount' => 120,
            'package_name_at_time' => 'ลด 17%',
            'payout_at_time' => 79,
            'result_status' => null,
            'win_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('wallet_transactions')->insert([
            'id' => 8001,
            'member_id' => 52,
            'scope' => 'MEMBER',
            'direction' => 'CREDIT',
            'amount' => 100,
            'balance_before' => 900,
            'balance_after' => 1000,
            'ref_type' => 'LOTTO_CANCEL',
            'ref_id' => 1003,
            'status' => 'SUCCESS',
            'created_by_type' => 'member',
            'created_by_id' => 52,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = app(LottoTicketsCancelReportDataTable::class)
            ->query(new LottoTicket)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('cancelled', $row->status);
        $this->assertSame('member52', $row->cancel_tx_member_user_name);
        $this->assertSame('หวยมาเลเซีย', $row->market_name);
        $this->assertSame('งดออกผล', $row->reason);
        $this->assertEquals(20.0, (float) $row->total_discount_amount);
        $this->assertEquals(100.0, (float) $row->total_net_amount);
        $this->assertEquals(450.0, (float) $row->total_win_amount);
        $this->assertSame('ลด 17%', $row->items->pluck('package_name_at_time')->filter()->implode(', '));

        $payload = (new LottoTicketsCancelReportTransformer)->transform($row);
        $this->assertSame($createdAt->format('d/m/Y H:i'), $payload['created_at']);
        $this->assertSame($cancelledAt->format('d/m/Y H:i'), $payload['latest_updated_at']);
    }

    public function test_blocked_numbers_report_reads_real_blocks(): void
    {
        DB::table('lotto_number_blocks')->insert([
            'id' => 7001,
            'draw_id' => 101,
            'bet_type' => 'top_2',
            'number' => '46',
            'mode' => 'block',
            'reason' => 'เต็ม',
            'blocked_by' => 9001,
            'blocked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_draw_bet_settings')->insert([
            'id' => 1,
            'draw_id' => 101,
            'bet_type' => 'top_2',
            'payout' => 90,
            'discount_percent' => 0,
            'is_enabled' => true,
            'min_bet' => 1,
            'max_bet' => 1000,
            'max_per_number' => 500,
        ]);

        $row = app(LottoBlockedNumbersReportDataTable::class)
            ->query(new LottoNumberBlock)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('หวยมาเลเซีย', $row->market_name);
        $this->assertSame('top_2', $row->bet_type);
        $this->assertSame('46', $row->number);
        $this->assertSame('block', $row->mode);
    }

    public function test_reports_default_exclude_yeekee_and_allow_yeekee_when_market_type_selected(): void
    {
        DB::table('lotto_tickets')->insert([
            'id' => 1005,
            'member_id' => 52,
            'draw_id' => 202,
            'total_amount' => 90,
            'total_bet_amount' => 90,
            'total_discount_amount' => 0,
            'total_net_amount' => 90,
            'total_win_amount' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_ticket_items')->insert([
            'id' => 5007,
            'ticket_id' => 1005,
            'bet_type' => 'top_2',
            'number' => '99',
            'amount' => 90,
            'payout_at_time' => 90,
            'result_status' => null,
            'win_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_number_blocks')->insert([
            'id' => 7002,
            'draw_id' => 202,
            'bet_type' => 'top_2',
            'number' => '99',
            'mode' => 'block',
            'reason' => 'yeekee block',
            'blocked_by' => 9001,
            'blocked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->app->instance('request', Request::create('/admin/lotto/reports', 'GET'));
        $defaultRevenue = app(LottoRevenueReportDataTable::class)->query(new LottoDraw)->pluck('market_id')->all();
        $this->assertContains(1, $defaultRevenue);
        $this->assertContains(2, $defaultRevenue);

        $this->app->instance('request', Request::create('/admin/lotto/reports', 'GET'));
        $defaultForecastMarketIds = app(LottoProfitLossForecastReportDataTable::class)->query(new LottoDrawBetSetting)->pluck('market_id')->unique()->values()->all();
        $this->assertNotContains(2, $defaultForecastMarketIds);

        $this->app->instance('request', Request::create('/admin/lotto/reports', 'GET'));
        $defaultMemberRows = app(LottoMemberBetTypesReportDataTable::class)->query(new LottoTicketItem)->get();
        $this->assertTrue($defaultMemberRows->every(fn ($row): bool => (int) $row->market_id === 1));

        $this->app->instance('request', Request::create('/admin/lotto/reports', 'GET'));
        $defaultCancelRows = app(LottoTicketsCancelReportDataTable::class)->query(new LottoTicket)->get();
        $this->assertTrue($defaultCancelRows->every(fn ($row): bool => (string) $row->market_name === 'หวยมาเลเซีย'));

        $this->app->instance('request', Request::create('/admin/lotto/reports', 'GET'));
        $defaultBlockedRows = app(LottoBlockedNumbersReportDataTable::class)->query(new LottoNumberBlock)->get();
        $this->assertTrue($defaultBlockedRows->every(fn ($row): bool => (string) $row->market_name === 'หวยมาเลเซีย'));

        $this->app->instance('request', Request::create('/admin/lotto/reports', 'GET', ['market_type' => 'yeekee']));
        $yeekeeForecastMarketIds = app(LottoProfitLossForecastReportDataTable::class)->query(new LottoDrawBetSetting)->pluck('market_id')->unique()->values()->all();
        $this->assertSame([2], $yeekeeForecastMarketIds);

        $this->app->instance('request', Request::create('/admin/lotto/reports', 'GET', ['market_type' => 'yeekee']));
        $yeekeeMemberRows = app(LottoMemberBetTypesReportDataTable::class)->query(new LottoTicketItem)->get();
        $this->assertTrue($yeekeeMemberRows->every(fn ($row): bool => (int) $row->market_id === 2));

        $this->app->instance('request', Request::create('/admin/lotto/reports', 'GET', ['market_type' => 'yeekee']));
        $yeekeeCancelRows = app(LottoTicketsCancelReportDataTable::class)->query(new LottoTicket)->get();
        $this->assertTrue($yeekeeCancelRows->every(fn ($row): bool => (string) $row->market_name === 'หวยยี่กี่'));

        $this->app->instance('request', Request::create('/admin/lotto/reports', 'GET', ['market_type' => 'yeekee']));
        $yeekeeBlockedRows = app(LottoBlockedNumbersReportDataTable::class)->query(new LottoNumberBlock)->get();
        $this->assertTrue($yeekeeBlockedRows->every(fn ($row): bool => (string) $row->market_name === 'หวยยี่กี่'));
    }

    private function prepareSchema(): void
    {
        Schema::create('members', function (Blueprint $table): void {
            $table->unsignedInteger('code')->primary();
            $table->string('user_name')->nullable();
            $table->string('name')->nullable();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
            $table->string('user_name')->nullable();
            $table->string('name')->nullable();
            $table->string('surname')->nullable();
            $table->string('enable')->nullable();
            $table->string('superadmin')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->dateTime('date_create')->nullable();
            $table->dateTime('date_update')->nullable();
        });

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('group_id')->nullable();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('icon')->nullable();
            $table->string('result_mode', 32)->default('normal');
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
            $table->string('status')->default('draft');
            $table->dateTime('open_at')->nullable();
            $table->dateTime('close_at')->nullable();
            $table->dateTime('result_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_draw_bet_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('draw_id');
            $table->string('bet_type');
            $table->decimal('payout', 12, 2)->default(0);
            $table->decimal('discount_percent', 12, 2)->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->decimal('min_bet', 12, 2)->default(0);
            $table->decimal('max_bet', 12, 2)->default(0);
            $table->decimal('max_per_number', 12, 2)->default(0);
        });

        Schema::create('lotto_groups', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
        });

        Schema::create('lotto_group_packages', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('group_id');
            $table->string('name');
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lotto_group_package_bet_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('package_id');
            $table->string('bet_type');
            $table->decimal('payout', 12, 2)->default(0);
            $table->decimal('discount_percent', 12, 2)->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('lotto_number_exposures', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('draw_id');
            $table->string('bet_type');
            $table->string('number');
            $table->decimal('sold_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('lotto_number_blocks', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('draw_id');
            $table->string('bet_type');
            $table->string('number');
            $table->string('mode')->default('block');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('blocked_by')->nullable();
            $table->dateTime('blocked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('member_id');
            $table->unsignedBigInteger('draw_id');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('total_bet_amount', 12, 2)->default(0);
            $table->decimal('total_discount_amount', 12, 2)->default(0);
            $table->decimal('total_net_amount', 12, 2)->default(0);
            $table->decimal('total_win_amount', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->text('reason')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_ticket_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ticket_id');
            $table->string('bet_type');
            $table->string('number');
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedBigInteger('package_id_at_time')->nullable();
            $table->string('package_name_at_time')->nullable();
            $table->decimal('payout_at_time', 12, 2)->default(0);
            $table->decimal('discount_amount_at_time', 12, 2)->default(0);
            $table->decimal('payable_amount_at_time', 12, 2)->default(0);
            $table->string('result_status')->nullable();
            $table->decimal('win_amount', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('member_id');
            $table->string('scope');
            $table->unsignedBigInteger('game_user_id')->nullable();
            $table->string('direction');
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('balance_before', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->string('ref_type');
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('ref_code')->nullable();
            $table->string('provider_txn_id')->nullable();
            $table->string('provider_round_id')->nullable();
            $table->string('group_code')->nullable();
            $table->unsignedBigInteger('related_txn_id')->nullable();
            $table->string('status')->default('SUCCESS');
            $table->string('description')->nullable();
            $table->longText('meta')->nullable();
            $table->string('created_by_type')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });
    }

    private function seedBaseData(): void
    {
        DB::table('members')->insert([
            'code' => 52,
            'user_name' => 'member52',
            'name' => 'Member 52',
        ]);

        DB::table('lotto_groups')->insert([
            'id' => 11,
            'name' => 'หุ้น',
        ]);

        DB::table('lotto_markets')->insert([
            'id' => 1,
            'group_id' => 11,
            'name' => 'หวยมาเลเซีย',
            'logo' => 'https://example.com/malaysia.png',
            'icon' => null,
            'result_mode' => 'normal',
        ]);

        DB::table('lotto_markets')->insert([
            'id' => 2,
            'group_id' => 11,
            'name' => 'หวยยี่กี่',
            'logo' => null,
            'icon' => null,
            'result_mode' => 'yeekee',
        ]);

        DB::table('lotto_group_packages')->insert([
            [
                'id' => 301,
                'group_id' => 11,
                'name' => 'แพกเกจหลัก',
                'image' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 302,
                'group_id' => 11,
                'name' => 'แพกเกจสำรอง',
                'image' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 101,
            'market_id' => 1,
            'draw_date' => '2026-04-05',
            'status' => 'open',
            'open_at' => now(),
            'close_at' => now()->addHour(),
            'result_at' => now()->addHours(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 202,
            'market_id' => 2,
            'draw_date' => '2026-04-05',
            'status' => 'open',
            'open_at' => now(),
            'close_at' => now()->addHour(),
            'result_at' => now()->addHours(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_draw_bet_settings')->insert([
            'id' => 2,
            'draw_id' => 202,
            'bet_type' => 'top_2',
            'payout' => 90,
            'discount_percent' => 0,
            'is_enabled' => true,
            'min_bet' => 1,
            'max_bet' => 1000,
            'max_per_number' => 500,
        ]);
    }
}
