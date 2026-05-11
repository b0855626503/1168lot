<?php

namespace Tests\Feature\Lotto;

use Exception;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Services\BetService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class BetServicePermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('member_lotto_market_policies');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');
        Schema::dropIfExists('members');

        Schema::create('members', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
        });

        Schema::create('lotto_groups', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('lotto_draws', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->string('status', 20)->default('open');
            $table->timestamps();
        });

        Schema::create('member_lotto_market_policies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('market_id');
            $table->boolean('is_allowed')->default(false);
            $table->string('source', 30)->default('inherit');
            $table->unsignedInteger('policy_version')->default(1);
            $table->timestamps();
            $table->unique(['member_id', 'market_id'], 'member_market_unique');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('member_lotto_market_policies');
        Schema::dropIfExists('lotto_draws');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');
        Schema::dropIfExists('members');

        parent::tearDown();
    }

    public function test_member_without_policy_row_can_bet(): void
    {
        DB::table('members')->insert([['code' => 1]]);
        DB::table('lotto_groups')->insert([['id' => 10, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()]]);
        DB::table('lotto_markets')->insert([['id' => 100, 'group_id' => 10, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()]]);
        DB::table('lotto_draws')->insert([['id' => 1000, 'market_id' => 100, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]]);

        // No row in member_lotto_market_policies

        $draw = LottoDraw::query()->find(1000);

        $this->invokeValidateMemberPermission(1, $draw);

        // No exception means success
        $this->assertTrue(true);
    }

    public function test_member_with_deny_policy_cannot_bet(): void
    {
        DB::table('members')->insert([['code' => 2]]);
        DB::table('lotto_groups')->insert([['id' => 20, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()]]);
        DB::table('lotto_markets')->insert([['id' => 200, 'group_id' => 20, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()]]);
        DB::table('lotto_draws')->insert([['id' => 2000, 'market_id' => 200, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]]);

        // Deny row exists
        DB::table('member_lotto_market_policies')->insert([
            'member_id' => 2,
            'group_id' => 20,
            'market_id' => 200,
            'is_allowed' => false,
            'source' => 'admin_rollout',
            'policy_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $draw = LottoDraw::query()->find(2000);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Member is blocked from betting on this market');

        $this->invokeValidateMemberPermission(2, $draw);
    }

    public function test_member_with_legacy_allow_row_can_bet(): void
    {
        DB::table('members')->insert([['code' => 3]]);
        DB::table('lotto_groups')->insert([['id' => 30, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()]]);
        DB::table('lotto_markets')->insert([['id' => 300, 'group_id' => 30, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()]]);
        DB::table('lotto_draws')->insert([['id' => 3000, 'market_id' => 300, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]]);

        // Legacy allow row — should be no-op
        DB::table('member_lotto_market_policies')->insert([
            'member_id' => 3,
            'group_id' => 30,
            'market_id' => 300,
            'is_allowed' => true,
            'source' => 'inherit',
            'policy_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $draw = LottoDraw::query()->find(3000);

        $this->invokeValidateMemberPermission(3, $draw);

        // No exception means success
        $this->assertTrue(true);
    }

    public function test_only_deny_row_blocks_member_when_both_rows_exist(): void
    {
        DB::table('members')->insert([['code' => 4]]);
        DB::table('lotto_groups')->insert([['id' => 40, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()]]);
        DB::table('lotto_markets')->insert([
            ['id' => 400, 'group_id' => 40, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 401, 'group_id' => 40, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('lotto_draws')->insert([
            ['id' => 4000, 'market_id' => 400, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4001, 'market_id' => 401, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Deny row for market 400
        DB::table('member_lotto_market_policies')->insert([
            'member_id' => 4,
            'group_id' => 40,
            'market_id' => 400,
            'is_allowed' => false,
            'source' => 'admin_rollout',
            'policy_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Legacy allow row for market 401
        DB::table('member_lotto_market_policies')->insert([
            'member_id' => 4,
            'group_id' => 40,
            'market_id' => 401,
            'is_allowed' => true,
            'source' => 'inherit',
            'policy_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Should be blocked on market 400 (deny row)
        $draw400 = LottoDraw::query()->find(4000);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Member is blocked from betting on this market');
        $this->invokeValidateMemberPermission(4, $draw400);
    }

    public function test_member_with_unrelated_deny_is_not_blocked_on_other_market(): void
    {
        DB::table('members')->insert([['code' => 5]]);
        DB::table('lotto_groups')->insert([['id' => 50, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()]]);
        DB::table('lotto_markets')->insert([
            ['id' => 500, 'group_id' => 50, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 501, 'group_id' => 50, 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('lotto_draws')->insert([
            ['id' => 5000, 'market_id' => 500, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5001, 'market_id' => 501, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Deny row only for market 500
        DB::table('member_lotto_market_policies')->insert([
            'member_id' => 5,
            'group_id' => 50,
            'market_id' => 500,
            'is_allowed' => false,
            'source' => 'admin_rollout',
            'policy_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Should still be able to bet on market 501 (no deny row)
        $draw501 = LottoDraw::query()->find(5001);
        $this->invokeValidateMemberPermission(5, $draw501);

        // No exception means success
        $this->assertTrue(true);
    }

    private function invokeValidateMemberPermission(int $memberId, LottoDraw $draw): void
    {
        $service = $this->app->make(BetService::class);
        $ref = new ReflectionMethod($service, 'validateMemberPermission');

        $ref->invoke($service, $memberId, $draw);
    }
}
