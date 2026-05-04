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
    public ?string $ownerId;
    public ?string $actorId;
    public ?string $amount;

    public ?string $resultMode;
    public ?int $roundNo;

    public function __construct(
        string $action,
        int $total,
        ?string $marketName = null,
        ?string $drawDate = null,
        ?string $ownerId = null,
        ?string $actorId = null,
        $amount = null,
        ?string $resultMode = null,
        ?int $roundNo = null
    )
    {
        $this->action = $action;
        $this->total = $total;
        $this->marketName = $this->normalizeNullableText($marketName);
        $this->drawDate = $this->normalizeNullableText($drawDate);
        $this->ownerId = $this->normalizeNullableText($ownerId);
        $this->actorId = $this->normalizeNullableText($actorId);
        $this->amount = $this->normalizeAmount($amount);
        $this->resultMode = $resultMode;
        $this->roundNo = $roundNo;

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
            'owner_id' => $this->ownerId,
            'actor_id' => $this->actorId,
            'amount' => $this->amount,
            'datatable_id' => 'lottoTicketsTable',
            'menu_badge_key' => 'lotto_tickets',
            'badge_id' => 'badge_lotto_tickets',
            'path' => '/lotto/tickets',
        ];
    }

    private function buildMessage(string $baseMessage): string
    {
        $formatter = new \Gametech\Lotto\Support\LottoMarketDisplayFormatter;
        $subject = $formatter->formatDrawSubject((string) $this->marketName, (string) $this->drawDate, $this->resultMode, $this->roundNo);

        if ($subject === '-' || $subject === '- งวดวันที่ -') {
            return $baseMessage;
        }

        $message = $baseMessage . ': ' . $subject;

        if ($this->action === 'cancelled') {
            if ($this->ownerId !== null) {
                $message .= ' ของ ' . $this->ownerId;
            }

            if ($this->actorId !== null) {
                $message .= ' โดย ' . $this->actorId;
            }

            return $message;
        }

        if ($this->action === 'created') {
            if ($this->actorId !== null) {
                $message .= ' โดย ' . $this->actorId;
            }

            if ($this->amount !== null) {
                $message .= ' จำนวน ' . $this->amount;
            }
        }

        return $message;
    }

    private function normalizeNullableText(?string $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function normalizeAmount($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = (float) $value;
        if (! is_finite($number)) {
            return null;
        }

        if (abs($number - round($number)) < 0.00001) {
            return number_format($number, 0, '.', ',');
        }

        return rtrim(rtrim(number_format($number, 2, '.', ','), '0'), '.');
    }
}
