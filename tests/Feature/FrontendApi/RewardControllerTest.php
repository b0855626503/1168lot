<?php

namespace Tests\Feature\FrontendApi;

use Gametech\FrontendApi\Http\Controllers\Api\V1\RewardController;
use Gametech\FrontendApi\Http\Requests\RewardHistoryRequest;
use Gametech\FrontendApi\Http\Requests\RewardListRequest;
use Gametech\FrontendApi\Http\Requests\RewardRedeemRequest;
use Gametech\Member\Models\Member;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class RewardControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('rewards_list');
        Schema::dropIfExists('members');

        Mockery::close();

        parent::tearDown();
    }

    public function test_reward_list_returns_only_available_rewards(): void
    {
        DB::table('members')->insert([
            'code' => 9001,
            'point_deposit' => 120,
            'name' => 'Reward Member',
        ]);

        DB::table('rewards_list')->insert([
            [
                'id' => 1,
                'code' => 'RW-CREDIT-01',
                'name' => 'เครดิต 50',
                'description' => 'แลกเครดิต',
                'image' => 'rewards/rw1.png',
                'reward_type' => 'wallet_credit',
                'fulfillment_mode' => 'auto',
                'point_cost' => 50,
                'stock_unlimited' => 0,
                'stock' => 10,
                'reserved_stock' => 0,
                'status' => 'active',
                'is_hidden' => 0,
                'is_featured' => 1,
                'priority' => 9,
                'created_at' => '2026-04-19 10:00:00',
                'updated_at' => '2026-04-19 10:00:00',
            ],
            [
                'id' => 2,
                'code' => 'RW-HIDDEN',
                'name' => 'ซ่อน',
                'description' => 'hidden',
                'image' => 'rewards/rw2.png',
                'reward_type' => 'external',
                'fulfillment_mode' => 'manual',
                'point_cost' => 20,
                'stock_unlimited' => 1,
                'stock' => null,
                'reserved_stock' => 0,
                'status' => 'active',
                'is_hidden' => 1,
                'is_featured' => 0,
                'priority' => 1,
                'created_at' => '2026-04-19 10:00:00',
                'updated_at' => '2026-04-19 10:00:00',
            ],
        ]);

        $baseRequest = Request::create('/api/v1/reward/list', 'GET');
        $baseRequest->setUserResolver(fn () => $this->customer(9001));
        $request = RewardListRequest::createFromBase($baseRequest);
        $request->setUserResolver(fn () => $this->customer(9001));

        $response = TestResponse::fromBaseResponse(
            app(RewardController::class)->list($request)
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('point', 120);
        $response->assertJsonCount(1, 'rewards');
        $response->assertJsonPath('rewards.0.code', 'RW-CREDIT-01');
    }

    public function test_reward_redeem_decreases_member_point_and_creates_redemption(): void
    {
        DB::table('members')->insert([
            'code' => 9002,
            'point_deposit' => 100,
            'name' => 'Redeem Member',
        ]);

        DB::table('rewards_list')->insert([
            'id' => 10,
            'code' => 'RW-MANUAL-01',
            'name' => 'ของรางวัลหน้าร้าน',
            'description' => 'manual reward',
            'reward_type' => 'external',
            'fulfillment_mode' => 'manual',
            'auto_claim' => 0,
            'point_cost' => 30,
            'stock_unlimited' => 0,
            'stock' => 5,
            'reserved_stock' => 0,
            'status' => 'active',
            'is_hidden' => 0,
            'created_at' => '2026-04-19 10:00:00',
            'updated_at' => '2026-04-19 10:00:00',
        ]);

        $pointRepository = new class
        {
            public function setPoint(array $payload): array
            {
                return ['success' => true, 'payload' => $payload];
            }
        };
        $this->app->instance('memberPointLogRepository', $pointRepository);

        $baseRequest = Request::create('/api/v1/reward/redeem', 'POST', [
            'reward_id' => 10,
        ]);
        $baseRequest->setUserResolver(fn () => $this->customer(9002));
        $request = RewardRedeemRequest::createFromBase($baseRequest);
        $request->setUserResolver(fn () => $this->customer(9002));

        $response = TestResponse::fromBaseResponse(
            app(RewardController::class)->redeem($request)
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('mode', 'manual');
        $response->assertJsonPath('redemption_status', 'pending');

        $this->assertSame(70, (int) DB::table('members')->where('code', 9002)->value('point_deposit'));
        $this->assertDatabaseHas('reward_redemptions', [
            'member_id' => 9002,
            'reward_id' => 10,
            'point_cost_snapshot' => 30,
            'status' => 'pending',
        ]);
    }

    public function test_reward_history_returns_timeline_groups(): void
    {
        DB::table('members')->insert([
            'code' => 9003,
            'point_deposit' => 90,
            'name' => 'History Member',
        ]);

        DB::table('reward_redemptions')->insert([
            [
                'id' => 100,
                'reward_id' => 10,
                'member_id' => 9003,
                'reward_code_snapshot' => 'RW-01',
                'reward_name_snapshot' => 'รางวัล A',
                'point_cost_snapshot' => 10,
                'reward_type_snapshot' => 'external',
                'fulfillment_mode_snapshot' => 'manual',
                'status' => 'pending',
                'created_at' => '2026-04-19 08:00:00',
                'updated_at' => '2026-04-19 08:00:00',
                'redeemed_at' => '2026-04-19 08:00:00',
            ],
            [
                'id' => 101,
                'reward_id' => 11,
                'member_id' => 9003,
                'reward_code_snapshot' => 'RW-02',
                'reward_name_snapshot' => 'รางวัล B',
                'point_cost_snapshot' => 15,
                'reward_type_snapshot' => 'external',
                'fulfillment_mode_snapshot' => 'manual',
                'status' => 'fulfilled',
                'created_at' => '2026-04-18 09:30:00',
                'updated_at' => '2026-04-18 09:30:00',
                'redeemed_at' => '2026-04-18 09:30:00',
            ],
        ]);

        $baseRequest = Request::create('/api/v1/reward/history', 'GET');
        $baseRequest->setUserResolver(fn () => $this->customer(9003));
        $request = RewardHistoryRequest::createFromBase($baseRequest);
        $request->setUserResolver(fn () => $this->customer(9003));

        $response = TestResponse::fromBaseResponse(
            app(RewardController::class)->history($request)
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(2, 'items');
        $response->assertJsonCount(2, 'timeline');
        $response->assertJsonPath('timeline.0.date', '2026-04-19');
        $response->assertJsonPath('timeline.0.count', 1);
    }

    private function customer(int $memberCode): Member
    {
        $member = new Member;
        $member->code = $memberCode;
        $member->name = 'Customer';
        $member->point_deposit = (int) DB::table('members')->where('code', $memberCode)->value('point_deposit');
        $member->exists = true;

        return $member;
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('rewards_list');
        Schema::dropIfExists('members');

        Schema::create('members', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
            $table->string('name')->nullable();
            $table->unsignedInteger('point_deposit')->default(0);
        });

        Schema::create('rewards_list', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            $table->string('reward_type')->default('wallet_credit');
            $table->string('fulfillment_mode')->default('auto');
            $table->boolean('auto_claim')->default(true);
            $table->unsignedInteger('point_cost')->default(0);
            $table->unsignedInteger('stock')->nullable();
            $table->unsignedInteger('reserved_stock')->default(0);
            $table->boolean('stock_unlimited')->default(true);
            $table->unsignedInteger('limit_per_user')->nullable();
            $table->unsignedInteger('limit_total')->nullable();
            $table->unsignedInteger('cooldown_minutes')->nullable();
            $table->string('limit_type')->default('unlimited');
            $table->string('limit_period')->nullable();
            $table->unsignedInteger('limit_per_period')->nullable();
            $table->boolean('strict_limit')->default(false);
            $table->decimal('credit_amount', 12, 2)->nullable();
            $table->decimal('gem_amount', 12, 2)->nullable();
            $table->json('payload')->nullable();
            $table->string('status')->default('active');
            $table->boolean('is_hidden')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('priority')->default(0);
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });

        Schema::create('reward_redemptions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('reward_id');
            $table->unsignedBigInteger('member_id');
            $table->string('reward_code_snapshot')->nullable();
            $table->string('reward_name_snapshot')->nullable();
            $table->unsignedInteger('point_cost_snapshot')->default(0);
            $table->string('reward_type_snapshot')->nullable();
            $table->string('fulfillment_mode_snapshot')->nullable();
            $table->decimal('credit_amount_snapshot', 12, 2)->nullable();
            $table->decimal('gem_amount_snapshot', 12, 2)->nullable();
            $table->json('payload_snapshot')->nullable();
            $table->string('status')->default('pending');
            $table->text('note_user')->nullable();
            $table->text('note_staff')->nullable();
            $table->string('contact_channel')->nullable();
            $table->string('contact_value')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->unsignedBigInteger('refunded_by')->nullable();
            $table->boolean('point_debited')->default(true);
            $table->string('request_ip')->nullable();
            $table->text('request_ua')->nullable();
            $table->string('request_source')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
}
