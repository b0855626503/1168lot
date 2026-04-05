<?php

namespace Tests\Feature\FrontendApi;

use Gametech\FrontendApi\Http\Controllers\Api\V1\MemberController;
use Gametech\Member\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class MemberContributorControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_contributor_returns_rule_more_message_with_display_value_replaced(): void
    {
        $authenticatedMember = new Member();
        $authenticatedMember->code = 52;
        $authenticatedMember->exists = true;

        $resolvedMember = new Member();
        $resolvedMember->code = 52;
        $resolvedMember->referral_code = 'AB12C3D4';
        $resolvedMember->faststart = 250.0;
        $resolvedMember->exists = true;

        $downline = new Member();
        $downline->user_name = '0900000012';
        $downline->name = 'Ref User One';
        $downline->date_regis = Carbon::parse('2026-04-01 08:00:00');
        $downline->setRelation('payment_first', (object) [
            'value' => 300.0,
            'date_approve' => Carbon::parse('2026-04-01 12:33:21'),
            'date_create' => Carbon::parse('2026-04-01 12:30:00'),
        ]);

        $memberRepository = Mockery::mock();
        $memberRepository->shouldReceive('findOrFail')->once()->with(52)->andReturn($resolvedMember);
        $memberRepository->shouldReceive('getAff')->once()->with(52)->andReturn([
            'downs_count' => 3,
            'payments_promotion_credit_bonus_sum' => 120.0,
            'payments_promotion_count' => 2,
        ]);
        $memberRepository->shouldReceive('without')->once()->with('bank')->andReturnSelf();
        $memberRepository->shouldReceive('select')->once()->with(['code', 'upline_code', 'user_name', 'name', 'date_regis'])->andReturnSelf();
        $memberRepository->shouldReceive('where')->once()->with('upline_code', 52)->andReturnSelf();
        $memberRepository->shouldReceive('where')->once()->with('enable', 'Y')->andReturnSelf();
        $memberRepository->shouldReceive('with')->once()->with(Mockery::type('array'))->andReturnSelf();
        $memberRepository->shouldReceive('orderByDesc')->once()->with('date_regis')->andReturnSelf();
        $memberRepository->shouldReceive('get')->once()->andReturn(collect([$downline]));

        $promotionRepository = Mockery::mock();
        $promotionRepository->shouldReceive('findOneWhere')
            ->once()
            ->with(['id' => 'pro_faststart'])
            ->andReturn((object) [
                'length_type' => 'PERCENT',
                'bonus_percent' => 1.5,
                'bonus_price' => 0,
            ]);

        $this->app->instance('Gametech\Member\Repositories\MemberRepository', $memberRepository);
        $this->app->instance('Gametech\Promotion\Repositories\PromotionRepository', $promotionRepository);

        $request = Request::create('/api/v1/member/contributor', 'GET');
        $request->attributes->set('frontend_language', 'en');
        $request->setUserResolver(static fn () => $authenticatedMember);

        $response = TestResponse::fromBaseResponse(
            app(MemberController::class)->contributor($request)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('rule.display_value', '1.50 %');
        $response->assertJsonPath(
            'rule.more_message',
            'Share this referral link to earn 1.50 % % for free! (Just copy and share the link—you earn money instantly). The more you share, the more you earn. You can share the link below through various channels such as your personal website, blog, Facebook, or other social networks. If someone registers via your referral link, they will automatically be under your referral. When a referred customer makes their first deposit without receiving a promotion, you will instantly earn a 1.50 % % commission—no conditions required.'
        );
        $response->assertJsonPath('referrals.0.username', '0900000012');
        $response->assertJsonPath('referrals.0.first_deposit_date', '2026-04-01 12:33:21');
    }
}
