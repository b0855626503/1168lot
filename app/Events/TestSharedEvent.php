<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestSharedEvent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public array $payload = [])
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel(config('app.name') . '_members');
    }

    public function broadcastAs(): string
    {
        return 'public.activity.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}

