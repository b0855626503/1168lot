<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\DataTables\LottoBlockedNumbersReportDataTable;
use Gametech\Lotto\DataTables\LottoMemberBetTypesReportDataTable;
use Gametech\Lotto\DataTables\LottoProfitLossForecastReportDataTable;
use Gametech\Lotto\DataTables\LottoTicketsCancelReportDataTable;
use Gametech\Lotto\Models\LottoDrawBetSetting;
use Gametech\Lotto\Models\LottoNumberBlock;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LottoTicketItem;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::dropIfExists('lotto_ticket_items');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_number_blocks');
        Schema::dropIfExists('lotto_number_exposures');
        Schema::dropIfExists('lotto_draw_bet_settings');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('members');
        Schema::dropIfExists('employees');

        parent::tearDown();
    }

    public function test_profit_loss_forecast_report_uses_real_draw_and_exposure_data(): void
    {
        DB::table('lotto_draw_bet_settings')->insert([
            'id' => 1,
            'draw_id' => 101,
            'bet_type' => 'top_2',
            'payout' => 90,
            'discount_percent' => 0,
            'is_enabled' => 1,
            'min_bet' => 1,
            'max_bet' => 1000,
            'max_per_number' => 1000,
        ]);

        DB::table('lotto_number_exposures')->insert([
            'id' => 1,
            'draw_id' => 101,
            'bet_type' => 'top_2',
            'number' => '46',
            'sold_amount' => 200,
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
            'payout_at_time' => 90,
            'result_status' => null,
            'win_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = app(LottoProfitLossForecastReportDataTable::class)
            ->query(new LottoDrawBetSetting())
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('หวยมาเลเซีย', $row->market_name);
        $this->assertSame('top_2', $row->bet_type);
        $this->assertEquals(200.0, (float) $row->total_bet_amount);
        $this->assertEquals(18000.0, (float) $row->max_risk_amount);
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
            ->query(new LottoTicketItem())
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(52, (int) $row->member_id);
        $this->assertSame('หวยมาเลเซีย', $row->market_name);
        $this->assertSame('top_2', $row->bet_type);
        $this->assertSame(1, (int) $row->ticket_count);
        $this->assertEquals(50.0, (float) $row->total_bet_amount);
        $this->assertEquals(1750.0, (float) $row->net_result);
    }

    public function test_tickets_cancel_report_reads_status_and_canceller_columns(): void
    {
        DB::table('employees')->insert([
            'code' => 9001,
            'user_name' => 'staff01',
            'name' => 'Staff One',
            'surname' => 'Tester',
            'enable' => 'Y',
            'superadmin' => 'N',
            'role_id' => 1,
            'date_create' => now(),
            'date_update' => now(),
        ]);

        DB::table('lotto_tickets')->insert([
            'id' => 1003,
            'member_id' => 52,
            'draw_id' => 101,
            'total_amount' => 120,
            'total_bet_amount' => 120,
            'total_discount_amount' => 0,
            'total_net_amount' => 120,
            'total_win_amount' => 0,
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => 9001,
            'created_at' => now()->subMinute(),
            'updated_at' => now(),
        ]);

        $row = app(LottoTicketsCancelReportDataTable::class)
            ->query(new LottoTicket())
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('cancelled', $row->status);
        $this->assertSame('staff01', $row->cancel_admin_user_name);
        $this->assertSame('หวยมาเลเซีย', $row->market_name);
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

        $row = app(LottoBlockedNumbersReportDataTable::class)
            ->query(new LottoNumberBlock())
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('หวยมาเลเซีย', $row->market_name);
        $this->assertSame('top_2', $row->bet_type);
        $this->assertSame('46', $row->number);
        $this->assertSame('block', $row->mode);
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
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('icon')->nullable();
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
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_ticket_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ticket_id');
            $table->string('bet_type');
            $table->string('number');
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('payout_at_time', 12, 2)->default(0);
            $table->string('result_status')->nullable();
            $table->decimal('win_amount', 12, 2)->nullable();
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

        DB::table('lotto_markets')->insert([
            'id' => 1,
            'name' => 'หวยมาเลเซีย',
            'logo' => 'https://example.com/malaysia.png',
            'icon' => null,
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
    }
}
