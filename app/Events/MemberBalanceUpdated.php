<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberBalanceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public int $memberCode;
    public float $balance;
    public float $amount;
    public string $reason;
    public int $referenceCode;
    public string $occurredAt;
    public string $message;

    public function __construct(
        int $memberCode,
        float $balance,
        float $amount,
        string $reason,
        int $referenceCode,
        string $message = 'ยอดเงินของคุณถูกอัปเดต'
    ) {
        $this->memberCode = $memberCode;
        $this->balance = $balance;
        $this->amount = $amount;
        $this->reason = $reason;
        $this->referenceCode = $referenceCode;
        $this->occurredAt = now()->format('Y-m-d H:i:s');
        $this->message = $message;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel(config('app.name').'_members.'.$this->memberCode);
    }

    public function broadcastAs(): string
    {
        return 'member.balance.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'member_code' => $this->memberCode,
            'balance' => $this->balance,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'reference_code' => $this->referenceCode,
            'occurred_at' => $this->occurredAt,
            'message' => $this->message,
        ];
    }
}
