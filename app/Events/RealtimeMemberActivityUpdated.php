<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RealtimeMemberActivityUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public int $memberCode;
    public string $method;
    public string $event;
    public string $occurredAt;
    public array $data;

    public function __construct(int $memberCode, string $method, string $event, array $data = [])
    {
        $this->memberCode = $memberCode;
        $this->method = strtolower(trim($method));
        $this->event = trim($event);
        $this->occurredAt = now()->format('Y-m-d H:i:s');
        $this->data = $data;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel(config('app.name') . '_members.' . $this->memberCode);
    }

    public function broadcastAs(): string
    {
        return 'member.activity.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'method' => $this->method,
            'event' => $this->event,
            'member_code' => $this->memberCode,
            'occurred_at' => $this->occurredAt,
            'data' => $this->data,
        ];
    }
}
