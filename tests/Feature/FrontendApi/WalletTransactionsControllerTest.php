<?php

namespace Tests\Feature\FrontendApi;

use Gametech\FrontendApi\Http\Controllers\Api\V1\WalletController;
use Gametech\Member\Models\Member;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WalletTransactionsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');

        parent::tearDown();
    }

    public function test_wallet_transactions_endpoint_returns_unified_history_and_summary(): void
    {
        $member = $this->customer();
        $this->seedBaseData();

        $request = Request::create('/api/v1/wallet/transactions', 'GET', [
            'limit' => 10,
        ]);
        $request->attributes->set('frontend_language', 'th');
        $request->setUserResolver(static fn () => $member);

        $response = TestResponse::fromBaseResponse(
            app(WalletController::class)->transactions($request)
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.filters.type', 'all');
        $response->assertJsonPath('data.summary.count', 6);
        $response->assertJsonPath('data.summary.total_credit_amount', 600);
        $response->assertJsonPath('data.summary.total_debit_amount', 150);
        $response->assertJsonPath('data.summary.net_amount', 450);
        $response->assertJsonPath('data.pagination.total', 6);
        $response->assertJsonPath('data.items.0.type', 'cashback');
        $response->assertJsonPath('data.items.0.title', 'ยอดเสีย');
        $response->assertJsonPath('data.items.0.direction', 'CREDIT');
        $response->assertJsonPath('data.items.0.signed_amount', 20);
        $response->assertJsonFragment([
            'type' => 'lotto_bet',
            'type_label' => 'แทงหวย',
            'ref_type' => 'LOTTO_BET',
            'title' => 'แทงหวย',
            'detail' => 'แทงหวยจากกระเป๋าหลัก: หวยรัฐบาล งวดวันที่ 2026-04-05',
        ]);
        $response->assertJsonFragment([
            'type' => 'lotto_refund',
            'type_label' => 'คืนเงินหวย',
            'ref_type' => 'LOTTO_CANCEL',
            'title' => 'คืนเงินหวย',
            'detail' => 'คืนเงินโพยหวยเข้ากระเป๋าหลัก: หวยรัฐบาล งวดวันที่ 2026-04-05',
        ]);
    }

    public function test_wallet_transactions_endpoint_filters_by_type_and_date_range(): void
    {
        $member = $this->customer();
        $this->seedBaseData();

        $request = Request::create('/api/v1/wallet/transactions', 'GET', [
            'type' => 'deposit',
            'date_start' => '2026-04-01',
            'date_stop' => '2026-04-01',
        ]);
        $request->attributes->set('frontend_language', 'th');
        $request->setUserResolver(static fn () => $member);

        $response = TestResponse::fromBaseResponse(
            app(WalletController::class)->transactions($request)
        );

        $response->assertOk();
        $response->assertJsonPath('data.filters.type', 'deposit');
        $response->assertJsonPath('data.filters.date_start', '2026-04-01');
        $response->assertJsonPath('data.filters.date_stop', '2026-04-01');
        $response->assertJsonPath('data.summary.count', 1);
        $response->assertJsonPath('data.summary.total_credit_amount', 500);
        $response->assertJsonPath('data.summary.total_debit_amount', 0);
        $response->assertJsonPath('data.items.0.type', 'deposit');
        $response->assertJsonCount(1, 'data.items');
    }

    public function test_wallet_transactions_endpoint_rejects_invalid_type_filter(): void
    {
        $member = $this->customer();
        $request = Request::create('/api/v1/wallet/transactions', 'GET', [
            'type' => 'unknown-type',
        ]);
        $request->attributes->set('frontend_language', 'th');
        $request->setUserResolver(static fn () => $member);

        $response = TestResponse::fromBaseResponse(
            app(WalletController::class)->transactions($request)
        );

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'ไม่รองรับประเภทประวัติที่ร้องขอ');
    }

    private function customer(int $memberCode = 9001): Member
    {
        $member = new Member();
        $member->code = $memberCode;
        $member->name = 'Wallet Member';
        $member->exists = true;

        return $member;
    }

    private function seedBaseData(): void
    {
        DB::table('lotto_markets')->insert([
            'id' => 10,
            'name' => 'หวยรัฐบาล',
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 101,
            'market_id' => 10,
            'draw_date' => '2026-04-05',
        ]);

        DB::table('lotto_tickets')->insert([
            'id' => 1001,
            'draw_id' => 101,
        ]);

        DB::table('wallet_transactions')->insert([
            [
                'id' => 1,
                'member_id' => 9001,
                'scope' => 'MEMBER',
                'direction' => 'CREDIT',
                'amount' => 500,
                'balance_before' => 1000,
                'balance_after' => 1500,
                'ref_type' => 'DEPOSIT',
                'ref_id' => 501,
                'ref_code' => '501',
                'group_code' => 'DEPOSIT_501',
                'status' => 'SUCCESS',
                'description' => 'Auto topup from bank payment #501',
                'meta' => null,
                'created_at' => '2026-04-01 10:00:00',
                'updated_at' => '2026-04-01 10:00:00',
            ],
            [
                'id' => 2,
                'member_id' => 9001,
                'scope' => 'MEMBER',
                'direction' => 'DEBIT',
                'amount' => 100,
                'balance_before' => 1500,
                'balance_after' => 1400,
                'ref_type' => 'WITHDRAW',
                'ref_id' => 601,
                'ref_code' => '601',
                'group_code' => 'WITHDRAW_601',
                'status' => 'SUCCESS',
                'description' => 'Withdraw request #601',
                'meta' => null,
                'created_at' => '2026-04-02 11:00:00',
                'updated_at' => '2026-04-02 11:00:00',
            ],
            [
                'id' => 3,
                'member_id' => 9001,
                'scope' => 'MEMBER',
                'direction' => 'DEBIT',
                'amount' => 50,
                'balance_before' => 1400,
                'balance_after' => 1350,
                'ref_type' => 'LOTTO_BET',
                'ref_id' => 1001,
                'ref_code' => '1001',
                'group_code' => 'LOTTO_BET_1001',
                'status' => 'SUCCESS',
                'description' => 'หักเงินจากการแทงหวย',
                'meta' => json_encode(['ticket_id' => 1001], JSON_UNESCAPED_UNICODE),
                'created_at' => '2026-04-03 12:00:00',
                'updated_at' => '2026-04-03 12:00:00',
            ],
            [
                'id' => 4,
                'member_id' => 9001,
                'scope' => 'MEMBER',
                'direction' => 'CREDIT',
                'amount' => 50,
                'balance_before' => 1350,
                'balance_after' => 1400,
                'ref_type' => 'LOTTO_CANCEL',
                'ref_id' => 1001,
                'ref_code' => '1001',
                'group_code' => 'LOTTO_CANCEL_1001',
                'status' => 'SUCCESS',
                'description' => 'คืนเงินจากการยกเลิกโพยหวย',
                'meta' => json_encode(['ticket_id' => 1001, 'reason' => 'สมาชิกกดยกเลิกเอง'], JSON_UNESCAPED_UNICODE),
                'created_at' => '2026-04-04 13:00:00',
                'updated_at' => '2026-04-04 13:00:00',
            ],
            [
                'id' => 5,
                'member_id' => 9001,
                'scope' => 'MEMBER',
                'direction' => 'CREDIT',
                'amount' => 30,
                'balance_before' => 1400,
                'balance_after' => 1430,
                'ref_type' => 'TRANFT',
                'ref_id' => 701,
                'ref_code' => 'tranBonus:FASTSTART:701',
                'group_code' => 'TRANFT_701',
                'status' => 'SUCCESS',
                'description' => 'Transfer bonus to wallet/game via tranBonus',
                'meta' => json_encode(['event' => 'FASTSTART'], JSON_UNESCAPED_UNICODE),
                'created_at' => '2026-04-05 09:00:00',
                'updated_at' => '2026-04-05 09:00:00',
            ],
            [
                'id' => 6,
                'member_id' => 9001,
                'scope' => 'MEMBER',
                'direction' => 'CREDIT',
                'amount' => 20,
                'balance_before' => 1430,
                'balance_after' => 1450,
                'ref_type' => 'TRANCB',
                'ref_id' => 702,
                'ref_code' => 'tranBonus:CASHBACK:702',
                'group_code' => 'TRANCB_702',
                'status' => 'SUCCESS',
                'description' => 'Transfer bonus to wallet/game via tranBonus',
                'meta' => json_encode(['event' => 'CASHBACK'], JSON_UNESCAPED_UNICODE),
                'created_at' => '2026-04-05 14:00:00',
                'updated_at' => '2026-04-05 14:00:00',
            ],
        ]);
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');

        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('member_id');
            $table->string('scope', 32)->default('MEMBER');
            $table->string('direction', 16);
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('balance_before', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->string('ref_type', 32);
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('ref_code', 64)->nullable();
            $table->string('group_code', 64)->nullable();
            $table->string('status', 16)->default('SUCCESS');
            $table->string('description')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('draw_id');
            $table->timestamps();
        });
    }
}
