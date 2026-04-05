<?php

namespace Tests\Feature\FrontendApi;

use Gametech\FrontendApi\Http\Controllers\Api\V1\RealtimeController;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RealtimeConfigControllerTest extends TestCase
{
    public function test_config_returns_member_shared_channel_not_admin_events_channel(): void
    {
        $response = TestResponse::fromBaseResponse(
            app(RealtimeController::class)->config()
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('realtime.shared_member_channel', (string) config('app.name') . '_members');
        $response->assertJsonPath('realtime.private_channel_member_template', (string) config('app.name') . '_members.{member_code}');

        $realtime = (array) $response->json('realtime');
        $events = array_values((array) ($realtime['events'] ?? []));

        $this->assertArrayNotHasKey('public_channel', $realtime);
        $this->assertSame([
            'public.activity.updated',
            'member.activity.updated',
            'member.balance.updated',
        ], $events);
        $this->assertNotContains('lotto.ticket.list.changed', $events);
        $this->assertNotContains('lotto.draw.status.changed', $events);
    }
}
