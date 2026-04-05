<?php

namespace Tests\Feature\FrontendApi;

use Gametech\FrontendApi\Http\Controllers\Api\V1\CouponController;
use Gametech\FrontendApi\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class CouponControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_redeem_returns_pending_coupon_item(): void
    {
        $member = new class {
            public int $code = 52;
        };

        $service = Mockery::mock(CouponService::class);
        $service->shouldReceive('redeemCode')
            ->once()
            ->with($member, 'ABC123', '127.0.0.1', 'th')
            ->andReturn([
                'code' => '9001',
                'status' => 'pending_claim',
            ]);

        $this->app->instance(CouponService::class, $service);

        $request = Request::create('/api/v1/coupon/redeem', 'POST', ['coupon' => 'ABC123']);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->attributes->set('frontend_language', 'th');
        $request->setUserResolver(static fn () => $member);

        $response = TestResponse::fromBaseResponse(
            app(CouponController::class)->redeem($request)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('item.code', '9001');
        $response->assertJsonPath('message', 'รับคูปองสำเร็จ');
    }

    public function test_my_coupons_returns_items_and_summary(): void
    {
        $member = new class {
            public int $code = 52;
        };

        $service = Mockery::mock(CouponService::class);
        $service->shouldReceive('listPendingBonuses')
            ->once()
            ->with($member)
            ->andReturn([
                ['code' => '9001'],
                ['code' => '9002'],
            ]);

        $this->app->instance(CouponService::class, $service);

        $request = Request::create('/api/v1/coupon/my', 'GET');
        $request->setUserResolver(static fn () => $member);

        $response = TestResponse::fromBaseResponse(
            app(CouponController::class)->myCoupons($request)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(2, 'items');
        $response->assertJsonPath('summary.count', 2);
    }

    public function test_claim_returns_claimed_coupon_item(): void
    {
        $member = new class {
            public int $code = 52;
        };

        $service = Mockery::mock(CouponService::class);
        $service->shouldReceive('claimBonus')
            ->once()
            ->with($member, 'BONUS001', '127.0.0.1', 'th')
            ->andReturn([
                'code' => 'BONUS001',
                'status' => 'claimed',
            ]);

        $this->app->instance(CouponService::class, $service);

        $request = Request::create('/api/v1/coupon/my/BONUS001/claim', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->attributes->set('frontend_language', 'th');
        $request->setUserResolver(static fn () => $member);

        $response = TestResponse::fromBaseResponse(
            app(CouponController::class)->claim($request, 'BONUS001')
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('item.status', 'claimed');
        $response->assertJsonPath('message', 'รับโบนัสจากคูปองสำเร็จ');
    }
}
