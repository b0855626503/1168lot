<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\YeekeeRound;
use Gametech\Lotto\Services\YeekeeShootingRewardService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class YeekeeShootingRewardServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSchema();
        config()->set('yeekee.reward_enabled', true);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('members');
        Schema::dropIfExists('yeekee_shoot_reward_logs');
        Schema::dropIfExists('yeekee_shoots');
        Schema::dropIfExists('yeekee_market_settings');
        Schema::dropIfExists('yeekee_rounds');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');

        parent::tearDown();
    }

    public function test_it_pays_once_and_is_idempotent(): void
    {
        $this->seedBaseData(
            snapshot: [
                'reward_enabled' => true,
                'reward_config' => [
                    'enabled' => true,
                    'type' => 'FIXED_AMOUNT_BY_POSITION',
                    'position' => 16,
                    'amount' => 120,
                    'currency' => 'THB',
                    'pay_on' => 'SETTLED_ONLY',
                ],
                'formula_config' => ['preset' => 'SHOOTS_SUM_MINUS_POSITION'],
            ]
        );

        $service = app(YeekeeShootingRewardService::class);
        $first = $service->applyForRound($this->round(), $this->draw());
        $second = $service->applyForRound($this->round(), $this->draw());

        $this->assertSame('paid', (string) $first['status']);
        $this->assertSame('already_paid', (string) $second['status']);
        $this->assertSame(1, DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());
        $this->assertSame(1, DB::table('yeekee_shoot_reward_logs')->count());
    }

    public function test_it_skips_when_kill_switch_disabled(): void
    {
        config()->set('yeekee.reward_enabled', false);
        $this->seedBaseData(
            snapshot: [
                'reward_enabled' => true,
                'reward_config' => [
                    'enabled' => true,
                    'type' => 'FIXED_AMOUNT_BY_POSITION',
                    'position' => 16,
                    'amount' => 100,
                    'currency' => 'THB',
                    'pay_on' => 'SETTLED_ONLY',
                ],
            ]
        );

        $result = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw());

        $this->assertSame('skipped', (string) $result['status']);
        $this->assertSame('emergency_kill_switch', (string) $result['reason']);
        $this->assertSame(0, DB::table('wallet_transactions')->count());
    }

    public function test_snapshot_policy_has_priority_over_market_setting(): void
    {
        $this->seedBaseData(
            snapshot: [
                'reward_enabled' => false,
                'reward_config' => [
                    'enabled' => true,
                    'type' => 'FIXED_AMOUNT_BY_POSITION',
                    'position' => 16,
                    'amount' => 100,
                    'currency' => 'THB',
                    'pay_on' => 'SETTLED_ONLY',
                ],
            ],
            marketRewardEnabled: true
        );

        $result = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw());

        $this->assertSame('skipped', (string) $result['status']);
        $this->assertSame('round_snapshot', (string) $result['policy_source']);
        $this->assertSame(0, DB::table('wallet_transactions')->count());
    }

    public function test_it_falls_back_to_market_setting_when_snapshot_missing(): void
    {
        $this->seedBaseData(snapshot: null, marketRewardEnabled: true);

        DB::table('yeekee_market_settings')->insert([
            'market_id' => 106,
            'reward_enabled' => 1,
            'reward_config' => json_encode([
                'enabled' => true,
                'type' => 'FIXED_AMOUNT_BY_POSITION',
                'position' => 16,
                'amount' => 100,
                'currency' => 'THB',
                'pay_on' => 'SETTLED_ONLY',
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw());

        $this->assertSame('paid', (string) $result['status']);
        $this->assertSame('market_setting', (string) $result['policy_source']);
    }

    public function test_it_marks_invalid_policy_for_unsupported_type_or_amount(): void
    {
        $this->seedBaseData(
            snapshot: [
                'reward_enabled' => true,
                'reward_config' => [
                    'enabled' => true,
                    'type' => 'PERCENT_OF_POOL_BY_POSITION',
                    'position' => 16,
                    'amount' => 0,
                    'currency' => 'THB',
                    'pay_on' => 'SETTLED_ONLY',
                ],
                'formula_config' => ['preset' => 'SHOOTS_SUM_ONLY'],
            ]
        );

        $result = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw(), [
            'formula_preset' => 'SHOOTS_SUM_ONLY',
        ]);

        $this->assertSame('invalid_policy', (string) $result['status']);
        $this->assertSame(0, DB::table('wallet_transactions')->count());
    }

    public function test_it_does_not_crash_with_unknown_formula_context(): void
    {
        $this->seedBaseData(
            snapshot: [
                'reward_enabled' => true,
                'reward_config' => [
                    'enabled' => true,
                    'type' => 'FIXED_AMOUNT_BY_POSITION',
                    'position' => 16,
                    'amount' => 90,
                    'currency' => 'THB',
                    'pay_on' => 'SETTLED_ONLY',
                ],
                'formula_config' => ['preset' => 'NEW_FORMULA_PRESET'],
            ]
        );

        $result = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw(), [
            'formula_preset' => 'NEW_FORMULA_PRESET',
        ]);

        $this->assertSame('paid', (string) $result['status']);
    }

    private function seedBaseData(?array $snapshot, bool $marketRewardEnabled = false): void
    {
        DB::table('members')->insert([
            'code' => 7001,
            'balance' => 1000,
            'date_update' => now(),
        ]);

        DB::table('lotto_markets')->insert([
            'id' => 106,
            'name' => 'Yeekee',
            'result_mode' => 'yeekee',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lotto_draws')->insert([
            'id' => 3003,
            'market_id' => 106,
            'draw_date' => '2026-05-07',
            'status' => 'resulted',
            'result_number' => json_encode(['top_3' => '123', 'bottom_2' => '23']),
            'result_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_rounds')->insert([
            'id' => 3,
            'market_id' => 106,
            'lotto_draw_id' => 3003,
            'round_date' => '2026-05-07',
            'round_no' => 3,
            'bet_open_at' => now(),
            'bet_close_at' => now(),
            'shoot_open_at' => now(),
            'shoot_close_at' => now(),
            'result_compute_at' => now(),
            'expected_settlement_deadline_at' => now(),
            'status' => 'resulted',
            'config_snapshot_json' => $snapshot === null ? null : json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_shoots')->insert([
            'id' => 4016,
            'yeekee_round_id' => 3,
            'lotto_draw_id' => 3003,
            'market_id' => 106,
            'member_id' => 7001,
            'position' => 16,
            'number_text' => '12345',
            'number_value' => 12345,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_market_settings')->insert([
            'market_id' => 106,
            'reward_enabled' => $marketRewardEnabled ? 1 : 0,
            'reward_config' => json_encode([
                'enabled' => true,
                'type' => 'FIXED_AMOUNT_BY_POSITION',
                'position' => 16,
                'amount' => 50,
                'currency' => 'THB',
                'pay_on' => 'SETTLED_ONLY',
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function draw(): LottoDraw
    {
        return LottoDraw::query()->findOrFail(3003);
    }

    private function round(): YeekeeRound
    {
        return YeekeeRound::query()->findOrFail(3);
    }

    private function prepareSchema(): void
    {
        Schema::create('members', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
            $table->decimal('balance', 14, 2)->default(0);
            $table->dateTime('date_update')->nullable();
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

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('result_mode', 32)->nullable();
            $table->timestamps();
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->date('draw_date')->nullable();
            $table->string('status', 32)->nullable();
            $table->json('result_number')->nullable();
            $table->dateTime('result_at')->nullable();
            $table->timestamps();
        });

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
            $table->json('config_snapshot_json')->nullable();
            $table->timestamps();
        });

        Schema::create('yeekee_market_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->json('reward_config')->nullable();
            $table->boolean('reward_enabled')->default(false);
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
            $table->unique(['yeekee_round_id', 'member_id', 'position'], 'yeekee_reward_round_member_position_unique');
        });
    }
}
