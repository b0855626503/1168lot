<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LottoTicketListChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $action;
    public int $total;
    public string $message;

    public function __construct(string $action, int $total)
    {
        $this->action = $action;
        $this->total = $total;
        $this->message = $action === 'cancelled'
            ? 'มีการคืนโพยหวย'
            : 'มีรายการโพยหวยใหม่';
    }

    public function broadcastOn(): Channel
    {
        return new Channel(config('app.name') . '_events');
    }

    public function broadcastAs(): string
    {
        return 'lotto.ticket.list.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'total' => $this->total,
            'message' => $this->message,
            'datatable_id' => 'lottoTicketsTable',
            'menu_badge_key' => 'lotto_tickets',
            'badge_id' => 'badge_lotto_tickets',
            'path' => '/lotto/tickets',
        ];
    }
}
