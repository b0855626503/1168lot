<?php

namespace Tests\Feature\FrontendApi;

use Gametech\Core\Core;
use Gametech\FrontendApi\Http\Controllers\Api\V1\PromotionController;
use Gametech\Member\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class PromotionSelectControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_select_checks_member_balance_for_pro_reset_limit(): void
    {
        $member = new Member;
        $member->code = 52;
        $member->balance = 1500.0;
        $member->exists = true;

        $core = Mockery::mock(Core::class);
        $core->shouldReceive('getConfigData')->once()->andReturn((object) [
            'pro_reset' => 1000,
        ]);
        $this->app->instance(Core::class, $core);

        $promotionRepository = Mockery::mock();
        $promotionRepository->shouldReceive('findOneWhere')
            ->once()
            ->with(['code' => 'pro_allbonus'])
            ->andReturn((object) [
                'code' => 'pro_allbonus',
                'id' => 'pro_allbonus',
                'name_th' => 'All Bonus',
            ]);
        $this->app->instance('Gametech\Promotion\Repositories\PromotionRepository', $promotionRepository);

        $request = Request::create('/api/v1/promotion/select', 'POST', [
            'promotion' => 'pro_allbonus',
        ]);
        $request->setUserResolver(static fn () => $member);

        $response = TestResponse::fromBaseResponse(app(PromotionController::class)->select($request));

        $response->assertStatus(200);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('1000', (string) $response->json('message'));
    }
}
