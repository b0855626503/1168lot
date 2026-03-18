<?php

namespace Gametech\LineOA\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LineOAChatTypingUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $conversation_id;

    public int $employee_id;

    public string $employee_name;

    public bool $is_typing;

    public string $at;

    public function __construct(
        int $conversationId,
        int $employeeId,
        string $employeeName,
        bool $isTyping
    ) {
        $this->conversation_id = $conversationId;
        $this->employee_id = $employeeId;
        $this->employee_name = $employeeName;
        $this->is_typing = $isTyping;
        $this->at = now()->toIso8601String();
    }

    public function broadcastOn(): Channel
    {
        return new Channel(config('app.name') . '_events');
    }

    public function broadcastAs(): string
    {
        return 'LineOAChatTypingUpdated';
    }
}
