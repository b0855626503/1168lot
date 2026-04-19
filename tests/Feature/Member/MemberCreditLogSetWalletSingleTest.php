<?php

namespace Tests\Feature\Member;

use Gametech\Core\Core;
use Gametech\Game\Repositories\GameUserEventRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Repositories\MemberCreditLogRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Promotion\Repositories\PromotionRepository;
use Illuminate\Container\Container;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Fluent;
use Mockery;
use Tests\TestCase;

class MemberCreditLogSetWalletSingleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('members_credit_log');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('logger_user_activity');
        Mockery::close();

        parent::tearDown();
    }

    public function test_set_wallet_single_records_wallet_transaction_for_admin_credit_adjust(): void
    {
        $member = new class(['code' => 9001, 'name' => 'Wallet Member', 'user_name' => 'wallet.member', 'balance' => 100]) extends Fluent
        {
            public function save(): bool
            {
                return true;
            }
        };
        $gameUser = new class(['code' => 7001, 'user_name' => 'wallet.member', 'balance' => 100]) extends Fluent
        {
            public function save(): bool
            {
                return true;
            }
        };

        $memberRepository = Mockery::mock(MemberRepository::class);
        $memberRepository->shouldReceive('find')
            ->with(9001)
            ->twice()
            ->andReturn($member, null);

        $gameUserRepository = Mockery::mock(GameUserRepository::class);
        $gameUserRepository->shouldReceive('findOneWhere')
            ->once()
            ->andReturn($gameUser);
        $gameUserRepository->shouldReceive('UserDeposit')
            ->once()
            ->with('GAME01', 'wallet.member', 50.0, false)
            ->andReturn([
                'success' => true,
                'before' => 100.0,
                'after' => 150.0,
                'ref_id' => 'SETWALLET-REF-01',
            ]);

        $this->app->instance('Gametech\Payment\Repositories\BillRepository', new class
        {
            public function create(array $data): object
            {
                return (object) ['code' => 5001, 'payload' => $data];
            }
        });

        $core = Mockery::mock(Core::class);
        $core->shouldReceive('getGame')
            ->andReturn((object) ['code' => 'GAME01', 'name' => 'Primary Game']);
        $this->app->instance(Core::class, $core);

        $repository = new class($memberRepository, $gameUserRepository, Mockery::mock(GameUserEventRepository::class), Mockery::mock(PromotionRepository::class), app(Container::class)) extends MemberCreditLogRepository
        {
            public function create(array $attributes)
            {
                return (object) ['code' => 8001, 'attributes' => $attributes];
            }
        };

        $result = $repository->setWalletSingle([
            'member_code' => 9001,
            'amount' => 50.0,
            'method' => 'D',
            'kind' => 'SETWALLET',
            'remark' => 'ปรับเครดิตโดยทีมงาน',
            'emp_code' => 100,
            'emp_name' => 'Admin One',
            'refer_code' => 9001,
            'refer_table' => 'members',
        ]);

        $this->assertTrue($result);
        $this->assertSame(1, DB::table('wallet_transactions')->count());
        $this->assertDatabaseHas('wallet_transactions', [
            'member_id' => 9001,
            'direction' => 'CREDIT',
            'ref_type' => 'SETWALLET',
            'status' => 'SUCCESS',
            'description' => 'Member wallet adjust via setWalletSingle',
            'created_by_type' => 'admin',
            'created_by_id' => 100,
        ]);
    }

    public function test_set_wallet_seamless_records_wallet_transaction_for_admin_credit_adjust(): void
    {
        $member = new class(['code' => 9002, 'name' => 'Seamless Member', 'user_name' => 'seamless.member', 'balance' => 200]) extends Fluent
        {
            public function save(): bool
            {
                return true;
            }
        };
        $gameUser = new class(['code' => 7002, 'user_name' => 'seamless.member', 'balance' => 200]) extends Fluent
        {
            public function save(): bool
            {
                return true;
            }
        };

        $memberRepository = Mockery::mock(MemberRepository::class);
        $memberRepository->shouldReceive('query')
            ->once()
            ->andReturn($this->lockableQueryReturning($member));
        $memberRepository->shouldReceive('find')
            ->with(9002)
            ->once()
            ->andReturn(null);

        $gameUserRepository = Mockery::mock(GameUserRepository::class);
        $gameUserRepository->shouldReceive('query')
            ->once()
            ->andReturn($this->lockableQueryReturning($gameUser));

        $this->app->instance('Gametech\Payment\Repositories\BillRepository', new class
        {
            public function create(array $data): object
            {
                return (object) ['code' => 5002, 'payload' => $data];
            }
        });

        $core = Mockery::mock(Core::class);
        $core->shouldReceive('getGame')
            ->andReturn((object) ['code' => 'GAME01', 'name' => 'Primary Game']);
        $this->app->instance(Core::class, $core);

        $repository = new class($memberRepository, $gameUserRepository, Mockery::mock(GameUserEventRepository::class), Mockery::mock(PromotionRepository::class), app(Container::class)) extends MemberCreditLogRepository
        {
            private int $nextCode = 9100;

            public function create(array $attributes)
            {
                $this->nextCode++;

                return (object) ['code' => $this->nextCode, 'attributes' => $attributes];
            }
        };

        $result = $repository->setWalletSeamless([
            'member_code' => 9002,
            'amount' => 75.0,
            'method' => 'D',
            'kind' => 'SETWALLET',
            'remark' => 'เพิ่มเครดิต seamless',
            'emp_code' => 101,
            'emp_name' => 'Admin Two',
            'refer_code' => 9002,
            'refer_table' => 'members',
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('wallet_transactions', [
            'member_id' => 9002,
            'direction' => 'CREDIT',
            'ref_type' => 'SETWALLET',
            'ref_id' => 9101,
            'created_by_type' => 'admin',
            'created_by_id' => 101,
        ]);
    }

    public function test_set_wallet_seamless_withdraw_records_wallet_transaction_for_admin_credit_adjust(): void
    {
        $member = new class(['code' => 9003, 'name' => 'Withdraw Member', 'user_name' => 'withdraw.member', 'balance' => 300]) extends Fluent
        {
            public function save(): bool
            {
                return true;
            }
        };
        $gameUser = new class(['code' => 7003, 'user_name' => 'withdraw.member', 'balance' => 300, 'pro_code' => 0, 'amount_balance' => 0, 'withdraw_limit' => 0, 'withdraw_limit_amount' => 0]) extends Fluent
        {
            public function save(): bool
            {
                return true;
            }
        };

        $memberRepository = Mockery::mock(MemberRepository::class);
        $memberRepository->shouldReceive('find')
            ->with(9003)
            ->once()
            ->andReturn($member);

        $gameUserRepository = Mockery::mock(GameUserRepository::class);
        $gameUserRepository->shouldReceive('findOneWhere')
            ->once()
            ->andReturn($gameUser);

        $this->app->instance('Gametech\Payment\Repositories\BillRepository', new class
        {
            public function create(array $data): object
            {
                return (object) ['code' => 5003, 'payload' => $data];
            }
        });

        Notification::shouldReceive('send')->once();

        $core = Mockery::mock(Core::class);
        $core->shouldReceive('getGame')
            ->andReturn((object) ['code' => 'GAME01', 'name' => 'Primary Game']);
        $this->app->instance(Core::class, $core);

        $repository = new class($memberRepository, $gameUserRepository, Mockery::mock(GameUserEventRepository::class), Mockery::mock(PromotionRepository::class), app(Container::class)) extends MemberCreditLogRepository
        {
            private int $nextCode = 9200;

            public function create(array $attributes)
            {
                $this->nextCode++;

                return (object) ['code' => $this->nextCode, 'attributes' => $attributes];
            }
        };

        $result = $repository->setWalletSeamlessWithdraw([
            'member_code' => 9003,
            'amount' => 120.0,
            'amount_balance' => 0,
            'withdraw_limit' => 0,
            'withdraw_limit_amount' => 0,
            'method' => 'D',
            'kind' => 'SETWALLET',
            'remark' => 'คืนยอดโดยระบบ seamless',
            'emp_code' => 102,
            'emp_name' => 'Admin Three',
            'refer_code' => 88001,
            'refer_table' => 'withdraws',
            'pro_name' => '',
            'pro_code' => 0,
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('wallet_transactions', [
            'member_id' => 9003,
            'direction' => 'CREDIT',
            'ref_type' => 'SETWALLET',
            'ref_id' => 9201,
            'created_by_type' => 'admin',
            'created_by_id' => 102,
        ]);
    }

    private function prepareSchema(): void
    {
        Schema::dropIfExists('members_credit_log');
        Schema::create('members_credit_log', function (Blueprint $table): void {
            $table->bigIncrements('code');
            $table->unsignedBigInteger('member_code')->nullable();
            $table->string('refer_table', 32)->nullable();
            $table->unsignedBigInteger('refer_code')->nullable();
            $table->string('kind', 32)->nullable();
            $table->string('credit_type', 2)->nullable();
            $table->string('enable', 1)->default('Y');
            $table->timestamps();
        });

        Schema::dropIfExists('bills');
        Schema::create('bills', function (Blueprint $table): void {
            $table->bigIncrements('code');
            $table->unsignedBigInteger('member_code')->nullable();
            $table->string('refer_table', 32)->nullable();
            $table->unsignedBigInteger('refer_code')->nullable();
            $table->string('method', 32)->nullable();
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

        Schema::dropIfExists('logger_user_activity');
        Schema::create('logger_user_activity', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('description');
            $table->text('details')->nullable();
            $table->string('userType');
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

    private function lockableQueryReturning(object $result): object
    {
        return new class($result)
        {
            public function __construct(private object $result) {}

            public function where($column, $operator = null, $value = null): self
            {
                return $this;
            }

            public function lockForUpdate(): self
            {
                return $this;
            }

            public function first(): object
            {
                return $this->result;
            }
        };
    }
}
