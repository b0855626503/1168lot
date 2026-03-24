<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Services\DrawService;
use Illuminate\Console\Command;

class SyncLottoDrawStatusesCommand extends Command
{
    protected $signature = 'lotto:sync-draw-statuses';

    protected $description = 'Sync lotto draw statuses by schedule (draft->open, open->closed)';

    public function handle(DrawService $drawService): int
    {
        $result = $drawService->syncScheduledStatuses();

        $opened = (int) ($result['opened'] ?? 0);
        $closed = (int) ($result['closed'] ?? 0);

        $this->info("Synced lotto draw statuses. opened={$opened}, closed={$closed}");

        return self::SUCCESS;
    }
}
