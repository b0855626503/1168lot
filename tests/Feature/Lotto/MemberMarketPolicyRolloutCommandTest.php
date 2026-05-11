<?php

namespace Tests\Feature\Lotto;

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

    public function test_deprecated_command_exits_successfully_without_inserting_rows(): void
    {
        $groupId = $this->insertGroup(enabled: true);
        $this->insertMembers([1001, 1002]);
        $this->insertMarket(61, $groupId, true);

        $this->artisan('lotto:policy-rollout-markets', [
            '--market-ids' => '61',
        ])
            ->expectsOutputToContain('deprecated')
            ->expectsOutputToContain('default-allow')
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('member_lotto_market_policies')->count());
    }

    public function test_deprecated_command_does_not_create_rows_even_with_dry_run_flag(): void
    {
        $groupId = $this->insertGroup(enabled: true);
        $this->insertMembers([1001]);
        $this->insertMarket(61, $groupId, true);

        $this->artisan('lotto:policy-rollout-markets', [
            '--market-ids' => '61',
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('deprecated')
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('member_lotto_market_policies')->count());
    }

    public function test_deprecated_command_does_not_create_rows_with_scope_selected(): void
    {
        $groupId = $this->insertGroup(enabled: true);
        $this->insertMembers([1001, 1002, 1003]);
        $this->insertMarket(61, $groupId, true);

        $this->artisan('lotto:policy-rollout-markets', [
            '--market-ids' => '61',
            '--scope' => 'selected',
            '--member-ids' => '1001,1003',
        ])
            ->expectsOutputToContain('deprecated')
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('member_lotto_market_policies')->count());
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
