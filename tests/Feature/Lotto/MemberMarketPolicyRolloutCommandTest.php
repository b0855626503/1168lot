<?php

namespace Tests\Feature\Lotto;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MemberMarketPolicyRolloutCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
    }

    public function test_parse_market_id_range_supports_61_to_105(): void
    {
        $groupId = $this->insertGroup(enabled: true);
        $this->insertMembers([1001]);

        foreach (range(61, 105) as $id) {
            $this->insertMarket($id, $groupId, true);
        }

        $this->artisan('lotto:policy-rollout-markets', [
            '--market-ids' => '61-105',
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('market_count=45')
            ->expectsOutputToContain('dry_run=true')
            ->assertExitCode(0);
    }

    public function test_missing_only_inserts_only_missing_without_updating_existing_row(): void
    {
        $groupId = $this->insertGroup(enabled: true);
        $this->insertMembers([1001]);

        $this->insertMarket(61, $groupId, true, 'new_only');
        $this->insertMarket(62, $groupId, true, 'new_only');

        $oldUpdatedAt = Carbon::parse('2026-01-01 00:00:00');

        DB::table('member_lotto_market_policies')->insert([
            'member_id' => 1001,
            'group_id' => $groupId,
            'market_id' => 61,
            'is_allowed' => 0,
            'source' => 'legacy',
            'policy_version' => 1,
            'created_at' => $oldUpdatedAt,
            'updated_at' => $oldUpdatedAt,
        ]);

        $this->artisan('lotto:policy-rollout-markets', [
            '--market-ids' => '61,62',
            '--scope' => 'selected',
            '--member-ids' => '1001',
            '--mode' => 'missing-only',
        ])->assertExitCode(0);

        $this->assertSame(2, DB::table('member_lotto_market_policies')->count());

        $existing = DB::table('member_lotto_market_policies')
            ->where('member_id', 1001)
            ->where('market_id', 61)
            ->first();

        $this->assertSame(0, (int) $existing->is_allowed);
        $this->assertSame('legacy', (string) $existing->source);
        $this->assertSame('2026-01-01 00:00:00', (string) $existing->updated_at);

        $inserted = DB::table('member_lotto_market_policies')
            ->where('member_id', 1001)
            ->where('market_id', 62)
            ->first();

        $this->assertNotNull($inserted);
        $this->assertSame(1, (int) $inserted->is_allowed);
        $this->assertSame('inherit', (string) $inserted->source);
    }

    public function test_resync_updates_existing_only_for_selected_markets(): void
    {
        $groupId = $this->insertGroup(enabled: true);
        $this->insertMembers([1001]);

        $this->insertMarket(61, $groupId, true, 'new_only', 5);
        $this->insertMarket(62, $groupId, true, 'new_only', 9);

        DB::table('member_lotto_market_policies')->insert([
            [
                'member_id' => 1001,
                'group_id' => $groupId,
                'market_id' => 61,
                'is_allowed' => 0,
                'source' => 'legacy',
                'policy_version' => 1,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
            [
                'member_id' => 1001,
                'group_id' => $groupId,
                'market_id' => 62,
                'is_allowed' => 0,
                'source' => 'legacy',
                'policy_version' => 1,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
        ]);

        $this->artisan('lotto:policy-rollout-markets', [
            '--market-ids' => '61',
            '--scope' => 'selected',
            '--member-ids' => '1001',
            '--mode' => 'resync',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('member_lotto_market_policies', [
            'member_id' => 1001,
            'market_id' => 61,
            'is_allowed' => 1,
            'source' => 'inherit',
            'policy_version' => 5,
        ]);

        $this->assertDatabaseHas('member_lotto_market_policies', [
            'member_id' => 1001,
            'market_id' => 62,
            'is_allowed' => 0,
            'source' => 'legacy',
            'policy_version' => 1,
        ]);
    }

    public function test_dry_run_does_not_insert_rows(): void
    {
        $groupId = $this->insertGroup(enabled: true);
        $this->insertMembers([1001, 1002]);
        $this->insertMarket(61, $groupId, true);
        $this->insertMarket(62, $groupId, true);

        $this->artisan('lotto:policy-rollout-markets', [
            '--market-ids' => '61,62',
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('inserted_rows=0')
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('member_lotto_market_policies')->count());
    }

    public function test_scope_selected_uses_only_selected_member_ids(): void
    {
        $groupId = $this->insertGroup(enabled: true);
        $this->insertMembers([1001, 1002, 1003]);
        $this->insertMarket(61, $groupId, true);

        $this->artisan('lotto:policy-rollout-markets', [
            '--market-ids' => '61',
            '--scope' => 'selected',
            '--member-ids' => '1001,1003',
            '--mode' => 'missing-only',
        ])
            ->expectsOutputToContain('member_count=2')
            ->assertExitCode(0);

        $this->assertSame(2, DB::table('member_lotto_market_policies')->count());
        $this->assertDatabaseMissing('member_lotto_market_policies', ['member_id' => 1002, 'market_id' => 61]);
    }

    public function test_rejects_when_market_ids_and_group_id_not_provided(): void
    {
        $this->artisan('lotto:policy-rollout-markets', [
            '--dry-run' => true,
        ])->assertExitCode(1);
    }

    public function test_rejects_when_scope_selected_without_member_ids(): void
    {
        $groupId = $this->insertGroup(enabled: true);
        $this->insertMarket(61, $groupId, true);

        $this->artisan('lotto:policy-rollout-markets', [
            '--market-ids' => '61',
            '--scope' => 'selected',
        ])->assertExitCode(1);
    }

    public function test_missing_only_is_idempotent_for_new_markets_without_duplicates(): void
    {
        $groupId = $this->insertGroup(enabled: true);
        $this->insertMembers([1001, 1002]);
        $this->insertMarket(106, $groupId, true);
        $this->insertMarket(107, $groupId, true);

        $this->artisan('lotto:policy-rollout-markets', [
            '--market-ids' => '106,107',
            '--mode' => 'missing-only',
            '--chunk' => 1000,
        ])->assertExitCode(0);

        $this->assertSame(4, DB::table('member_lotto_market_policies')->count());

        $this->artisan('lotto:policy-rollout-markets', [
            '--market-ids' => '106,107',
            '--mode' => 'missing-only',
            '--chunk' => 1000,
        ])->assertExitCode(0);

        $this->assertSame(4, DB::table('member_lotto_market_policies')->count());
    }

    private function createSchema(): void
    {
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
            $table->unsignedBigInteger('group_id');
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

    private function insertGroup(bool $enabled, string $rolloutMode = 'new_only', int $policyVersion = 1): int
    {
        return (int) DB::table('lotto_groups')->insertGetId([
            'is_enabled' => $enabled,
            'rollout_mode' => $rolloutMode,
            'policy_version' => $policyVersion,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertMarket(int $id, int $groupId, bool $enabled, ?string $rolloutMode = null, int $policyVersion = 1): void
    {
        DB::table('lotto_markets')->insert([
            'id' => $id,
            'group_id' => $groupId,
            'is_enabled' => $enabled,
            'rollout_mode' => $rolloutMode,
            'policy_version' => $policyVersion,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<int>  $memberIds
     */
    private function insertMembers(array $memberIds): void
    {
        DB::table('members')->insert(collect($memberIds)->map(static fn (int $id): array => ['code' => $id])->all());
    }
}
