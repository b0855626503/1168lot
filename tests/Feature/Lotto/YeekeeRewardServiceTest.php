<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Services\YeekeeRewardService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class YeekeeRewardServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('yeekee_shoot_reward_logs');
        Schema::dropIfExists('yeekee_shoots');
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('yeekee_rounds');
        parent::tearDown();
    }

    public function test_reward_round_is_idempotent_and_applies_min_bet_rule(): void
    {
        DB::table('yeekee_rounds')->insert([
            'id' => 1,
            'market_id' => 11,
            'lotto_draw_id' => 101,
            'round_date' => '2026-04-30',
            'round_no' => 1,
            'bet_open_at' => now(),
            'bet_close_at' => now(),
            'shoot_open_at' => now(),
            'shoot_close_at' => now(),
            'result_compute_at' => now(),
            'expected_settlement_deadline_at' => now(),
            'status' => 'pending_result',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_shoots')->insert([
            'yeekee_round_id' => 1,
            'lotto_draw_id' => 101,
            'market_id' => 11,
            'member_id' => 501,
            'position' => 1,
            'number_text' => '00001',
            'number_value' => 1,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_tickets')->insert([
            'id' => 2001,
            'member_id' => 501,
            'draw_id' => 101,
            'total_net_amount' => 150,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(YeekeeRewardService::class);
        $first = $service->rewardRound(1, [
            ['position' => 1, 'credit_amount' => 10.0],
        ], 100.0);
        $second = $service->rewardRound(1, [
            ['position' => 1, 'credit_amount' => 10.0],
        ], 100.0);

        $this->assertSame(1, $first['credited']);
        $this->assertSame(0, $first['skipped']);
        $this->assertSame(0, $second['credited']);
        $this->assertSame(1, $second['skipped']);
        $this->assertSame(1, DB::table('yeekee_shoot_reward_logs')->count());
    }

    private function prepareSchema(): void
    {
        Schema::create('yeekee_rounds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('lotto_draw_id');
            $table->date('round_date');
            $table->unsignedInteger('round_no')->default(1);
            $table->dateTime('bet_open_at');
            $table->dateTime('bet_close_at');
            $table->dateTime('shoot_open_at');
            $table->dateTime('shoot_close_at');
            $table->dateTime('result_compute_at');
            $table->dateTime('expected_settlement_deadline_at');
            $table->string('status', 32)->default('draft');
            $table->timestamps();
        });

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('member_id');
            $table->unsignedBigInteger('draw_id');
            $table->decimal('total_net_amount', 12, 2)->default(0);
            $table->enum('status', ['active', 'cancelled', 'resulted'])->default('active');
            $table->timestamps();
        });

        Schema::create('yeekee_shoots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('yeekee_round_id');
            $table->unsignedBigInteger('lotto_draw_id');
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedInteger('position');
            $table->string('number_text', 5);
            $table->unsignedInteger('number_value');
            $table->dateTime('submitted_at');
            $table->timestamps();
        });

        Schema::create('yeekee_shoot_reward_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('yeekee_round_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedInteger('position');
            $table->decimal('credit_amount', 12, 2);
            $table->string('reward_ref_type', 32)->default('YEEKEE_SHOOT_REWARD');
            $table->timestamps();
        });
    }
}
