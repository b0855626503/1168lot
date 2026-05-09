<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Services\ResultCorrectionApplyService;
use Gametech\Lotto\Services\ResultCorrectionPreviewService;
use Gametech\Lotto\Services\ResultCorrectionRetryDebitService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class LottoResultCorrectionWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
        $this->seedData();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lotto_result_correction_items');
        Schema::dropIfExists('lotto_result_corrections');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('logs');
        Schema::dropIfExists('lotto_ticket_items');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');
        Schema::dropIfExists('members');
        parent::tearDown();
    }

    public function test_preview_apply_and_retry_workflow_handles_credit_partial_reverse_and_completion(): void
    {
        $draw = LottoDraw::query()->findOrFail(100);
        $preview = app(ResultCorrectionPreviewService::class)->preview(
            $draw,
            ['top_3' => '999', 'top_2' => '99', 'bottom_2' => '45'],
            'แก้ผลทดสอบ',
            1
        );

        $this->assertSame(2, $preview['summary']['affected_ticket_count']);
        $this->assertSame(1000.0, $preview['summary']['total_credit_amount']);
        $this->assertSame(1000.0, $preview['summary']['total_reverse_amount']);

        $apply = app(ResultCorrectionApplyService::class)->apply((int) $preview['correction_id'], 1);
        $this->assertSame('partial_failed', $apply['status']);
        $this->assertSame(1000.0, $apply['total_credit_amount']);
        $this->assertSame(300.0, $apply['total_reversed_amount']);
        $this->assertSame(700.0, $apply['total_reverse_remaining_amount']);

        $this->assertSame(2, DB::table('wallet_transactions')->count());
        $this->assertSame(1300.0, (float) DB::table('members')->where('code', 2002)->value('balance'));
        $this->assertSame(0.0, (float) DB::table('members')->where('code', 2001)->value('balance'));
        $this->assertSame(0.0, (float) DB::table('lotto_tickets')->where('id', 5001)->value('total_win_amount'));
        $this->assertSame(1000.0, (float) DB::table('lotto_tickets')->where('id', 5002)->value('total_win_amount'));
        $this->assertSame('lose', (string) DB::table('lotto_ticket_items')->where('id', 6001)->value('result_status'));
        $this->assertSame('win', (string) DB::table('lotto_ticket_items')->where('id', 6002)->value('result_status'));
        $this->assertSame(0.0, (float) DB::table('lotto_tickets')->where('id', 5003)->value('total_win_amount'));

        DB::table('members')->where('code', 2001)->update(['balance' => 700, 'date_update' => now()]);
        $retry = app(ResultCorrectionRetryDebitService::class)->retryRemaining((int) $preview['correction_id'], null, 1);
        $this->assertSame('completed', $retry['status']);
        $this->assertSame(0.0, $retry['remaining_amount']);
        $this->assertSame(3, DB::table('wallet_transactions')->count());
    }

    public function test_apply_is_idempotent_after_completed_or_partial_failed(): void
    {
        $draw = LottoDraw::query()->findOrFail(100);
        $preview = app(ResultCorrectionPreviewService::class)->preview(
            $draw,
            ['top_3' => '999', 'top_2' => '99', 'bottom_2' => '45'],
            'idempotent',
            1
        );

        app(ResultCorrectionApplyService::class)->apply((int) $preview['correction_id'], 1);

        $this->expectException(InvalidArgumentException::class);
        app(ResultCorrectionApplyService::class)->apply((int) $preview['correction_id'], 1);
    }

    public function test_preview_can_run_without_persisting_correction_record(): void
    {
        $draw = LottoDraw::query()->findOrFail(100);

        $preview = app(ResultCorrectionPreviewService::class)->preview(
            $draw,
            ['top_3' => '999', 'top_2' => '99', 'bottom_2' => '45'],
            'preview_only',
            1,
            false
        );

        $this->assertNull($preview['correction_id']);
        $this->assertSame(0, DB::table('lotto_result_corrections')->count());
        $this->assertSame(0, DB::table('lotto_result_correction_items')->count());
    }

    public function test_multiple_corrections_can_credit_same_ticket_without_unique_key_collision(): void
    {
        $draw = LottoDraw::query()->findOrFail(100);

        $first = app(ResultCorrectionPreviewService::class)->preview(
            $draw,
            ['top_3' => '999', 'top_2' => '99', 'bottom_2' => '45'],
            'round_1',
            1
        );
        app(ResultCorrectionApplyService::class)->apply((int) $first['correction_id'], 1);

        $draw->refresh();
        $second = app(ResultCorrectionPreviewService::class)->preview(
            $draw,
            ['top_3' => '123', 'top_2' => '23', 'bottom_2' => '45'],
            'round_2',
            1
        );
        app(ResultCorrectionApplyService::class)->apply((int) $second['correction_id'], 1);

        $draw->refresh();
        $third = app(ResultCorrectionPreviewService::class)->preview(
            $draw,
            ['top_3' => '999', 'top_2' => '99', 'bottom_2' => '45'],
            'round_3',
            1
        );
        $thirdApply = app(ResultCorrectionApplyService::class)->apply((int) $third['correction_id'], 1);

        $this->assertIsArray($thirdApply);
        $this->assertGreaterThanOrEqual(1, DB::table('wallet_transactions')
            ->where('ref_type', 'LOTTO_RESULT_CORRECTION_CREDIT')
            ->count());
    }

    private function prepareSchema(): void
    {
        Schema::create('members', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
            $table->decimal('balance', 14, 2)->default(0);
            $table->dateTime('date_update')->nullable();
        });

        Schema::create('lotto_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable();
        });

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->string('result_mode')->default('normal');
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
            $table->dateTime('result_at')->nullable();
            $table->enum('status', ['draft', 'open', 'closed', 'resulted'])->default('draft');
            $table->json('result_number')->nullable();
            $table->string('result_hash')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('draw_id');
            $table->string('status')->default('active');
            $table->decimal('total_win_amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('lotto_ticket_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('bet_type', 32);
            $table->string('number', 32);
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('payout_at_time', 14, 4)->default(0);
            $table->decimal('potential_win_amount_at_time', 14, 2)->nullable();
            $table->string('result_status', 16)->nullable();
            $table->decimal('win_amount', 14, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('scope', 16)->nullable();
            $table->unsignedBigInteger('game_user_id')->nullable();
            $table->string('direction', 16);
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_before', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->string('ref_type', 64);
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('ref_code')->nullable();
            $table->string('group_code')->nullable();
            $table->unsignedBigInteger('related_txn_id')->nullable();
            $table->string('status', 32)->default('SUCCESS');
            $table->string('description')->nullable();
            $table->text('meta')->nullable();
            $table->string('created_by_type', 32)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_result_corrections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('draw_id');
            $table->json('old_result_number')->nullable();
            $table->json('new_result_number')->nullable();
            $table->string('old_result_hash')->nullable();
            $table->string('new_result_hash')->nullable();
            $table->string('source')->default('manual');
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('ticket_count')->default(0);
            $table->unsignedInteger('affected_ticket_count')->default(0);
            $table->unsignedInteger('old_winning_ticket_count')->default(0);
            $table->unsignedInteger('new_winning_ticket_count')->default(0);
            $table->decimal('total_reversed_amount', 14, 2)->default(0);
            $table->decimal('total_reverse_failed_amount', 14, 2)->default(0);
            $table->decimal('total_new_payout_amount', 14, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_result_correction_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('correction_id');
            $table->unsignedBigInteger('draw_id');
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('member_id');
            $table->decimal('old_win_amount', 14, 2)->default(0);
            $table->decimal('new_win_amount', 14, 2)->default(0);
            $table->decimal('initial_member_balance', 14, 2)->default(0);
            $table->decimal('reverse_required_amount', 14, 2)->default(0);
            $table->decimal('reverse_debited_amount', 14, 2)->default(0);
            $table->decimal('reverse_remaining_amount', 14, 2)->default(0);
            $table->decimal('new_credit_amount', 14, 2)->default(0);
            $table->string('status', 32)->default('unchanged');
            $table->unsignedBigInteger('reverse_wallet_txn_id')->nullable();
            $table->unsignedBigInteger('new_credit_wallet_txn_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('emp_code')->nullable();
            $table->string('mode', 16)->nullable();
            $table->string('menu')->nullable();
            $table->string('record')->nullable();
            $table->text('item_before')->nullable();
            $table->text('item')->nullable();
            $table->string('ip', 64)->nullable();
            $table->string('user_create', 64)->nullable();
            $table->dateTime('date_update')->nullable();
            $table->dateTime('date_create')->nullable();
        });
    }

    private function seedData(): void
    {
        DB::table('members')->insert([
            ['code' => 2001, 'balance' => 300, 'date_update' => now()],
            ['code' => 2002, 'balance' => 300, 'date_update' => now()],
        ]);

        DB::table('lotto_groups')->insert([['id' => 1, 'code' => 'main']]);
        DB::table('lotto_markets')->insert([['id' => 10, 'group_id' => 1, 'name' => 'A', 'code' => 'A1', 'result_mode' => 'normal']]);
        DB::table('lotto_draws')->insert([[
            'id' => 100,
            'market_id' => 10,
            'draw_date' => '2026-05-09',
            'result_at' => now(),
            'status' => 'resulted',
            'result_number' => json_encode(['top_3' => '123', 'top_2' => '23', 'bottom_2' => '45']),
            'result_hash' => 'h1',
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        DB::table('lotto_tickets')->insert([
            ['id' => 5001, 'member_id' => 2001, 'draw_id' => 100, 'status' => 'resulted', 'total_win_amount' => 1000, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5002, 'member_id' => 2002, 'draw_id' => 100, 'status' => 'resulted', 'total_win_amount' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5003, 'member_id' => 2002, 'draw_id' => 100, 'status' => 'cancelled', 'total_win_amount' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('lotto_ticket_items')->insert([
            ['id' => 6001, 'ticket_id' => 5001, 'bet_type' => 'top_3', 'number' => '123', 'amount' => 10, 'payout_at_time' => 100, 'potential_win_amount_at_time' => 1000, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6002, 'ticket_id' => 5002, 'bet_type' => 'top_3', 'number' => '999', 'amount' => 10, 'payout_at_time' => 100, 'potential_win_amount_at_time' => 1000, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6003, 'ticket_id' => 5003, 'bet_type' => 'top_3', 'number' => '999', 'amount' => 10, 'payout_at_time' => 100, 'potential_win_amount_at_time' => 1000, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
