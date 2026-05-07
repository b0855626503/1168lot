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
        Schema::dropIfExists('lotto_tickets');
        Schema::dropIfExists('yeekee_market_settings');
        Schema::dropIfExists('yeekee_rounds');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');

        parent::tearDown();
    }

    public function test_global_enabled_but_market_reward_disabled_does_not_pay(): void
    {
        $this->seedBaseData(snapshot: [
            'reward_enabled' => false,
            'reward_config' => $this->rewardConfig(),
        ]);
        $this->seedTicket(memberId: 7001, drawId: 3003, amount: 150);

        $result = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw());

        $this->assertSame('skipped', (string) $result['status']);
        $this->assertSame('reward_disabled', (string) $result['reason']);
        $this->assertSame(0, DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());
    }

    public function test_round_reward_config_disabled_does_not_pay(): void
    {
        $this->seedBaseData(snapshot: [
            'reward_enabled' => true,
            'reward_config' => $this->rewardConfig(['reward_enabled' => false]),
        ]);
        $this->seedTicket(memberId: 7001, drawId: 3003, amount: 150);

        $result = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw());

        $this->assertSame('skipped', (string) $result['status']);
        $this->assertSame('reward_config_disabled', (string) $result['reason']);
        $this->assertSame(0, DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());
    }

    public function test_multiple_reward_positions_pay_from_existing_reward_config_contract(): void
    {
        $this->seedBaseData(snapshot: [
            'reward_enabled' => true,
            'reward_config' => $this->rewardConfig([
                'reward_positions' => [
                    ['position' => 1, 'credit_amount' => 10],
                    ['position' => 16, 'credit_amount' => 20],
                    ['position' => 100, 'credit_amount' => 30],
                ],
            ]),
        ]);
        $this->seedShoot(id: 4001, position: 1, memberId: 7001);
        $this->seedShoot(id: 4016, position: 16, memberId: 7001);
        $this->seedShoot(id: 4100, position: 100, memberId: 7001);
        $this->seedTicket(memberId: 7001, drawId: 3003, amount: 150);

        $result = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw());

        $this->assertSame('paid', (string) $result['status']);
        $this->assertSame(3, (int) $result['paid_count']);
        $this->assertSame(3, DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());
        $this->assertSame(60.0, (float) DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->sum('amount'));
    }

    public function test_missing_position_skips_only_that_position(): void
    {
        $this->seedBaseData(snapshot: [
            'reward_enabled' => true,
            'reward_config' => $this->rewardConfig([
                'reward_positions' => [
                    ['position' => 1, 'credit_amount' => 10],
                    ['position' => 16, 'credit_amount' => 20],
                ],
            ]),
        ]);
        $this->seedShoot(id: 4001, position: 1, memberId: 7001);
        $this->seedTicket(memberId: 7001, drawId: 3003, amount: 150);

        $result = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw());

        $this->assertSame('paid', (string) $result['status']);
        $this->assertSame(1, (int) $result['paid_count']);
        $this->assertSame(1, (int) $result['skipped_count']);
        $this->assertSame(1, DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());
    }

    public function test_member_shooting_without_valid_bet_in_same_draw_does_not_pay(): void
    {
        $this->seedBaseData(snapshot: [
            'reward_enabled' => true,
            'reward_config' => $this->rewardConfig(),
        ]);
        $this->seedShoot(id: 4016, position: 16, memberId: 7001);

        $result = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw());

        $this->assertSame('skipped', (string) $result['status']);
        $this->assertSame('member_has_no_valid_bet_same_round', (string) $result['reason']);
        $this->assertSame(0, DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());
    }

    public function test_member_betting_different_draw_does_not_pay(): void
    {
        $this->seedBaseData(snapshot: [
            'reward_enabled' => true,
            'reward_config' => $this->rewardConfig(),
        ]);
        $this->seedShoot(id: 4016, position: 16, memberId: 7001);
        $this->seedTicket(memberId: 7001, drawId: 3004, amount: 150);

        $result = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw());

        $this->assertSame('skipped', (string) $result['status']);
        $this->assertSame('member_has_no_valid_bet_same_round', (string) $result['reason']);
        $this->assertSame(0, DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());
    }

    public function test_min_bet_amount_passes_and_fails_from_total_net_amount(): void
    {
        $this->seedBaseData(snapshot: [
            'reward_enabled' => true,
            'reward_config' => $this->rewardConfig(['min_bet_amount' => 100]),
        ]);
        $this->seedShoot(id: 4016, position: 16, memberId: 7001);
        $this->seedTicket(memberId: 7001, drawId: 3003, amount: 99);

        $failed = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw());
        $this->assertSame('min_bet_amount_not_met', (string) $failed['reason']);
        $this->assertSame(0, DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());

        DB::table('lotto_tickets')->where('id', 9001)->update(['total_net_amount' => 100]);
        $paid = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw());

        $this->assertSame('paid', (string) $paid['status']);
        $this->assertSame(1, DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());
    }

    public function test_retry_apply_is_idempotent_and_uses_reward_ref_type(): void
    {
        $this->seedBaseData(snapshot: [
            'reward_enabled' => true,
            'reward_config' => $this->rewardConfig(),
        ]);
        $this->seedShoot(id: 4016, position: 16, memberId: 7001);
        $this->seedTicket(memberId: 7001, drawId: 3003, amount: 150);

        $service = app(YeekeeShootingRewardService::class);
        $first = $service->applyForRound($this->round(), $this->draw());
        $second = $service->applyForRound($this->round(), $this->draw());

        $this->assertSame('paid', (string) $first['status']);
        $this->assertSame('already_paid', (string) $second['status']);
        $this->assertStringStartsWith('YEEKEE_SHOOT_REWARD:', (string) $first['idempotency_key']);
        $this->assertSame(1, DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());
        $this->assertSame('YEEKEE_SHOOT_REWARD', (string) DB::table('wallet_transactions')->value('ref_type'));
    }

    public function test_max_rewards_per_member_per_round_limits_rewards(): void
    {
        $this->seedBaseData(snapshot: [
            'reward_enabled' => true,
            'reward_config' => $this->rewardConfig([
                'max_rewards_per_member_per_round' => 1,
                'reward_positions' => [
                    ['position' => 1, 'credit_amount' => 10],
                    ['position' => 16, 'credit_amount' => 20],
                ],
            ]),
        ]);
        $this->seedShoot(id: 4001, position: 1, memberId: 7001);
        $this->seedShoot(id: 4016, position: 16, memberId: 7001);
        $this->seedTicket(memberId: 7001, drawId: 3003, amount: 150);

        $result = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw());

        $this->assertSame(1, (int) $result['paid_count']);
        $this->assertSame(1, (int) $result['skipped_count']);
        $this->assertSame(1, DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());
    }

    public function test_reward_scope_per_market_per_day_limits_same_market_only(): void
    {
        $snapshot = [
            'reward_enabled' => true,
            'reward_config' => $this->rewardConfig([
                'reward_scope' => 'per_market_per_day',
                'max_rewards_per_member_per_day' => 1,
            ]),
        ];
        $this->seedBaseData(snapshot: $snapshot);
        $this->seedRoundAndDraw(roundId: 4, drawId: 3004, marketId: 106, drawDate: '2026-05-07', snapshot: $snapshot);
        $this->seedRoundAndDraw(roundId: 5, drawId: 3005, marketId: 107, drawDate: '2026-05-07', snapshot: $snapshot);
        $this->seedShoot(id: 4016, roundId: 3, drawId: 3003, marketId: 106, position: 16, memberId: 7001);
        $this->seedShoot(id: 4017, roundId: 4, drawId: 3004, marketId: 106, position: 16, memberId: 7001);
        $this->seedShoot(id: 4018, roundId: 5, drawId: 3005, marketId: 107, position: 16, memberId: 7001);
        $this->seedTicket(memberId: 7001, drawId: 3003, amount: 150);
        $this->seedTicket(memberId: 7001, drawId: 3004, amount: 150, id: 9002);
        $this->seedTicket(memberId: 7001, drawId: 3005, amount: 150, id: 9003);

        $service = app(YeekeeShootingRewardService::class);
        $first = $service->applyForRound($this->round(3), $this->draw(3003));
        $sameMarket = $service->applyForRound($this->round(4), $this->draw(3004));
        $differentMarket = $service->applyForRound($this->round(5), $this->draw(3005));

        $this->assertSame('paid', (string) $first['status']);
        $this->assertSame('max_rewards_per_member_per_day_reached', (string) $sameMarket['reason']);
        $this->assertSame('paid', (string) $differentMarket['status']);
        $this->assertSame(2, DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());
    }

    public function test_reward_scope_global_per_day_limits_across_markets(): void
    {
        $snapshot = [
            'reward_enabled' => true,
            'reward_config' => $this->rewardConfig([
                'reward_scope' => 'global_per_day',
                'max_rewards_per_member_per_day' => 1,
            ]),
        ];
        $this->seedBaseData(snapshot: $snapshot);
        $this->seedRoundAndDraw(roundId: 5, drawId: 3005, marketId: 107, drawDate: '2026-05-07', snapshot: $snapshot);
        $this->seedShoot(id: 4016, roundId: 3, drawId: 3003, marketId: 106, position: 16, memberId: 7001);
        $this->seedShoot(id: 4018, roundId: 5, drawId: 3005, marketId: 107, position: 16, memberId: 7001);
        $this->seedTicket(memberId: 7001, drawId: 3003, amount: 150);
        $this->seedTicket(memberId: 7001, drawId: 3005, amount: 150, id: 9003);

        $service = app(YeekeeShootingRewardService::class);
        $first = $service->applyForRound($this->round(3), $this->draw(3003));
        $second = $service->applyForRound($this->round(5), $this->draw(3005));

        $this->assertSame('paid', (string) $first['status']);
        $this->assertSame('max_rewards_per_member_per_day_reached', (string) $second['reason']);
        $this->assertSame(1, DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());
    }

    public function test_snapshot_has_priority_and_fallback_market_setting_for_legacy_rounds(): void
    {
        $this->seedBaseData(snapshot: [
            'reward_enabled' => false,
            'reward_config' => $this->rewardConfig(),
        ], marketRewardEnabled: true);
        $this->seedShoot(id: 4016, position: 16, memberId: 7001);
        $this->seedTicket(memberId: 7001, drawId: 3003, amount: 150);

        $snapshotResult = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw());
        $this->assertSame('round_snapshot', (string) $snapshotResult['policy_source']);
        $this->assertSame('skipped', (string) $snapshotResult['status']);

        DB::table('yeekee_rounds')->where('id', 3)->update(['config_snapshot_json' => null]);
        $fallbackResult = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw());

        $this->assertSame('market_setting', (string) $fallbackResult['policy_source']);
        $this->assertSame('paid', (string) $fallbackResult['status']);
    }

    /**
     * @param  array<string,mixed>|null  $snapshot
     */
    private function seedBaseData(?array $snapshot, bool $marketRewardEnabled = false): void
    {
        DB::table('members')->insert([
            'code' => 7001,
            'balance' => 1000,
            'date_update' => now(),
        ]);

        DB::table('lotto_markets')->insert([
            ['id' => 106, 'name' => 'Yeekee', 'result_mode' => 'yeekee', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 107, 'name' => 'Yeekee 2', 'result_mode' => 'yeekee', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->seedRoundAndDraw(roundId: 3, drawId: 3003, marketId: 106, drawDate: '2026-05-07', snapshot: $snapshot);

        DB::table('yeekee_market_settings')->insert([
            ['market_id' => 106, 'reward_enabled' => $marketRewardEnabled ? 1 : 0, 'reward_config' => json_encode($this->rewardConfig(), JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()],
            ['market_id' => 107, 'reward_enabled' => 1, 'reward_config' => json_encode($this->rewardConfig(['reward_scope' => 'per_market_per_day', 'max_rewards_per_member_per_day' => 1]), JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * @param  array<string,mixed>  $override
     * @return array<string,mixed>
     */
    private function rewardConfig(array $override = []): array
    {
        return array_replace([
            'reward_enabled' => true,
            'reward_positions' => [
                ['position' => 16, 'credit_amount' => 20],
            ],
            'min_bet_amount' => 50,
        ], $override);
    }

    /**
     * @param  array<string,mixed>|null  $snapshot
     */
    private function seedRoundAndDraw(int $roundId, int $drawId, int $marketId, string $drawDate, ?array $snapshot = null): void
    {
        DB::table('lotto_draws')->insert([
            'id' => $drawId,
            'market_id' => $marketId,
            'draw_date' => $drawDate,
            'status' => 'resulted',
            'result_number' => json_encode(['top_3' => '123', 'bottom_2' => '23']),
            'result_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('yeekee_rounds')->insert([
            'id' => $roundId,
            'market_id' => $marketId,
            'lotto_draw_id' => $drawId,
            'round_date' => $drawDate,
            'round_no' => $roundId,
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
    }

    private function seedShoot(int $id, int $position, int $memberId, int $roundId = 3, int $drawId = 3003, int $marketId = 106): void
    {
        DB::table('yeekee_shoots')->insert([
            'id' => $id,
            'yeekee_round_id' => $roundId,
            'lotto_draw_id' => $drawId,
            'market_id' => $marketId,
            'member_id' => $memberId,
            'position' => $position,
            'number_text' => '12345',
            'number_value' => 12345,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedTicket(int $memberId, int $drawId, float $amount, int $id = 9001, string $status = 'active'): void
    {
        DB::table('lotto_tickets')->insert([
            'id' => $id,
            'member_id' => $memberId,
            'draw_id' => $drawId,
            'total_amount' => $amount,
            'total_net_amount' => $amount,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function draw(int $id = 3003): LottoDraw
    {
        return LottoDraw::query()->findOrFail($id);
    }

    private function round(int $id = 3): YeekeeRound
    {
        return YeekeeRound::query()->findOrFail($id);
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

        Schema::create('lotto_tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('draw_id');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('total_net_amount', 12, 2)->default(0);
            $table->string('status', 32)->default('active');
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
            $table->unsignedBigInteger('lotto_draw_id')->nullable();
            $table->unsignedBigInteger('market_id')->nullable();
            $table->unsignedBigInteger('yeekee_shoot_id')->nullable();
            $table->unsignedBigInteger('member_id');
            $table->unsignedInteger('position');
            $table->decimal('credit_amount', 12, 2);
            $table->string('currency', 8)->default('THB');
            $table->string('status', 32)->default('paid');
            $table->string('reason', 128)->nullable();
            $table->string('policy_source', 32)->nullable();
            $table->string('policy_hash', 64)->nullable();
            $table->string('reward_ref_type', 32)->default('YEEKEE_SHOOT_REWARD');
            $table->string('idempotency_key', 191)->nullable();
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
            $table->unique(['yeekee_round_id', 'member_id', 'position'], 'yeekee_reward_round_member_position_unique');
            $table->unique('idempotency_key', 'yeekee_reward_idempotency_key_unique');
        });
    }
}
