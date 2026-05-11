<?php

namespace Tests\Feature;

use Gametech\Lotto\Services\MemberMarketPolicyService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MemberMarketPolicyServiceBootstrapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('member_lotto_market_policies');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');
        Schema::dropIfExists('members');

        Schema::create('members', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
        });

        Schema::create('lotto_groups', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->string('rollout_mode', 20)->default('new_only');
            $table->unsignedInteger('policy_version')->default(1);
            $table->timestamps();
        });

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id');
            $table->boolean('is_enabled')->default(true);
            $table->string('rollout_mode', 20)->nullable();
            $table->unsignedInteger('policy_version')->default(1);
            $table->timestamps();
        });

        Schema::create('member_lotto_market_policies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('market_id');
            $table->boolean('is_allowed')->default(false);
            $table->string('source', 20)->default('inherit');
            $table->unsignedInteger('policy_version')->default(1);
            $table->timestamps();
            $table->unique(['member_id', 'market_id'], 'member_market_unique');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('member_lotto_market_policies');
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');
        Schema::dropIfExists('members');

        parent::tearDown();
    }

    public function test_bootstrap_for_member_no_longer_creates_policy_rows(): void
    {
        DB::table('members')->insert([
            ['code' => 9001],
        ]);

        DB::table('lotto_groups')->insert([
            ['id' => 11, 'is_enabled' => true, 'rollout_mode' => 'new_only', 'policy_version' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'is_enabled' => false, 'rollout_mode' => 'new_only', 'policy_version' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('lotto_markets')->insert([
            ['id' => 101, 'group_id' => 11, 'is_enabled' => true, 'rollout_mode' => null, 'policy_version' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 102, 'group_id' => 11, 'is_enabled' => true, 'rollout_mode' => 'selected', 'policy_version' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 103, 'group_id' => 12, 'is_enabled' => true, 'rollout_mode' => null, 'policy_version' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 104, 'group_id' => 11, 'is_enabled' => false, 'rollout_mode' => null, 'policy_version' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'MemberMarketPolicyService: bootstrapForMember is disabled under blacklist model.'
                    && ($context['member_id'] ?? 0) === 9001;
            });

        app(MemberMarketPolicyService::class)->bootstrapForMember(9001);

        $this->assertSame(0, DB::table('member_lotto_market_policies')->count());
    }

    public function test_bootstrap_for_member_does_not_create_duplicates_or_updates(): void
    {
        DB::table('members')->insert([
            ['code' => 9002],
        ]);

        DB::table('lotto_groups')->insert([
            ['id' => 21, 'is_enabled' => true, 'rollout_mode' => 'new_only', 'policy_version' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('lotto_markets')->insert([
            ['id' => 201, 'group_id' => 21, 'is_enabled' => true, 'rollout_mode' => null, 'policy_version' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('member_lotto_market_policies')->insert([
            'member_id' => 9002,
            'group_id' => 21,
            'market_id' => 201,
            'is_allowed' => false,
            'source' => 'legacy',
            'policy_version' => 1,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        app(MemberMarketPolicyService::class)->bootstrapForMember(9002);

        // bootstrapForMember is a no-op under blacklist — no new rows, existing row unchanged
        $this->assertSame(1, DB::table('member_lotto_market_policies')->count());
        $this->assertDatabaseHas('member_lotto_market_policies', [
            'member_id' => 9002,
            'market_id' => 201,
            'group_id' => 21,
            'is_allowed' => 0,
            'source' => 'legacy',
            'policy_version' => 1,
        ]);
    }
}
