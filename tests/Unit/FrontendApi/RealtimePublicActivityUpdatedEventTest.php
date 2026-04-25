<?php

namespace Tests\Unit\FrontendApi;

use App\Events\RealtimePublicActivityUpdated;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class RealtimePublicActivityUpdatedEventTest extends TestCase
{
    public function test_public_activity_updated_broadcasts_to_shared_member_channel(): void
    {
        $event = new RealtimePublicActivityUpdated(
            'lotto',
            'lotto.ticket.list.changed',
            ['action' => 'created']
        );

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-'.config('app.name').'_members', $channel->name);
        $this->assertSame('public.activity.updated', $event->broadcastAs());
    }

    public function test_public_activity_updated_includes_message_when_provided(): void
    {
        $event = new RealtimePublicActivityUpdated(
            'lotto',
            'lotto.draw_resulted',
            ['draw_id' => 120],
            'หวยออมสิน งวดวันที่ 25 ออกผลแล้ว'
        );

        $payload = $event->broadcastWith();

        $this->assertSame('หวยออมสิน งวดวันที่ 25 ออกผลแล้ว', $payload['message']);
        $this->assertSame(['draw_id' => 120], $payload['data']);
    }
}
