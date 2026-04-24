<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Services\SettlementService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LottoWinningSettlementMaterializationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
        $this->seedBaseData();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('logs');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('lotto_winnings');
        Schema::dropIfExists('settlement_batches');
        Schema::dropIfExists('lotto_ticket_items');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');
        Schema::dropIfExists('members');

        parent::tearDown();
    }

    public function test_settlement_creates_batch_and_materialized_winnings_and_rerun_does_not_duplicate(): void
    {
        $draw = LottoDraw::query()->findOrFail(101);

        $service = app(SettlementService::class);

        $summary1 = $service->settleDraw($draw, [
            'top_3' => '123',
            'top_2' => '23',
            'bottom_2' => '45',
        ]);

        $this->assertSame(1, (int) ($summary1['winning_item_count'] ?? 0));
        $this->assertDatabaseCount('settlement_batches', 1);
        $this->assertDatabaseCount('lotto_winnings', 1);

        $summary2 = $service->settleDraw($draw, [
            'top_3' => '123',
            'top_2' => '23',
            'bottom_2' => '45',
        ]);

        $this->assertSame(1, (int) ($summary2['winning_item_count'] ?? 0));
        $this->assertDatabaseCount('settlement_batches', 1);
        $this->assertDatabaseCount('lotto_winnings', 1);

        $winning = \DB::table('lotto_winnings')->first();
        $this->assertSame(101, (int) $winning->draw_id);
        $this->assertSame(5001, (int) $winning->bet_item_id);
        $this->assertSame('credited', (string) $winning->status);
    }

    private function prepareSchema(): void
    {
        Schema::create('logs', function (Blueprint $table): void {
            $table->bigIncrements('code');
            $table->unsignedBigInteger('emp_code')->nullable();
            $table->string('mode')->nullable();
            $table->string('menu')->nullable();
            $table->string('record')->nullable();
            $table->longText('item_before')->nullable();
            $table->longText('item')->nullable();
            $table->string('ip')->nullable();
            $table->string('user_create')->nullable();
            $table->dateTime('date_create')->nullable();
            $table->dateTime('date_update')->nullable();
        });

        Schema::create('members', function (Blueprint $table): void {
            $table->unsignedInteger('code')->primary();
            $table->string('user_name')->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->dateTime('date_update')->nullable();
        });

        Schema::create('lotto_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name')->nullable();
        });

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->string('code');
            $table->string('name')->nullable();
            $table->boolean('is_enabled')->default(true);
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
            $table->dateTime('open_at')->nullable();
            $table->dateTime('close_at')->nullable();
            $table->dateTime('result_at')->nullable();
            $table->enum('status', ['draft', 'open', 'closed', 'resulted'])->default('closed');
            $table->json('result_number')->nullable();
            $table->string('result_hash')->nullable();
            $table->string('result_fetch_status')->nullable();
            $table->text('result_fetch_error')->nullable();
            $table->dateTime('result_applied_at')->nullable();
            $table->dateTime('result_fetched_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('member_id');
            $table->unsignedBigInteger('draw_id');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('total_win_amount', 12, 2)->default(0);
            $table->enum('status', ['active', 'cancelled', 'resulted'])->default('active');
            $table->timestamps();
        });

        Schema::create('lotto_ticket_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('bet_type');
            $table->string('number');
            $table->decimal('amount', 10, 2);
            $table->decimal('payout_at_time', 8, 2);
            $table->decimal('potential_win_amount_at_time', 12, 2)->nullable();
            $table->string('result_status')->nullable();
            $table->decimal('win_amount', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('member_id');
            $table->string('scope')->default('MEMBER');
            $table->unsignedBigInteger('game_user_id')->nullable();
            $table->string('direction');
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('balance_before', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->string('ref_type');
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('ref_code')->nullable();
            $table->string('group_code')->nullable();
            $table->unsignedBigInteger('related_txn_id')->nullable();
            $table->string('status')->default('SUCCESS');
            $table->string('description')->nullable();
            $table->text('meta')->nullable();
            $table->string('created_by_type')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });

        Schema::create('settlement_batches', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('draw_id');
            $table->date('draw_date')->nullable();
            $table->string('lottery_type');
            $table->string('market')->nullable();
            $table->string('mode');
            $table->string('status');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->unsignedInteger('total_bets_processed')->default(0);
            $table->unsignedInteger('total_winning_records')->default(0);
            $table->decimal('total_stake', 14, 2)->default(0);
            $table->decimal('total_payout', 14, 2)->default(0);
            $table->text('error_message')->nullable();
            $table->string('triggered_by')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamps();
        });

        Schema::create('lotto_winnings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('draw_id');
            $table->unsignedBigInteger('bet_id');
            $table->unsignedBigInteger('bet_item_id');
            $table->string('ticket_no')->nullable();
            $table->unsignedInteger('user_id');
            $table->string('username')->nullable();
            $table->string('lottery_type');
            $table->string('market')->nullable();
            $table->string('bet_type');
            $table->string('number');
            $table->decimal('stake', 14, 2);
            $table->decimal('odds', 10, 4);
            $table->decimal('payout', 14, 2)->nullable();
            $table->decimal('net_profit', 14, 2)->nullable();
            $table->string('result_number')->nullable();
            $table->string('matched_rule')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('settlement_batch_id');
            $table->dateTime('settled_at')->nullable();
            $table->dateTime('credited_at')->nullable();
            $table->timestamps();
            $table->unique(['draw_id', 'bet_item_id']);
        });
    }

    private function seedBaseData(): void
    {
        \DB::table('members')->insert([
            'code' => 52,
            'user_name' => 'member52',
            'balance' => 0,
            'date_update' => now(),
        ]);

        \DB::table('lotto_groups')->insert([
            'id' => 1,
            'code' => 'thai',
            'name' => 'Thai',
        ]);

        \DB::table('lotto_markets')->insert([
            'id' => 1,
            'group_id' => 1,
            'code' => 'gsb',
            'name' => 'GSB',
            'is_enabled' => 1,
        ]);

        \DB::table('lotto_draws')->insert([
            'id' => 101,
            'market_id' => 1,
            'draw_date' => now()->toDateString(),
            'open_at' => now()->subHour(),
            'close_at' => now()->subMinutes(30),
            'status' => 'closed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('lotto_tickets')->insert([
            'id' => 1001,
            'member_id' => 52,
            'draw_id' => 101,
            'total_amount' => 100,
            'total_win_amount' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('lotto_ticket_items')->insert([
            'id' => 5001,
            'ticket_id' => 1001,
            'bet_type' => 'top_3',
            'number' => '123',
            'amount' => 100,
            'payout_at_time' => 10,
            'result_status' => null,
            'win_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
