<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardSummaryUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $webCode;
    public string $summaryDate;
    public array $updatedSections;
    public string $lastSyncedAt;

    public function __construct(
        string $webCode,
        string $summaryDate,
        array $updatedSections,
        string $lastSyncedAt,
    ) {
        $this->webCode = $webCode;
        $this->summaryDate = $summaryDate;
        $this->updatedSections = $updatedSections;
        $this->lastSyncedAt = $lastSyncedAt;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('dashboard.summary.' . $this->webCode)];
    }

    public function broadcastAs(): string
    {
        return 'dashboard.summary.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'web_code' => $this->webCode,
            'summary_date' => $this->summaryDate,
            'updated_sections' => $this->updatedSections,
            'last_synced_at' => $this->lastSyncedAt,
        ];
    }
}
