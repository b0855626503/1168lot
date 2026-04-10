<?php

namespace Tests\Feature;

use Gametech\Lotto\Services\MemberMarketPolicyService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

    public function test_bootstrap_for_member_creates_policies_for_all_enabled_markets(): void
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

        app(MemberMarketPolicyService::class)->bootstrapForMember(9001);

        $policies = DB::table('member_lotto_market_policies')
            ->orderBy('market_id')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();

        $this->assertCount(2, $policies);
        $this->assertSame(101, (int) $policies[0]['market_id']);
        $this->assertSame(11, (int) $policies[0]['group_id']);
        $this->assertSame(1, (int) $policies[0]['is_allowed']);
        $this->assertSame('inherit', $policies[0]['source']);
        $this->assertSame(2, (int) $policies[0]['policy_version']);

        $this->assertSame(102, (int) $policies[1]['market_id']);
        $this->assertSame(0, (int) $policies[1]['is_allowed']);
        $this->assertSame(5, (int) $policies[1]['policy_version']);
    }

    public function test_bootstrap_for_member_updates_existing_policy_without_creating_duplicates(): void
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

        $this->assertSame(1, DB::table('member_lotto_market_policies')->count());
        $this->assertDatabaseHas('member_lotto_market_policies', [
            'member_id' => 9002,
            'market_id' => 201,
            'group_id' => 21,
            'is_allowed' => 1,
            'source' => 'inherit',
            'policy_version' => 4,
        ]);
    }
}
