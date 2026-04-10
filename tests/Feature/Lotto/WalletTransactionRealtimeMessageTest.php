<?php

namespace Tests\Feature\Lotto;

use App\Events\RealtimeMemberActivityUpdated;
use Gametech\Lotto\Services\WalletTransactionService;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class WalletTransactionRealtimeMessageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('members');

        Schema::create('members', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
            $table->decimal('balance', 14, 2)->default(0);
            $table->dateTime('date_update')->nullable();
        });

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id')->nullable();
            $table->date('draw_date')->nullable();
            $table->string('status')->nullable();
            $table->json('result_number')->nullable();
            $table->dateTime('result_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('draw_id')->nullable();
            $table->string('status')->nullable();
            $table->decimal('total_win_amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('member_id');
            $table->string('scope', 16)->nullable();
            $table->unsignedBigInteger('game_user_id')->nullable();
            $table->string('direction', 16);
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_before', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->string('ref_type', 32);
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('ref_code')->nullable();
            $table->string('group_code')->nullable();
            $table->unsignedBigInteger('related_txn_id')->nullable();
            $table->string('status', 16)->nullable();
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->string('created_by_type', 16)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('members');

        Mockery::close();

        parent::tearDown();
    }

    public function test_credit_member_balance_broadcasts_targeted_lotto_win_message(): void
    {
        \DB::table('members')->insert([
            'code' => 7,
            'balance' => 100,
            'date_update' => now(),
        ]);

        \DB::table('lotto_markets')->insert([
            'id' => 3,
            'name' => 'หวยออมสิน',
        ]);

        \DB::table('lotto_draws')->insert([
            'id' => 18,
            'market_id' => 3,
            'draw_date' => '2026-04-10',
            'status' => 'resulted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $broadcastFactory = Mockery::mock(BroadcastFactory::class);
        $broadcastFactory->shouldReceive('event')
            ->once()
            ->with(Mockery::on(function ($event): bool {
                return $event instanceof RealtimeMemberActivityUpdated
                    && $event->memberCode === 7
                    && $event->method === 'lotto'
                    && $event->event === 'lotto.ticket_won'
                    && $event->message === 'โพยหวยของคุณถูกรางวัล 540.00 บาท (หวยออมสิน งวดวันที่ 2026-04-10)'
                    && (float) ($event->data['balance'] ?? 0) === 640.0
                    && (int) ($event->data['draw_id'] ?? 0) === 18
                    && (int) ($event->data['ticket_id'] ?? 0) === 41;
            }))
            ->andReturnSelf();
        $this->app->instance(BroadcastFactory::class, $broadcastFactory);

        $service = app(WalletTransactionService::class);
        $service->creditMemberBalance(
            memberId: 7,
            amount: 540,
            refType: 'LOTTO_SETTLE_WIN',
            refId: 41,
            refCode: '18',
            groupCode: 'LOTTO_SETTLE_DRAW_18',
            meta: [
                'draw_id' => 18,
                'ticket_id' => 41,
            ],
            createdByType: 'system',
            createdById: null,
            description: 'จ่ายรางวัลหวย'
        );

        $this->assertSame(640.0, (float) \DB::table('members')->where('code', 7)->value('balance'));
    }
}
