<?php

namespace App\Services\Dashboard;

use App\Events\DashboardSummaryUpdated;

class DashboardSummaryBroadcastNotifier
{
    public function notify(string $webCode, string $summaryDate, array $updatedSections, string $lastSyncedAt): void
    {
        broadcast(new DashboardSummaryUpdated(
            webCode: $webCode,
            summaryDate: $summaryDate,
            updatedSections: array_values($updatedSections),
            lastSyncedAt: $lastSyncedAt,
        ));
    }
}
