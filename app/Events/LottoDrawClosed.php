<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LottoDrawClosed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $drawId;
    public string $marketName;
    public string $drawDate;
    public string $message;
    public string $datatableId;
    public string $path;

    public function __construct(int $drawId, string $marketName, string $drawDate)
    {
        $this->drawId = $drawId;
        $this->marketName = $marketName;
        $this->drawDate = $drawDate;
        $this->message = sprintf('งวดหวย %s (%s) ปิดรับแล้ว', $marketName, $drawDate);
        $this->datatableId = 'lottoDrawsTable';
        $this->path = '/lotto/draws';
    }

    public function broadcastOn(): Channel
    {
        return new Channel(config('app.name') . '_events');
    }

    public function broadcastAs(): string
    {
        return 'lotto.draw.closed';
    }

    public function broadcastWith(): array
    {
        return [
            'draw_id' => $this->drawId,
            'market_name' => $this->marketName,
            'draw_date' => $this->drawDate,
            'message' => $this->message,
            'datatable_id' => $this->datatableId,
            'path' => $this->path,
        ];
    }
}

