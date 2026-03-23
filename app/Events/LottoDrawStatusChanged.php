<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LottoDrawStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $drawId;
    public string $marketName;
    public string $drawDate;
    public string $status;
    public string $statusLabel;
    public string $actor;
    public string $changedAt;
    public string $message;
    public string $datatableId;
    public string $path;

    public function __construct(
        int $drawId,
        string $marketName,
        string $drawDate,
        string $status,
        string $statusLabel,
        string $actor,
        string $changedAt
    ) {
        $this->drawId = $drawId;
        $this->marketName = $marketName;
        $this->drawDate = $drawDate;
        $this->status = $status;
        $this->statusLabel = $statusLabel;
        $this->actor = $actor;
        $this->changedAt = $changedAt;
        $this->message = sprintf(
            'หวยงวดที่ #%d %s (%s) ถูกปรับเป็นสถานะ %s โดย %s เมื่อ %s',
            $drawId,
            $marketName,
            $drawDate,
            $statusLabel,
            $actor,
            $changedAt
        );
        $this->datatableId = 'lottoDrawsTable';
        $this->path = '/lotto/draws';
    }

    public function broadcastOn(): Channel
    {
        return new Channel(config('app.name') . '_events');
    }

    public function broadcastAs(): string
    {
        return 'lotto.draw.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'draw_id' => $this->drawId,
            'market_name' => $this->marketName,
            'draw_date' => $this->drawDate,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'actor' => $this->actor,
            'changed_at' => $this->changedAt,
            'message' => $this->message,
            'datatable_id' => $this->datatableId,
            'path' => $this->path,
        ];
    }
}

