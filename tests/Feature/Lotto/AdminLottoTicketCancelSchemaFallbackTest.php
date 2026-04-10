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

class AdminLottoTicketCancelSchemaFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'log']);
        $this->prepareSchema();
        $this->app->instance(DashboardSummarySyncService::class, new class
        {
            public function dispatchForModelChange(string $domain, $model, array $overrideSections = []): void {}
        });
        $this->mockAdminGuard(1);
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

    public function test_admin_cancel_writes_reason_to_ticket_and_transaction_meta(): void
    {
        $this->seedFixture();

        $request = Request::create('/lotto/tickets/202/cancel', 'POST', [
            'reason' => 'เทส',
        ]);

        $response = $this->createTestResponse(
            app(LottoTicketController::class)->cancel($request, 202, app(WalletTransactionService::class))
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertSame('cancelled', DB::table('lotto_tickets')->where('id', 202)->value('status'));
        $this->assertSame('เทส', DB::table('lotto_tickets')->where('id', 202)->value('reason'));
        $cancelTxn = DB::table('wallet_transactions')
            ->where('ref_type', 'LOTTO_CANCEL')
            ->where('ref_id', 202)
            ->orderByDesc('id')
            ->first(['meta']);

        $this->assertNotNull($cancelTxn);
        $decodedMeta = json_decode((string) $cancelTxn->meta, true);
        $this->assertSame('เทส', $decodedMeta['reason'] ?? null);
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

    private function seedFixture(): void
    {
        DB::table('employees')->insert([
            'code' => 1,
            'user_name' => 'staff01',
            'name' => 'Staff',
            'surname' => 'User',
            'enable' => 'Y',
            'superadmin' => 'Y',
            'date_create' => now(),
            'date_update' => now(),
        ]);

        DB::table('members')->insert([
            'code' => 52,
            'user_name' => 'member52',
            'name' => 'Member 52',
            'balance' => 100,
            'date_create' => now(),
            'date_update' => now(),
        ]);

        DB::table('lotto_markets')->insert([
            'id' => 1,
            'group_id' => 1,
            'name' => 'หวยรัฐบาล',
            'is_enabled' => 1,
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 1,
            'market_id' => 1,
            'draw_date' => '2026-04-05',
            'open_at' => '2026-04-05 09:00:00',
            'close_at' => '2026-04-05 23:00:00',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_tickets')->insert([
            'id' => 202,
            'member_id' => 52,
            'draw_id' => 1,
            'total_amount' => 23.24,
            'total_bet_amount' => 23.24,
            'total_discount_amount' => 0,
            'total_net_amount' => 23.24,
            'total_win_amount' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_ticket_items')->insert([
            'ticket_id' => 202,
            'bet_type' => 'top_2',
            'number' => '46',
            'amount' => 23.24,
            'payout_at_time' => 100,
            'discount_percent_at_time' => 0,
            'discount_amount_at_time' => 0,
            'payable_amount_at_time' => 23.24,
            'potential_win_amount_at_time' => 2324,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_number_exposures')->insert([
            'draw_id' => 1,
            'bet_type' => 'top_2',
            'number' => '46',
            'sold_amount' => 23.24,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('wallet_transactions')->insert([
            'member_id' => 52,
            'scope' => 'MEMBER',
            'game_user_id' => null,
            'direction' => 'DEBIT',
            'amount' => 23.24,
            'balance_before' => 123.24,
            'balance_after' => 100,
            'ref_type' => 'LOTTO_BET',
            'ref_id' => 202,
            'ref_code' => '202',
            'group_code' => 'LOTTO_BET_202',
            'related_txn_id' => null,
            'status' => 'SUCCESS',
            'description' => 'เดิมพันหวย',
            'meta' => null,
            'created_by_type' => 'member',
            'created_by_id' => 52,
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
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->text('reason')->nullable();
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
