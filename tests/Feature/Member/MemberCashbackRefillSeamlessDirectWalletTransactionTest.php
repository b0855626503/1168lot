<?php

namespace Tests\Feature\Member;

use Gametech\Core\Core;
use Gametech\Game\Repositories\GameUserEventRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Repositories\MemberCashbackRepository;
use Gametech\Member\Repositories\MemberCreditFreeLogRepository;
use Gametech\Member\Repositories\MemberCreditLogRepository;
use Gametech\Member\Repositories\MemberFreeCreditRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Promotion\Repositories\PromotionRepository;
use Illuminate\Container\Container;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Fluent;
use Mockery;
use Tests\TestCase;

class MemberCashbackRefillSeamlessDirectWalletTransactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('members_cashback');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('logger_admin_activity');
        Mockery::close();

        parent::tearDown();
    }

    public function test_refill_seamless_direct_records_wallet_transaction_when_crediting_main_wallet(): void
    {
        $member = new class(['code' => 9001, 'user_name' => 'cashback.member', 'balance' => 100.0, 'balance_free' => 0.0]) extends Fluent
        {
            public function save(): bool
            {
                return true;
            }
        };
        $gameUser = new class(['code' => 7001, 'amount' => 0, 'bonus' => 0, 'turnpro' => 0, 'amount_balance' => 0, 'withdraw_limit' => 0, 'withdraw_limit_rate' => 0, 'withdraw_limit_amount' => 0]) extends Fluent
        {
            public function save(): bool
            {
                return true;
            }
        };

        $memberRepository = Mockery::mock(MemberRepository::class);
        $memberRepository->shouldReceive('find')
            ->once()
            ->with(9001)
            ->andReturn($member);

        $memberFreeCreditRepository = Mockery::mock(MemberFreeCreditRepository::class);
        $memberFreeCreditRepository->shouldNotReceive('create');

        $gameUserEventRepository = Mockery::mock(GameUserEventRepository::class);
        $gameUserEventRepository->shouldReceive('findOneWhere')
            ->once()
            ->andReturn($gameUser);

        $memberCreditFreeLogRepository = Mockery::mock(MemberCreditFreeLogRepository::class);
        $memberCreditFreeLogRepository->shouldNotReceive('create');

        $memberCreditLogRepository = new class(Mockery::mock(MemberRepository::class), Mockery::mock(GameUserRepository::class), Mockery::mock(GameUserEventRepository::class), Mockery::mock(PromotionRepository::class), app(Container::class)) extends MemberCreditLogRepository
        {
            public function create(array $attributes)
            {
                return (object) ['code' => 8001, 'attributes' => $attributes];
            }
        };

        $repository = new class($memberRepository, $memberFreeCreditRepository, $gameUserEventRepository, $memberCreditFreeLogRepository, $memberCreditLogRepository, app(Container::class)) extends MemberCashbackRepository
        {
            public function find($id, $columns = ['*'])
            {
                return null;
            }

            public function create(array $attributes)
            {
                return (object) ['code' => 7001, 'attributes' => $attributes];
            }
        };

        $core = Mockery::mock(Core::class);
        $core->shouldReceive('getConfigData')
            ->andReturn((object) [
                'seamless' => 'Y',
                'freecredit_open' => 'N',
            ]);
        $core->shouldReceive('getGame')
            ->andReturn((object) ['code' => 'GAME01']);
        $this->app->instance(Core::class, $core);

        DB::table('promotions')->insert([
            'id' => 'pro_cashback',
            'code' => 5001,
            'turnpro' => 3,
            'withdraw_limit' => 1,
            'withdraw_limit_rate' => 1.5,
        ]);

        $result = $repository->refillSeamlessDirect([
            'upline_code' => 8000,
            'member_code' => 9001,
            'balance' => 100.0,
            'cashback' => 25.0,
            'date_cashback' => '2026-04-25',
            'sum_deposit' => 1000.0,
            'sum_withdraw' => 50.0,
            'sum_balance' => 100.0,
            'ip' => '127.0.0.1',
            'emp_code' => 0,
            'emp_name' => 'System Auto',
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('wallet_transactions', [
            'member_id' => 9001,
            'direction' => 'CREDIT',
            'amount' => 25.00,
            'balance_before' => 100.00,
            'balance_after' => 125.00,
            'ref_type' => 'TRANCB',
            'ref_id' => 7001,
            'status' => 'SUCCESS',
            'description' => 'Auto cashback refill via refillSeamlessDirect',
            'created_by_type' => 'system',
        ]);
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('members_cashback');
        Schema::create('members_cashback', function (Blueprint $table): void {
            $table->bigIncrements('code');
            $table->unsignedBigInteger('member_code')->nullable();
            $table->unsignedBigInteger('downline_code')->nullable();
            $table->string('topupic', 1)->default('N');
            $table->timestamps();
        });

        Schema::dropIfExists('promotions');
        Schema::create('promotions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->unsignedBigInteger('code')->nullable();
            $table->decimal('turnpro', 15, 2)->default(0);
            $table->decimal('withdraw_limit', 15, 2)->default(0);
            $table->decimal('withdraw_limit_rate', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::dropIfExists('wallet_transactions');
        Schema::create('wallet_transactions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('member_id');
            $table->string('scope', 32);
            $table->unsignedBigInteger('game_user_id')->nullable();
            $table->string('direction', 16);
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('balance_before', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->string('ref_type', 32);
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('ref_code', 64)->nullable();
            $table->string('group_code', 64)->nullable();
            $table->unsignedBigInteger('related_txn_id')->nullable();
            $table->string('status', 16)->default('SUCCESS');
            $table->string('description', 255)->nullable();
            $table->json('meta')->nullable();
            $table->string('created_by_type', 16)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('logger_admin_activity');
        Schema::create('logger_admin_activity', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('description');
            $table->text('details')->nullable();
            $table->string('userType')->nullable();
            $table->unsignedBigInteger('userId')->nullable();
            $table->string('route')->nullable();
            $table->string('ipAddress')->nullable();
            $table->text('userAgent')->nullable();
            $table->string('locale')->nullable();
            $table->text('referer')->nullable();
            $table->string('methodType')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
}
