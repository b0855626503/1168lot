<?php

namespace Tests\Feature\FrontendApi;

use Gametech\Core\Core;
use Gametech\FrontendApi\Http\Controllers\Api\V1\DepositController;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class DepositChannelsControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_channels_normalizes_bank_to_boolean_like_flag(): void
    {
        $core = Mockery::mock(Core::class);
        $core->shouldReceive('getBankTopupCountsNew')->once()->andReturn([
            'bank' => 2,
            'payment' => 0,
            'tw' => 1,
            'slip' => 0,
            'payment_min_sort' => null,
            'tw_min_sort' => 2,
            'slip_min_sort' => null,
            'bank_min_sort' => 1,
        ]);

        $this->app->instance(Core::class, $core);

        $request = Request::create('/api/v1/deposit/channels', 'GET');
        $response = TestResponse::fromBaseResponse(app(DepositController::class)->channels($request));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.deposit.bank', 1);
        $response->assertJsonPath('data.deposit.tw', 1);
    }
}
