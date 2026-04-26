<?php

namespace Tests\Feature\FrontendApi;

use Gametech\Core\Core;
use Gametech\FrontendApi\Http\Controllers\Api\V1\MemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class MemberBalanceControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_balance_succeeds_when_game_context_is_missing(): void
    {
        $authenticatedMember = new class
        {
            public int $code = 3;
        };

        $resolvedMember = new class
        {
            public int $code = 3;
            public float $balance = 1000.0;
            public float $point_deposit = 0.0;
            public int $diamond = 0;
            public float $balance_free = 0.0;
            public float $credit = 0.0;
            public string $user_name = 'boatjunior';
            public float $bonus = 0.0;
            public float $cashback = 0.0;
            public float $ic = 0.0;
            public float $faststart = 0.0;
            public ?string $pic_id = null;
            public int $bank_code = 1;
            public string $name = 'Boat Junior';
            public string $acc_no = '1234567890';
            public string $tel = '0900000000';
            public float $maxwithdraw_day = 0.0;
            public int $down_count = 0;
            public Collection $down;

            public function __construct()
            {
                $this->down = collect([]);
            }

            public function load(string $relation): self
            {
                return $this;
            }

            public function loadCount(string $relation): self
            {
                return $this;
            }
        };

        $memberRepository = Mockery::mock();
        $memberRepository->shouldReceive('findOrFail')->once()->with(3)->andReturn($resolvedMember);
        $memberRepository->shouldReceive('sumWithdrawAmountByDate')->once()->with(3, Mockery::type('string'))->andReturn(100.0);

        $gameRepository = Mockery::mock();
        $gameRepository->shouldReceive('findOneWhere')->never();

        $core = Mockery::mock(Core::class);
        $core->shouldReceive('getConfigData')->andReturn((object) [
            'point_open' => 'N',
            'diamond_open' => 'N',
            'notice' => null,
            'multigame_open' => 'N',
            'wheel_open' => 'N',
            'seamless' => 'N',
            'maxwithdraw_day' => 1000,
            'wallet_withdraw_all' => 'N',
            'deposit_min' => '100.00',
            'minwithdraw' => 100,
            'withdraw_status' => 'Y',
        ]);
        $core->shouldReceive('getGame')->once()->andReturn(null);
        $core->shouldReceive('getSelectPro')->once()->andReturn([]);
        $core->shouldReceive('getBankTopupCountsNew')->once()->andReturn([]);

        $this->app->instance(Core::class, $core);
        $this->app->instance('Gametech\Game\Repositories\GameUserRepository', $gameRepository);
        $this->app->instance('Gametech\Member\Repositories\MemberRepository', $memberRepository);

        $request = Request::create('/api/v1/member/balance', 'GET');
        $request->setUserResolver(static fn () => $authenticatedMember);

        $response = TestResponse::fromBaseResponse(
            app(MemberController::class)->balance($request)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('profile.user_name', 'boatjunior');
        $response->assertJsonPath('profile.deposit_min', '100.00');
        $response->assertJsonPath('profile.withdraw_sum_today', 100);
        $response->assertJsonPath('profile.withdraw_remain_today', 900);
    }

    public function test_balance_resets_promotion_when_seamless_member_balance_is_below_threshold(): void
    {
        $authenticatedMember = new class
        {
            public int $code = 3;
        };

        $resolvedMember = new class
        {
            public int $code = 3;
            public float $balance = 500.0;
            public float $point_deposit = 0.0;
            public int $diamond = 0;
            public float $balance_free = 0.0;
            public float $credit = 0.0;
            public string $user_name = 'boatjunior';
            public float $bonus = 0.0;
            public float $cashback = 0.0;
            public float $ic = 0.0;
            public float $faststart = 0.0;
            public ?string $pic_id = null;
            public int $bank_code = 1;
            public string $name = 'Boat Junior';
            public string $acc_no = '1234567890';
            public string $tel = '0900000000';
            public float $maxwithdraw_day = 0.0;
            public int $down_count = 0;
            public Collection $down;

            public function __construct()
            {
                $this->down = collect([]);
            }

            public function load(string $relation): self
            {
                return $this;
            }

            public function loadCount(string $relation): self
            {
                return $this;
            }
        };

        $gameUser = new class
        {
            public int $code = 77;
            public int $game_code = 11;
            public int $pro_code = 99;
            public float $amount_balance = 200.0;
            public float $withdraw_limit = 150.0;
            public float $withdraw_limit_amount = 150.0;
            public bool $saved = false;

            public function save(): void
            {
                $this->saved = true;
            }
        };

        $memberRepository = Mockery::mock();
        $memberRepository->shouldReceive('findOrFail')->once()->with(3)->andReturn($resolvedMember);
        $memberRepository->shouldReceive('sumWithdrawSeamlessAmountByDate')->once()->with(3, Mockery::type('string'))->andReturn(100.0);

        $gameRepository = Mockery::mock();
        $gameRepository->shouldReceive('findOneWhere')->once()->andReturn($gameUser);

        $memberPromotionLogRepository = Mockery::mock();
        $memberPromotionLogRepository->shouldReceive('where')->once()->with('member_code', 3)->andReturnSelf();
        $memberPromotionLogRepository->shouldReceive('where')->once()->with('complete', 'N')->andReturnSelf();
        $memberPromotionLogRepository->shouldReceive('update')->once()->with(['complete' => 'Y'])->andReturn(1);

        $memberCreditLogRepository = Mockery::mock();
        $memberCreditLogRepository->shouldReceive('create')->once()->with(Mockery::on(function (array $payload): bool {
            return (int) ($payload['member_code'] ?? 0) === 3
                && (int) ($payload['pro_code'] ?? -1) === 0
                && str_contains((string) ($payload['remark'] ?? ''), '1000');
        }));

        $core = Mockery::mock(Core::class);
        $core->shouldReceive('getConfigData')->andReturn((object) [
            'point_open' => 'N',
            'diamond_open' => 'N',
            'notice' => null,
            'multigame_open' => 'N',
            'wheel_open' => 'N',
            'seamless' => 'Y',
            'pro_reset' => 1000,
            'maxwithdraw_day' => 1000,
            'wallet_withdraw_all' => 'N',
            'deposit_min' => 100,
            'minwithdraw' => 100,
            'withdraw_status' => 'Y',
        ]);
        $core->shouldReceive('getGame')->once()->andReturn((object) ['code' => 11]);
        $core->shouldReceive('getSelectPro')->once()->andReturn([]);
        $core->shouldReceive('getBankTopupCountsNew')->once()->andReturn([]);

        $this->app->instance(Core::class, $core);
        $this->app->instance('Gametech\Game\Repositories\GameUserRepository', $gameRepository);
        $this->app->instance('Gametech\Member\Repositories\MemberRepository', $memberRepository);
        $this->app->instance('Gametech\Member\Repositories\MemberPromotionLogRepository', $memberPromotionLogRepository);
        $this->app->instance('Gametech\Member\Repositories\MemberCreditLogRepository', $memberCreditLogRepository);

        $request = Request::create('/api/v1/member/balance', 'GET');
        $request->setUserResolver(static fn () => $authenticatedMember);

        $response = TestResponse::fromBaseResponse(
            app(MemberController::class)->balance($request)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('profile.getpro', false);
        $response->assertJsonPath('profile.amount_balance', 0);
        $this->assertTrue($gameUser->saved);
        $this->assertSame(0, $gameUser->pro_code);
    }
}
