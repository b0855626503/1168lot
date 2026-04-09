<?php

namespace Tests\Feature\FrontendApi;

use Gametech\FrontendApi\Http\Controllers\Api\V1\RealtimeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class RealtimeAuthControllerTest extends TestCase
{
    public function test_authenticate_handles_array_response_from_broadcast_driver(): void
    {
        $guard = Mockery::mock();
        $guard->shouldReceive('user')->once()->andReturn((object) ['code' => 1]);

        Auth::shouldReceive('guard')
            ->once()
            ->with('customer')
            ->andReturn($guard);

        Broadcast::shouldReceive('auth')
            ->once()
            ->andReturn(['auth' => 'app-key:test-signature']);

        $request = Request::create('/api/v1/realtime/auth', 'POST', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-' . (string) config('app.name') . '_members.1',
        ]);

        $response = TestResponse::fromBaseResponse(
            app(RealtimeController::class)->authenticate($request)
        );

        $response->assertStatus(200);
        $response->assertExactJson([
            'auth' => 'app-key:test-signature',
        ]);
    }
}

