<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Models\MemberLottoMarketPolicy;
use Gametech\Lotto\Transformers\MemberLottoPermissionTransformer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MemberLottoPermissionControllerTest extends TestCase
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
            $table->string('user_name', 100)->nullable();
            $table->string('name', 100)->nullable();
        });

        Schema::create('lotto_groups', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->string('name', 100)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('lotto_markets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id');
            $table->boolean('is_enabled')->default(true);
            $table->string('name', 100)->nullable();
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
        Schema::dropIfExists('lotto_markets');
        Schema::dropIfExists('lotto_groups');
        Schema::dropIfExists('members');

        parent::tearDown();
    }

    public function test_transformer_displays_blocklist_labels_for_deny_row(): void
    {
        DB::table('members')->insert([['code' => 300, 'user_name' => 'test3']]);
        DB::table('lotto_groups')->insert([['id' => 3, 'is_enabled' => true, 'name' => 'G3', 'sort' => 1, 'created_at' => now(), 'updated_at' => now()]]);
        DB::table('lotto_markets')->insert([['id' => 70, 'group_id' => 3, 'is_enabled' => true, 'name' => 'M3', 'created_at' => now(), 'updated_at' => now()]]);

        $blocked = MemberLottoMarketPolicy::query()->create([
            'member_id' => 300,
            'group_id' => 3,
            'market_id' => 70,
            'is_allowed' => false,
            'source' => 'admin',
            'policy_version' => 1,
        ]);

        $blocked->load(['member', 'group', 'market']);
        $transformer = new MemberLottoPermissionTransformer;
        $result = $transformer->transform($blocked);

        $this->assertStringContainsString('บล็อก', $result['is_allowed']);
        $this->assertStringContainsString('fa-ban', $result['is_allowed']);
        $this->assertStringNotContainsString('อนุญาต', $result['is_allowed']);
    }

    public function test_transformer_displays_normal_label_for_legacy_allow_row(): void
    {
        DB::table('members')->insert([['code' => 301, 'user_name' => 'test4']]);
        DB::table('lotto_groups')->insert([['id' => 4, 'is_enabled' => true, 'name' => 'G4', 'sort' => 1, 'created_at' => now(), 'updated_at' => now()]]);
        DB::table('lotto_markets')->insert([['id' => 71, 'group_id' => 4, 'is_enabled' => true, 'name' => 'M4', 'created_at' => now(), 'updated_at' => now()]]);

        $allowed = MemberLottoMarketPolicy::query()->create([
            'member_id' => 301,
            'group_id' => 4,
            'market_id' => 71,
            'is_allowed' => true,
            'source' => 'inherit',
            'policy_version' => 1,
        ]);

        $allowed->load(['member', 'group', 'market']);
        $transformer = new MemberLottoPermissionTransformer;
        $result = $transformer->transform($allowed);

        $this->assertStringContainsString('ปกติ', $result['is_allowed']);
        $this->assertStringContainsString('fa-check', $result['is_allowed']);
    }

    public function test_create_model_defaults_is_allowed_to_false(): void
    {
        DB::table('members')->insert([['code' => 400, 'user_name' => 'test5']]);
        DB::table('lotto_groups')->insert([['id' => 5, 'is_enabled' => true, 'name' => 'G5', 'sort' => 1, 'created_at' => now(), 'updated_at' => now()]]);
        DB::table('lotto_markets')->insert([['id' => 80, 'group_id' => 5, 'is_enabled' => true, 'name' => 'M5', 'created_at' => now(), 'updated_at' => now()]]);

        $policy = MemberLottoMarketPolicy::query()->create([
            'member_id' => 400,
            'group_id' => 5,
            'market_id' => 80,
            'is_allowed' => false,
            'source' => 'admin',
            'policy_version' => 1,
        ]);

        $this->assertSame(false, (bool) $policy->is_allowed);
        $this->assertSame('admin', $policy->source);
    }

    public function test_delete_removes_row_for_unblock(): void
    {
        DB::table('members')->insert([['code' => 500, 'user_name' => 'test6']]);
        DB::table('lotto_groups')->insert([['id' => 6, 'is_enabled' => true, 'name' => 'G6', 'sort' => 1, 'created_at' => now(), 'updated_at' => now()]]);
        DB::table('lotto_markets')->insert([['id' => 90, 'group_id' => 6, 'is_enabled' => true, 'name' => 'M6', 'created_at' => now(), 'updated_at' => now()]]);

        $policy = MemberLottoMarketPolicy::query()->create([
            'member_id' => 500,
            'group_id' => 6,
            'market_id' => 90,
            'is_allowed' => false,
            'source' => 'admin',
            'policy_version' => 1,
        ]);

        $this->assertNotNull(MemberLottoMarketPolicy::query()->find($policy->id));

        $policy->delete();

        $this->assertNull(MemberLottoMarketPolicy::query()->find($policy->id));
    }
}
