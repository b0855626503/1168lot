<?php

namespace Tests\Feature\Member;

use Gametech\Core\Core;
use Gametech\Game\Repositories\GameUserEventRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Repositories\MemberCreditLogRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Promotion\Repositories\PromotionRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Fluent;
use Mockery;
use Tests\TestCase;

class MemberCreditLogTranBonusGuardTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_tran_bonus_rejects_unsupported_method(): void
    {
        $member = $this->memberStub([
            'code' => 9001,
            'name' => 'Guard Member',
            'user_name' => 'guard.member',
            'balance' => 0,
            'bonus' => 0,
            'faststart' => 0,
            'cashback' => 0,
            'ic' => 0,
        ]);

        $memberRepository = Mockery::mock(MemberRepository::class);
        $memberRepository->shouldReceive('find')
            ->once()
            ->with(9001)
            ->andReturn($member);

        $gameUserRepository = Mockery::mock(GameUserRepository::class);
        $gameUserEventRepository = Mockery::mock(GameUserEventRepository::class);
        $promotionRepository = Mockery::mock(PromotionRepository::class);

        $gameUserEventRepository->shouldNotReceive('findOneWhere');
        $gameUserRepository->shouldNotReceive('findOneWhere');
        $promotionRepository->shouldNotReceive('findOneWhere');

        $core = Mockery::mock(Core::class);
        $core->shouldReceive('getConfigData')
            ->once()
            ->andReturn((object) [
                'seamless' => 'Y',
                'pro_reset' => 1000,
            ]);

        $this->app->instance(Core::class, $core);
        $this->app->instance('core', $core);

        $repository = new class($memberRepository, $gameUserRepository, $gameUserEventRepository, $promotionRepository, app(Container::class)) extends MemberCreditLogRepository {};

        $result = $repository->tranBonus(['member_code' => 9001], 'SPIN');

        $this->assertFalse($result);
    }

    public function test_tran_bonus_handles_missing_promotion_when_checking_minimum_amount(): void
    {
        $member = $this->memberStub([
            'code' => 9002,
            'name' => 'Null Promotion Member',
            'user_name' => 'null.promotion.member',
            'balance' => 0,
            'bonus' => 0,
            'faststart' => 0,
            'cashback' => 0,
            'ic' => 0,
        ]);

        $gameUser = new Fluent([
            'code' => 7002,
            'balance' => 0,
            'user_name' => 'null.promotion.member',
        ]);

        $gameEvent = new Fluent([
            'code' => 6002,
            'pro_code' => 'PROMO-MISSING',
        ]);

        $memberRepository = Mockery::mock(MemberRepository::class);
        $memberRepository->shouldReceive('find')
            ->once()
            ->with(9002)
            ->andReturn($member);

        $gameUserEventRepository = Mockery::mock(GameUserEventRepository::class);
        $gameUserEventRepository->shouldReceive('findOneWhere')
            ->once()
            ->with([
                'method' => 'BONUS',
                'member_code' => 9002,
                'game_code' => 'GAME01',
                'enable' => 'Y',
            ])
            ->andReturn($gameEvent);

        $gameUserRepository = Mockery::mock(GameUserRepository::class);
        $gameUserRepository->shouldReceive('findOneWhere')
            ->once()
            ->with([
                'member_code' => 9002,
                'game_code' => 'GAME01',
                'enable' => 'Y',
            ])
            ->andReturn($gameUser);

        $promotionRepository = Mockery::mock(PromotionRepository::class);
        $promotionRepository->shouldReceive('findOneWhere')
            ->once()
            ->with(['code' => 'PROMO-MISSING'])
            ->andReturn(null);

        $core = Mockery::mock(Core::class);
        $core->shouldReceive('getConfigData')
            ->once()
            ->andReturn((object) [
                'seamless' => 'Y',
                'pro_reset' => 1000,
            ]);
        $core->shouldReceive('getGame')
            ->once()
            ->andReturn((object) [
                'code' => 'GAME01',
                'name' => 'Primary Game',
            ]);

        $this->app->instance(Core::class, $core);
        $this->app->instance('core', $core);

        $repository = new class($memberRepository, $gameUserRepository, $gameUserEventRepository, $promotionRepository, app(Container::class)) extends MemberCreditLogRepository {};

        $result = $repository->tranBonus(['member_code' => 9002], 'BONUS');

        $this->assertFalse($result);
    }

    private function memberStub(array $attributes): object
    {
        return new class($attributes) extends Fluent
        {
            public function save(): bool
            {
                return true;
            }
        };
    }
}
