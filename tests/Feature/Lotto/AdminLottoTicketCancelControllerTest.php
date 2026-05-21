<?php

namespace Tests\Feature\Lotto;

use App\Services\Dashboard\DashboardSummarySyncService;
use Gametech\Lotto\Http\Controllers\Admin\LottoTicketController;
use Gametech\Lotto\Services\WalletTransactionService;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AdminLottoTicketCancelControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'log']);
        $this->prepareSchema();
        $this->app->instance(DashboardSummarySyncService::class, new class
        {
            public function dispatchForModelChange(string $domain, $model, array $overrideSections = []): void {}

            public function dispatchRiskCurrentForDraw(int $drawId, string $sourceType, string $sourceId): void {}
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('logs');
        Schema::dropIfExists('banks');
        Schema::dropIfExists('lotto_number_exposures');
        Schema::dropIfExists('lotto_ticket_items');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('members');
        Schema::dropIfExists('employees');

        parent::tearDown();
    }

    public function test_admin_can_cancel_active_ticket_with_reason_and_refund_member(): void
    {
        $this->mockAdminGuard(7001);

        $this->seedMember(52, 500);
        $this->seedOpenDraw(101, 1);
        $this->seedTicket(1001, 52, 101, 'active');
        $this->seedTicketItem(1001, 'top_2', '46', 80);
        $this->seedExposure(101, 'top_2', '46', 80);
        $this->seedBetWalletTransaction(52, 1001, 80);

        $request = Request::create('/lotto/tickets/1001/cancel', 'POST', [
            'reason' => 'เลขผิดชุด',
        ]);

        $response = $this->createTestResponse(
            app(LottoTicketController::class)->cancel($request, 1001, app(WalletTransactionService::class))
        );

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'ยกเลิกโพยสำเร็จ');
        $response->assertJsonPath('data.ticket_id', 1001);
        $response->assertJsonPath('data.reason', 'เลขผิดชุด');

        $this->assertSame('cancelled', DB::table('lotto_tickets')->where('id', 1001)->value('status'));
        $this->assertSame('เลขผิดชุด', DB::table('lotto_tickets')->where('id', 1001)->value('reason'));
        $this->assertSame(7001, (int) DB::table('lotto_tickets')->where('id', 1001)->value('cancelled_by'));
        $this->assertEquals(80.0, (float) DB::table('lotto_tickets')->where('id', 1001)->value('refund_amount'));
        $this->assertEquals(0.0, (float) DB::table('lotto_number_exposures')->where('draw_id', 101)->where('bet_type', 'top_2')->where('number', '46')->value('sold_amount'));
        $this->assertEquals(580.0, (float) DB::table('members')->where('code', 52)->value('balance'));

        $cancelTransaction = DB::table('wallet_transactions')
            ->where('ref_type', 'LOTTO_CANCEL')
            ->where('ref_id', 1001)
            ->first();

        $this->assertNotNull($cancelTransaction);
        $this->assertSame('admin', $cancelTransaction->created_by_type);
        $this->assertSame(7001, (int) $cancelTransaction->created_by_id);
    }

    public function test_admin_cancel_requires_reason(): void
    {
        $this->mockAdminGuard(7002);

        $this->seedMember(53, 300);
        $this->seedOpenDraw(102, 2);
        $this->seedTicket(1002, 53, 102, 'active');
        $this->seedTicketItem(1002, 'top_2', '47', 50);
        $this->seedExposure(102, 'top_2', '47', 50);
        $this->seedBetWalletTransaction(53, 1002, 50);

        $request = Request::create('/lotto/tickets/1002/cancel', 'POST', [
            'reason' => '   ',
        ]);

        $response = $this->createTestResponse(
            app(LottoTicketController::class)->cancel($request, 1002, app(WalletTransactionService::class))
        );

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'กรุณาระบุสาเหตุการยกเลิกโพย');
        $this->assertSame('active', DB::table('lotto_tickets')->where('id', 1002)->value('status'));
        $this->assertSame(0, DB::table('wallet_transactions')->where('ref_type', 'LOTTO_CANCEL')->count());
    }

    private function mockAdminGuard(int $adminCode): void
    {
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('user')->andReturn((object) [
            'code' => $adminCode,
            'user_name' => 'staff'.$adminCode,
        ]);
        $guard->shouldReceive('id')->andReturn($adminCode);

        $authFactory = Mockery::mock(AuthFactory::class);
        $authFactory->shouldReceive('guard')->with('admin')->andReturn($guard);

        $this->app->instance('auth', $authFactory);
    }

    private function seedMember(int $memberCode, float $balance): void
    {
        DB::table('members')->insert([
            'code' => $memberCode,
            'user_name' => 'member'.$memberCode,
            'name' => 'Member '.$memberCode,
            'balance' => $balance,
            'date_create' => now(),
            'date_update' => now(),
        ]);
    }

    private function seedOpenDraw(int $drawId, int $marketId): void
    {
        DB::table('lotto_markets')->insert([
            'id' => $marketId,
            'group_id' => 1,
            'name' => 'Market '.$marketId,
            'is_enabled' => 1,
        ]);

        DB::table('lotto_draws')->insert([
            'id' => $drawId,
            'market_id' => $marketId,
            'draw_date' => '2026-04-05',
            'open_at' => '2026-04-05 09:00:00',
            'close_at' => '2026-04-05 23:00:00',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedTicket(int $ticketId, int $memberId, int $drawId, string $status): void
    {
        DB::table('lotto_tickets')->insert([
            'id' => $ticketId,
            'member_id' => $memberId,
            'draw_id' => $drawId,
            'total_amount' => 80,
            'total_bet_amount' => 80,
            'total_discount_amount' => 0,
            'total_net_amount' => 80,
            'total_win_amount' => 0,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedTicketItem(int $ticketId, string $betType, string $number, float $amount): void
    {
        DB::table('lotto_ticket_items')->insert([
            'ticket_id' => $ticketId,
            'bet_type' => $betType,
            'number' => $number,
            'amount' => $amount,
            'payout_at_time' => 100,
            'discount_percent_at_time' => 0,
            'discount_amount_at_time' => 0,
            'payable_amount_at_time' => $amount,
            'potential_win_amount_at_time' => $amount * 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedExposure(int $drawId, string $betType, string $number, float $soldAmount): void
    {
        DB::table('lotto_number_exposures')->insert([
            'draw_id' => $drawId,
            'bet_type' => $betType,
            'number' => $number,
            'sold_amount' => $soldAmount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedBetWalletTransaction(int $memberId, int $ticketId, float $amount): void
    {
        DB::table('wallet_transactions')->insert([
            'member_id' => $memberId,
            'scope' => 'MEMBER',
            'game_user_id' => null,
            'direction' => 'DEBIT',
            'amount' => $amount,
            'balance_before' => 580,
            'balance_after' => 500,
            'ref_type' => 'LOTTO_BET',
            'ref_id' => $ticketId,
            'ref_code' => (string) $ticketId,
            'group_code' => 'LOTTO_BET_'.$ticketId,
            'related_txn_id' => null,
            'status' => 'SUCCESS',
            'description' => 'เดิมพันหวย',
            'meta' => null,
            'created_by_type' => 'member',
            'created_by_id' => $memberId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('logs');
        Schema::dropIfExists('banks');
        Schema::dropIfExists('lotto_number_exposures');
        Schema::dropIfExists('lotto_ticket_items');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('members');
        Schema::dropIfExists('employees');

        Schema::create('employees', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
            $table->string('user_name')->nullable();
            $table->string('name')->nullable();
            $table->string('surname')->nullable();
            $table->string('enable')->nullable();
            $table->string('superadmin')->nullable();
            $table->dateTime('date_create')->nullable();
            $table->dateTime('date_update')->nullable();
        });

        Schema::create('banks', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
            $table->string('name')->nullable();
        });

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
            $table->boolean('is_enabled')->default(true);
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
            $table->dateTime('open_at')->nullable();
            $table->dateTime('close_at')->nullable();
            $table->dateTime('result_at')->nullable();
            $table->text('result_number')->nullable();
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
            $table->string('bet_type');
            $table->string('number');
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('payout_at_time', 12, 2)->default(0);
            $table->decimal('discount_percent_at_time', 12, 2)->default(0);
            $table->decimal('discount_amount_at_time', 12, 2)->default(0);
            $table->decimal('payable_amount_at_time', 12, 2)->default(0);
            $table->decimal('potential_win_amount_at_time', 12, 2)->default(0);
            $table->string('result_status')->nullable();
            $table->decimal('win_amount', 12, 2)->nullable();
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

        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('member_id');
            $table->string('scope');
            $table->unsignedBigInteger('game_user_id')->nullable();
            $table->string('direction');
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('ref_type');
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('ref_code')->nullable();
            $table->string('group_code')->nullable();
            $table->unsignedBigInteger('related_txn_id')->nullable();
            $table->string('status');
            $table->string('description')->nullable();
            $table->text('meta')->nullable();
            $table->string('created_by_type')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });

        Schema::create('logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('emp_code')->nullable();
            $table->string('mode')->nullable();
            $table->string('menu')->nullable();
            $table->unsignedBigInteger('record')->nullable();
            $table->longText('item_before')->nullable();
            $table->longText('item')->nullable();
            $table->string('ip')->nullable();
            $table->string('user_create')->nullable();
            $table->dateTime('date_update')->nullable();
            $table->dateTime('date_create')->nullable();
        });
    }
}
