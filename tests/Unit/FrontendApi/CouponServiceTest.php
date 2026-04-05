<?php

namespace Tests\Unit\FrontendApi;

use Gametech\Core\Core;
use Gametech\FrontendApi\Services\CouponService;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Repositories\MemberCreditFreeLogRepository;
use Gametech\Member\Repositories\MemberCreditLogRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\BillRepository;
use Gametech\Payment\Repositories\BonusRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class CouponServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('coupons_list');
        Schema::dropIfExists('bonus');
        Schema::dropIfExists('promotions');

        Mockery::close();

        parent::tearDown();
    }

    public function test_list_pending_bonuses_filters_expired_and_maps_payload(): void
    {
        $bonusRepository = Mockery::mock(BonusRepository::class);
        $bonusRepository->shouldReceive('findWhere')
            ->once()
            ->with(['member_code' => 52, 'status' => 'N'])
            ->andReturn(collect([
                (object) [
                    'code' => 'B001',
                    'name' => 'โบนัส 1',
                    'cashback' => 'N',
                    'value' => 100,
                    'turnpro' => 2,
                    'amount_limit' => 3,
                    'rate' => '',
                    'date_expire' => now()->addDay()->format('Y-m-d H:i:s'),
                ],
                (object) [
                    'code' => 'B002',
                    'name' => 'โบนัสหมดอายุ',
                    'cashback' => 'Y',
                    'value' => 50,
                    'turnpro' => 0,
                    'amount_limit' => 0,
                    'rate' => '',
                    'date_expire' => now()->subDay()->format('Y-m-d H:i:s'),
                ],
            ]));

        $service = $this->makeService(
            bonusRepository: $bonusRepository
        );

        $items = $service->listPendingBonuses((object) ['code' => 52]);

        $this->assertCount(1, $items);
        $this->assertSame('B001', $items[0]['code']);
        $this->assertSame('pending_claim', $items[0]['status']);
        $this->assertSame('credit', $items[0]['type']);
    }

    public function test_redeem_code_marks_coupon_used_and_returns_pending_item(): void
    {
        Schema::create('coupons', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
            $table->string('name')->nullable();
            $table->string('enable')->nullable();
            $table->dateTime('refill_start')->nullable();
            $table->dateTime('refill_stop')->nullable();
        });

        Schema::create('coupons_list', function (Blueprint $table): void {
            $table->unsignedBigInteger('code')->primary();
            $table->unsignedBigInteger('coupon_code');
            $table->unsignedBigInteger('member_code')->nullable();
            $table->string('name');
            $table->string('cashback')->nullable();
            $table->decimal('value', 15, 2)->default(0);
            $table->decimal('turnpro', 15, 2)->default(0);
            $table->decimal('amount_limit', 15, 2)->default(0);
            $table->decimal('money', 15, 2)->default(0);
            $table->string('enable')->nullable();
            $table->string('status')->nullable();
            $table->string('norefill')->nullable();
            $table->string('newuser')->nullable();
            $table->integer('date_expire')->default(0);
            $table->date('date_start')->nullable();
            $table->date('date_stop')->nullable();
            $table->dateTime('date_update')->nullable();
        });

        DB::table('coupons')->insert([
            'code' => 100,
            'name' => 'โบนัสต้อนรับ',
            'enable' => 'Y',
        ]);

        DB::table('coupons_list')->insert([
            'code' => 501,
            'coupon_code' => 100,
            'name' => 'ABC123',
            'cashback' => 'N',
            'value' => 150,
            'turnpro' => 1,
            'amount_limit' => 2,
            'money' => 0,
            'enable' => 'Y',
            'status' => 'N',
            'norefill' => 'N',
            'newuser' => 'N',
            'date_expire' => 0,
            'date_start' => now()->toDateString(),
            'date_stop' => now()->toDateString(),
        ]);

        $bonusRepository = Mockery::mock(BonusRepository::class);
        $bonusRepository->shouldReceive('create')
            ->once()
            ->andReturn((object) ['code' => 'BONUS001']);

        $memberCreditLogRepository = Mockery::mock(MemberCreditLogRepository::class);
        $memberCreditLogRepository->shouldReceive('create')->once()->andReturnTrue();

        $service = $this->makeService(
            bonusRepository: $bonusRepository,
            memberCreditLogRepository: $memberCreditLogRepository
        );

        $member = (object) [
            'code' => 52,
            'user_name' => '0855626503',
            'status_pro' => 0,
        ];

        $item = $service->redeemCode($member, 'ABC123', '127.0.0.1', 'th');

        $this->assertSame('BONUS001', $item['code']);
        $this->assertSame('pending_claim', $item['status']);
        $this->assertSame('credit', $item['type']);
        $this->assertSame('Y', DB::table('coupons_list')->where('code', 501)->value('status'));
        $this->assertSame(52, (int) DB::table('coupons_list')->where('code', 501)->value('member_code'));
    }

    public function test_claim_bonus_marks_bonus_as_claimed_and_returns_summary(): void
    {
        Schema::create('bonus', function (Blueprint $table): void {
            $table->string('code')->primary();
            $table->unsignedBigInteger('member_code');
            $table->string('name')->nullable();
            $table->string('cashback')->nullable();
            $table->decimal('value', 15, 2)->default(0);
            $table->decimal('turnpro', 15, 2)->default(0);
            $table->decimal('amount_limit', 15, 2)->default(0);
            $table->string('status')->nullable();
            $table->dateTime('date_expire')->nullable();
        });

        Schema::create('promotions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->unsignedBigInteger('code')->nullable();
        });

        DB::table('bonus')->insert([
            'code' => 'BONUS001',
            'member_code' => 52,
            'name' => 'โบนัสต้อนรับ',
            'cashback' => 'N',
            'value' => 200,
            'turnpro' => 1.5,
            'amount_limit' => 2,
            'status' => 'N',
            'date_expire' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        DB::table('promotions')->insert([
            'id' => 'pro_coupon',
            'code' => 77,
        ]);

        $memberCreditLogRepository = Mockery::mock(MemberCreditLogRepository::class);
        $memberCreditLogRepository->shouldReceive('create')
            ->once()
            ->andReturn((object) ['code' => 9001]);

        $billRepository = Mockery::mock(BillRepository::class);
        $billRepository->shouldReceive('create')
            ->once()
            ->andReturn((object) ['code' => 7001]);

        $gameUser = new class {
            public int $code = 11;
            public string $user_name = 'game-user-1';
            public int $pro_code = 0;
            public float $amount_balance = 0;
            public float $withdraw_limit_amount = 0;
            public bool $saved = false;

            public function save(): void
            {
                $this->saved = true;
            }
        };

        $gameUserRepository = Mockery::mock(GameUserRepository::class);
        $gameUserRepository->shouldReceive('findOneWhere')->once()->andReturn($gameUser);

        $core = Mockery::mock(Core::class);
        $core->shouldReceive('getConfigData')->once()->andReturn((object) [
            'seamless' => 'Y',
            'multigame_open' => 'N',
            'pro_reset' => 0,
        ]);
        $core->shouldReceive('getGame')->once()->andReturn((object) [
            'id' => 1,
            'code' => 99,
        ]);

        $service = $this->makeService(
            memberCreditLogRepository: $memberCreditLogRepository,
            billRepository: $billRepository,
            gameUserRepository: $gameUserRepository,
            core: $core
        );

        $member = new class {
            public int $code = 52;
            public string $user_name = '0855626503';
            public string $name = 'Boat';
            public float $balance = 0;
            public bool $saved = false;

            public function saveQuietly(): void
            {
                $this->saved = true;
            }
        };

        $item = $service->claimBonus($member, 'BONUS001', '127.0.0.1', 'th');

        $this->assertSame('BONUS001', $item['code']);
        $this->assertSame('claimed', $item['status']);
        $this->assertSame(200.0, $item['amount']);
        $this->assertTrue($member->saved);
        $this->assertTrue($gameUser->saved);
        $this->assertSame('Y', DB::table('bonus')->where('code', 'BONUS001')->value('status'));
    }

    private function makeService(
        ?BonusRepository $bonusRepository = null,
        ?BankPaymentRepository $bankPaymentRepository = null,
        ?MemberCreditLogRepository $memberCreditLogRepository = null,
        ?MemberCreditFreeLogRepository $memberCreditFreeLogRepository = null,
        ?GameUserRepository $gameUserRepository = null,
        ?BillRepository $billRepository = null,
        ?Core $core = null
    ): CouponService {
        return new CouponService(
            $bonusRepository ?? Mockery::mock(BonusRepository::class),
            $bankPaymentRepository ?? Mockery::mock(BankPaymentRepository::class),
            $memberCreditLogRepository ?? Mockery::mock(MemberCreditLogRepository::class),
            $memberCreditFreeLogRepository ?? Mockery::mock(MemberCreditFreeLogRepository::class),
            $gameUserRepository ?? Mockery::mock(GameUserRepository::class),
            $billRepository ?? Mockery::mock(BillRepository::class),
            $core ?? Mockery::mock(Core::class)
        );
    }
}
