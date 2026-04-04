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
    public ?string $marketName;
    public ?string $drawDate;

    public function __construct(string $action, int $total, ?string $marketName = null, ?string $drawDate = null)
    {
        $this->action = $action;
        $this->total = $total;
        $this->marketName = $this->normalizeNullableText($marketName);
        $this->drawDate = $this->normalizeNullableText($drawDate);

        $baseMessage = match ($action) {
            'cancelled' => 'มีการคืนโพยหวย',
            'resulted' => 'มีโพยหวยถูกตัดสินผลแล้ว',
            default => 'มีรายการโพยหวยใหม่',
        };

        $this->message = $this->buildMessage($baseMessage);
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
            'market_name' => $this->marketName,
            'draw_date' => $this->drawDate,
            'datatable_id' => 'lottoTicketsTable',
            'menu_badge_key' => 'lotto_tickets',
            'badge_id' => 'badge_lotto_tickets',
            'path' => '/lotto/tickets',
        ];
    }

    private function buildMessage(string $baseMessage): string
    {
        $segments = [];

        if ($this->marketName !== null) {
            $segments[] = $this->marketName;
        }

        if ($this->drawDate !== null) {
            $segments[] = 'งวดวันที่ ' . $this->drawDate;
        }

        if ($segments === []) {
            return $baseMessage;
        }

        return $baseMessage . ': ' . implode(' ', $segments);
    }

    private function normalizeNullableText(?string $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
