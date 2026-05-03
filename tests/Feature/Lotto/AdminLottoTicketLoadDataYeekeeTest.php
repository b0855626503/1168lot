<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Http\Controllers\Admin\LottoTicketController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminLottoTicketLoadDataYeekeeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('yeekee_rounds');
        Schema::dropIfExists('lotto_ticket_items');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('members');

        parent::tearDown();
    }

    public function test_loaddata_returns_is_yeekee_false_and_round_no_null_for_normal_market(): void
    {
        $this->seedMember(10, 0);
        $this->seedMarket(1, 'หวยไทย', 'normal');
        $this->seedDraw(101, 1);
        $this->seedTicket(1001, 10, 101);

        $request = Request::create('/lotto/tickets/loaddata', 'POST', ['id' => 1001]);
        $response = $this->createTestResponse(
            app(LottoTicketController::class)->loadData($request)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.draw.is_yeekee', false);
        $response->assertJsonPath('data.draw.round_no', null);
    }

    public function test_loaddata_returns_is_yeekee_true_and_round_no_when_yeekee_round_exists(): void
    {
        $this->seedMember(11, 0);
        $this->seedMarket(2, 'ยี่กี่นครม', 'yeekee');
        $this->seedDraw(102, 2);
        $this->seedTicket(1002, 11, 102);
        $this->seedYeekeeRound(201, 102, 77);

        $request = Request::create('/lotto/tickets/loaddata', 'POST', ['id' => 1002]);
        $response = $this->createTestResponse(
            app(LottoTicketController::class)->loadData($request)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.draw.is_yeekee', true);
        $response->assertJsonPath('data.draw.round_no', 77);
    }

    public function test_loaddata_returns_is_yeekee_true_and_round_no_null_when_no_yeekee_round_row(): void
    {
        $this->seedMember(12, 0);
        $this->seedMarket(3, 'ยี่กี่VIP', 'yeekee');
        $this->seedDraw(103, 3);
        $this->seedTicket(1003, 12, 103);
        // No yeekee_rounds row inserted for draw 103.

        $request = Request::create('/lotto/tickets/loaddata', 'POST', ['id' => 1003]);
        $response = $this->createTestResponse(
            app(LottoTicketController::class)->loadData($request)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.draw.is_yeekee', true);
        $response->assertJsonPath('data.draw.round_no', null);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function seedMember(int $code, float $balance): void
    {
        DB::table('members')->insert([
            'code' => $code,
            'user_name' => 'member'.$code,
            'name' => 'Member '.$code,
            'balance' => $balance,
            'date_create' => now(),
            'date_update' => now(),
        ]);
    }

    private function seedMarket(int $id, string $name, string $resultMode): void
    {
        DB::table('lotto_markets')->insert([
            'id' => $id,
            'group_id' => 1,
            'name' => $name,
            'result_mode' => $resultMode,
            'is_enabled' => 1,
        ]);
    }

    private function seedDraw(int $id, int $marketId): void
    {
        DB::table('lotto_draws')->insert([
            'id' => $id,
            'market_id' => $marketId,
            'draw_date' => '2026-04-10',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedTicket(int $id, int $memberId, int $drawId): void
    {
        DB::table('lotto_tickets')->insert([
            'id' => $id,
            'member_id' => $memberId,
            'draw_id' => $drawId,
            'total_amount' => 100,
            'total_bet_amount' => 100,
            'total_discount_amount' => 0,
            'total_net_amount' => 100,
            'total_win_amount' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedYeekeeRound(int $id, int $drawId, int $roundNo): void
    {
        DB::table('yeekee_rounds')->insert([
            'id' => $id,
            'lotto_draw_id' => $drawId,
            'round_no' => $roundNo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('yeekee_rounds');
        Schema::dropIfExists('lotto_ticket_items');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('members');

        Schema::create('members', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
            $table->string('user_name')->nullable();
            $table->string('name')->nullable();
            $table->decimal('balance', 12, 2)->default(0);
            $table->dateTime('date_create')->nullable();
            $table->dateTime('date_update')->nullable();
        });

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('group_id')->nullable();
            $table->string('name');
            $table->string('result_mode')->default('normal');
            $table->boolean('is_enabled')->default(true);
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('member_id');
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
            $table->string('bet_type')->nullable();
            $table->string('number')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('payout_at_time', 12, 2)->default(0);
            $table->decimal('discount_percent_at_time', 6, 2)->default(0);
            $table->decimal('discount_amount_at_time', 12, 2)->default(0);
            $table->decimal('payable_amount_at_time', 12, 2)->default(0);
            $table->decimal('potential_win_amount_at_time', 12, 2)->default(0);
            $table->string('result_status')->nullable();
            $table->decimal('win_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('yeekee_rounds', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('lotto_draw_id');
            $table->unsignedSmallInteger('round_no');
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('ref_type')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('created_by_type')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();
        });
    }
}
