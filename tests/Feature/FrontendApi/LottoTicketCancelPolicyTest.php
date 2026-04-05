<?php

namespace Tests\Feature\FrontendApi;

use App\Services\Dashboard\DashboardSummarySyncService;
use Gametech\FrontendApi\Http\Controllers\Api\V1\LottoController;
use Gametech\Member\Models\Member;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LottoTicketCancelPolicyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'log']);
        $this->prepareSchema();
        $this->app->instance(DashboardSummarySyncService::class, new class {
            public function dispatchForModelChange(string $domain, $model, array $overrideSections = []): void
            {
            }
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('logs');
        Schema::dropIfExists('banks');
        Schema::dropIfExists('lotto_number_exposures');
        Schema::dropIfExists('lotto_ticket_items');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('members');

        parent::tearDown();
    }

    public function test_cancel_succeeds_when_member_is_under_daily_limit_and_before_cutoff(): void
    {
        Carbon::setTestNow('2026-04-05 12:00:00');

        $member = $this->seedMember(9001, 500);
        $this->seedOpenDraw(drawId: 2001, marketId: 301, closeAt: '2026-04-05 12:30:00');
        $this->seedTicket(ticketId: 3001, memberId: 9001, drawId: 2001, status: 'active');
        $this->seedTicketItem(ticketId: 3001, betType: 'top_2', number: '46', amount: 50);
        $this->seedExposure(drawId: 2001, betType: 'top_2', number: '46', soldAmount: 50);
        $this->seedBetWalletTransaction(memberId: 9001, ticketId: 3001, amount: 50);

        $response = $this->cancelResponse($member, 3001);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'ยกเลิกโพยสำเร็จ');

        $this->assertSame('cancelled', DB::table('lotto_tickets')->where('id', 3001)->value('status'));
        $this->assertSame(0.0, (float) DB::table('lotto_number_exposures')->where('draw_id', 2001)->where('bet_type', 'top_2')->where('number', '46')->value('sold_amount'));
        $this->assertSame(550.0, (float) DB::table('members')->where('code', 9001)->value('balance'));
        $this->assertSame(1, DB::table('wallet_transactions')->where('member_id', 9001)->where('ref_type', 'LOTTO_CANCEL')->count());
    }

    public function test_cancel_rejects_when_member_already_cancelled_four_tickets_today(): void
    {
        Carbon::setTestNow('2026-04-05 12:00:00');

        $member = $this->seedMember(9002, 100);
        $this->seedOpenDraw(drawId: 2002, marketId: 302, closeAt: '2026-04-05 13:00:00');
        $this->seedTicket(ticketId: 3002, memberId: 9002, drawId: 2002, status: 'active');
        $this->seedTicketItem(ticketId: 3002, betType: 'top_2', number: '47', amount: 20);
        $this->seedExposure(drawId: 2002, betType: 'top_2', number: '47', soldAmount: 20);

        foreach ([4001, 4002, 4003, 4004] as $ticketId) {
            $this->seedTicket(
                ticketId: $ticketId,
                memberId: 9002,
                drawId: 2002,
                status: 'cancelled',
                cancelledAt: '2026-04-05 09:00:00'
            );
            $this->seedCancelWalletTransaction(
                memberId: 9002,
                ticketId: $ticketId,
                createdByType: 'member',
                createdById: 9002,
                createdAt: '2026-04-05 09:00:00'
            );
        }

        $response = $this->cancelResponse($member, 3002);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'สมาชิกยกเลิกโพยได้ไม่เกินวันละ 4 ครั้ง');
        $this->assertSame('active', DB::table('lotto_tickets')->where('id', 3002)->value('status'));
    }

    public function test_cancel_rejects_when_less_than_ten_minutes_before_close(): void
    {
        Carbon::setTestNow('2026-04-05 12:21:00');

        $member = $this->seedMember(9003, 100);
        $this->seedOpenDraw(drawId: 2003, marketId: 303, closeAt: '2026-04-05 12:30:00');
        $this->seedTicket(ticketId: 3003, memberId: 9003, drawId: 2003, status: 'active');
        $this->seedTicketItem(ticketId: 3003, betType: 'bottom_2', number: '48', amount: 20);
        $this->seedExposure(drawId: 2003, betType: 'bottom_2', number: '48', soldAmount: 20);

        $response = $this->cancelResponse($member, 3003);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'ยกเลิกโพยได้ก่อนเวลาปิดรับอย่างน้อย 10 นาทีเท่านั้น');
        $this->assertSame('active', DB::table('lotto_tickets')->where('id', 3003)->value('status'));
    }

    public function test_cancel_does_not_count_admin_or_system_cancellations_toward_member_daily_quota(): void
    {
        Carbon::setTestNow('2026-04-05 12:00:00');

        $member = $this->seedMember(9004, 100);
        $this->seedOpenDraw(drawId: 2004, marketId: 304, closeAt: '2026-04-05 13:00:00');
        $this->seedTicket(ticketId: 3004, memberId: 9004, drawId: 2004, status: 'active');
        $this->seedTicketItem(ticketId: 3004, betType: 'top_2', number: '49', amount: 20);
        $this->seedExposure(drawId: 2004, betType: 'top_2', number: '49', soldAmount: 20);
        $this->seedBetWalletTransaction(memberId: 9004, ticketId: 3004, amount: 20);

        foreach ([4101, 4102, 4103, 4104] as $ticketId) {
            $this->seedTicket(
                ticketId: $ticketId,
                memberId: 9004,
                drawId: 2004,
                status: 'cancelled',
                cancelledAt: '2026-04-05 09:00:00'
            );
        }

        $this->seedCancelWalletTransaction(memberId: 9004, ticketId: 4101, createdByType: 'admin', createdById: 7001, createdAt: '2026-04-05 09:00:00');
        $this->seedCancelWalletTransaction(memberId: 9004, ticketId: 4102, createdByType: 'admin', createdById: 7001, createdAt: '2026-04-05 09:05:00');
        $this->seedCancelWalletTransaction(memberId: 9004, ticketId: 4103, createdByType: 'system', createdById: null, createdAt: '2026-04-05 09:10:00');
        $this->seedCancelWalletTransaction(memberId: 9004, ticketId: 4104, createdByType: 'admin', createdById: 7001, createdAt: '2026-04-05 09:15:00');

        $response = $this->cancelResponse($member, 3004);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'ยกเลิกโพยสำเร็จ');
        $this->assertSame('cancelled', DB::table('lotto_tickets')->where('id', 3004)->value('status'));
    }

    private function cancelResponse(Member $member, int $ticketId)
    {
        $request = Request::create('/api/v1/lotto/tickets/' . $ticketId . '/cancel', 'POST');
        $request->setUserResolver(static fn (?string $guard = null) => $guard === 'customer' ? $member : null);

        return $this->createTestResponse(
            app(LottoController::class)->cancel($request, $ticketId)
        );
    }

    private function seedMember(int $memberCode, float $balance): Member
    {
        DB::table('members')->insert([
            'code' => $memberCode,
            'name' => 'Member ' . $memberCode,
            'balance' => $balance,
            'date_create' => now(),
            'date_update' => now(),
        ]);

        $member = new Member();
        $member->code = $memberCode;
        $member->name = 'Member ' . $memberCode;
        $member->exists = true;

        return $member;
    }

    private function seedOpenDraw(int $drawId, int $marketId, string $closeAt): void
    {
        DB::table('lotto_markets')->insert([
            'id' => $marketId,
            'group_id' => 1,
            'name' => 'Market ' . $marketId,
            'is_enabled' => 1,
        ]);

        DB::table('lotto_draws')->insert([
            'id' => $drawId,
            'market_id' => $marketId,
            'draw_date' => '2026-04-05',
            'open_at' => '2026-04-05 09:00:00',
            'close_at' => $closeAt,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedTicket(int $ticketId, int $memberId, int $drawId, string $status, ?string $cancelledAt = null): void
    {
        DB::table('lotto_tickets')->insert([
            'id' => $ticketId,
            'member_id' => $memberId,
            'draw_id' => $drawId,
            'total_amount' => 50,
            'total_bet_amount' => 50,
            'total_discount_amount' => 0,
            'total_net_amount' => 50,
            'total_win_amount' => 0,
            'status' => $status,
            'cancelled_at' => $cancelledAt,
            'refund_amount' => $status === 'cancelled' ? 50 : 0,
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
            'balance_before' => 550,
            'balance_after' => 500,
            'ref_type' => 'LOTTO_BET',
            'ref_id' => $ticketId,
            'ref_code' => (string) $ticketId,
            'group_code' => 'LOTTO_BET_' . $ticketId,
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

    private function seedCancelWalletTransaction(
        int $memberId,
        int $ticketId,
        string $createdByType,
        ?int $createdById,
        string $createdAt
    ): void {
        DB::table('wallet_transactions')->insert([
            'member_id' => $memberId,
            'scope' => 'MEMBER',
            'game_user_id' => null,
            'direction' => 'CREDIT',
            'amount' => 50,
            'balance_before' => 450,
            'balance_after' => 500,
            'ref_type' => 'LOTTO_CANCEL',
            'ref_id' => $ticketId,
            'ref_code' => (string) $ticketId,
            'group_code' => 'LOTTO_CANCEL_' . $ticketId,
            'related_txn_id' => null,
            'status' => 'SUCCESS',
            'description' => 'คืนเงินจากการยกเลิกโพยหวย',
            'meta' => null,
            'created_by_type' => $createdByType,
            'created_by_id' => $createdById,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
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

        Schema::create('members', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
            $table->string('name')->nullable();
            $table->decimal('balance', 12, 2)->default(0);
            $table->dateTime('date_create')->nullable();
            $table->dateTime('date_update')->nullable();
        });

        Schema::create('banks', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
            $table->string('name')->nullable();
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
