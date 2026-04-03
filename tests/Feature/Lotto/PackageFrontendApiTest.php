<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Models\LottoGroupPackage;
use Gametech\Lotto\Models\LottoGroupPackageBetSetting;
use Gametech\Member\Models\Member;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PackageFrontendApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
        Cache::flush();
    }

    public function test_available_returns_only_active_packages_in_requested_group_and_enabled_settings(): void
    {
        $this->actingAsCustomer();

        $active = $this->createPackage(groupId: 1, name: 'A', isActive: true);
        $this->createSetting((int) $active->id, 'top_3', 900, 5, true);
        $this->createSetting((int) $active->id, 'bottom_2', 90, 0, false);

        $this->createPackage(groupId: 1, name: 'B', isActive: false);
        $otherGroup = $this->createPackage(groupId: 2, name: 'C', isActive: true);
        $this->createSetting((int) $otherGroup->id, 'top_2', 90, 0, true);

        $response = $this->getJson('/api/lotto/groups/1/packages');
        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', (int) $active->id);
        $response->assertJsonPath('data.0.group_id', 1);
        $response->assertJsonPath('data.0.image', '/storage/lotto/media/a.png');
        $response->assertJsonPath('data.0.bet_settings.0.bet_type', 'top_3');
        $response->assertJsonCount(1, 'data.0.bet_settings');
    }

    public function test_select_package_success_and_selected_endpoint_returns_selected_state(): void
    {
        $this->actingAsCustomer(memberCode: 2001);
        $package = $this->createPackage(groupId: 10, name: 'VIP-10', isActive: true, image: '/storage/lotto/media/vip10.png');
        $this->createSetting((int) $package->id, 'top_3', 650, 27, true);
        $this->createSetting((int) $package->id, 'bottom_2', 69, 27, true);

        $select = $this->postJson('/api/lotto/groups/10/select-package', [
            'package_id' => (int) $package->id,
        ]);
        $select->assertOk();
        $select->assertJsonPath('success', true);
        $select->assertJsonPath('data.group_id', 10);
        $select->assertJsonPath('data.package_id', (int) $package->id);
        $select->assertJsonPath('data.selected', true);

        $selected = $this->getJson('/api/lotto/groups/10/selected-package');
        $selected->assertOk();
        $selected->assertJsonPath('success', true);
        $selected->assertJsonPath('selected', true);
        $selected->assertJsonPath('data.group_id', 10);
        $selected->assertJsonPath('data.package_id', (int) $package->id);
        $selected->assertJsonPath('data.image', '/storage/lotto/media/vip10.png');
        $selected->assertJsonPath('data.bet_settings.0.bet_type', 'bottom_2');
        $selected->assertJsonPath('data.bet_settings.0.payout', 69.0);
        $selected->assertJsonPath('data.bet_settings.0.discount_percent', 27.0);
        $selected->assertJsonPath('data.bet_settings.1.bet_type', 'top_3');
        $selected->assertJsonPath('data.bet_settings.1.payout', 650.0);
        $selected->assertJsonPath('data.bet_settings.1.discount_percent', 27.0);
        $selected->assertJsonCount(2, 'data.bet_settings');
    }

    public function test_select_package_is_idempotent_when_selecting_same_package_twice(): void
    {
        $this->actingAsCustomer(memberCode: 2002);
        $package = $this->createPackage(groupId: 11, name: 'VIP-11', isActive: true);

        $first = $this->postJson('/api/lotto/groups/11/select-package', [
            'package_id' => (int) $package->id,
        ]);
        $second = $this->postJson('/api/lotto/groups/11/select-package', [
            'package_id' => (int) $package->id,
        ]);

        $first->assertOk();
        $second->assertOk();
        $first->assertJsonPath('data.package_id', (int) $package->id);
        $second->assertJsonPath('data.package_id', (int) $package->id);
    }

    public function test_select_package_returns_expected_errors_for_wrong_group_and_inactive_package(): void
    {
        $this->actingAsCustomer(memberCode: 2003);

        $wrongGroupPackage = $this->createPackage(groupId: 99, name: 'Wrong', isActive: true);
        $inactiveInGroup = $this->createPackage(groupId: 12, name: 'Inactive', isActive: false);

        $wrongGroup = $this->postJson('/api/lotto/groups/12/select-package', [
            'package_id' => (int) $wrongGroupPackage->id,
        ]);
        $wrongGroup->assertStatus(400);
        $wrongGroup->assertJsonPath('success', false);
        $wrongGroup->assertJsonPath('error_code', 'PACKAGE_NOT_IN_GROUP');

        $inactive = $this->postJson('/api/lotto/groups/12/select-package', [
            'package_id' => (int) $inactiveInGroup->id,
        ]);
        $inactive->assertStatus(409);
        $inactive->assertJsonPath('success', false);
        $inactive->assertJsonPath('error_code', 'PACKAGE_INACTIVE');
    }

    public function test_selected_package_returns_selected_false_when_not_selected_or_selected_package_becomes_inactive(): void
    {
        $this->actingAsCustomer(memberCode: 2004);
        $package = $this->createPackage(groupId: 13, name: 'T-13', isActive: true);

        $initial = $this->getJson('/api/lotto/groups/13/selected-package');
        $initial->assertOk();
        $initial->assertJsonPath('success', true);
        $initial->assertJsonPath('selected', false);
        $initial->assertJsonPath('data', null);

        $this->postJson('/api/lotto/groups/13/select-package', [
            'package_id' => (int) $package->id,
        ])->assertOk();

        $package->update(['is_active' => false]);

        $afterInactive = $this->getJson('/api/lotto/groups/13/selected-package');
        $afterInactive->assertOk();
        $afterInactive->assertJsonPath('success', true);
        $afterInactive->assertJsonPath('selected', false);
        $afterInactive->assertJsonPath('data', null);
    }

    public function test_package_endpoints_require_customer_authentication(): void
    {
        $this->createPackage(groupId: 21, name: 'Auth', isActive: true);

        $this->getJson('/api/lotto/groups/21/packages')->assertStatus(401);
        $this->postJson('/api/lotto/groups/21/select-package', ['package_id' => 1])->assertStatus(401);
        $this->getJson('/api/lotto/groups/21/selected-package')->assertStatus(401);
    }

    private function actingAsCustomer(int $memberCode = 1001): void
    {
        $member = new Member();
        $member->code = $memberCode;
        $member->name = 'Test Member';
        $member->exists = true;

        $this->actingAs($member, 'customer');
    }

    private function createPackage(int $groupId, string $name, bool $isActive, string $image = '/storage/lotto/media/a.png'): LottoGroupPackage
    {
        return LottoGroupPackage::query()->create([
            'group_id' => $groupId,
            'name' => $name,
            'description' => $name . ' description',
            'image' => $image,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSetting(int $packageId, string $betType, float $payout, float $discountPercent, bool $enabled): LottoGroupPackageBetSetting
    {
        return LottoGroupPackageBetSetting::query()->create([
            'package_id' => $packageId,
            'bet_type' => $betType,
            'payout' => $payout,
            'discount_percent' => $discountPercent,
            'is_enabled' => $enabled,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('failed_requests');
        Schema::dropIfExists('lotto_group_package_bet_settings');
        Schema::dropIfExists('lotto_group_packages');

        Schema::create('lotto_group_packages', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('group_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lotto_group_package_bet_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('package_id');
            $table->string('bet_type');
            $table->decimal('payout', 14, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('failed_requests', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('trace_id')->unique();
            $table->string('url')->nullable();
            $table->string('method', 16)->nullable();
            $table->longText('headers')->nullable();
            $table->longText('body')->nullable();
            $table->integer('status')->nullable();
            $table->longText('response')->nullable();
            $table->decimal('duration', 12, 3)->nullable();
            $table->longText('txid')->nullable();
            $table->longText('roundId')->nullable();
            $table->longText('company')->nullable();
            $table->longText('game_user')->nullable();
            $table->timestamps();
        });
    }
}
