<?php

namespace App\Jobs;

use App\Services\Dashboard\DashboardSummarySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncDashboardSummaryBucket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 120;

    /**
     * @var int[]
     */
    public array $backoff = [5, 15, 30, 60];

    public function __construct(
        public string $summaryDate,
        public string $webCode,
        public array $updatedSections = [],
        public ?string $sourceType = null,
        public ?string $sourceId = null,
    ) {}

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->lockKey()))
                ->releaseAfter(2)
                ->expireAfter($this->timeout + 30),
        ];
    }

    public function tags(): array
    {
        return [
            'dashboard-summary',
            'dashboard-summary:' . $this->webCode,
            'dashboard-summary:' . $this->summaryDate,
        ];
    }

    public function handle(DashboardSummarySyncService $syncService): void
    {
        $syncService->syncBucket(
            summaryDate: $this->summaryDate,
            webCode: $this->webCode,
            updatedSections: $this->updatedSections,
            sourceType: $this->sourceType,
            sourceId: $this->sourceId,
        );
    }

    public function failed(Throwable $e): void
    {
        Log::error('Dashboard summary sync job failed', [
            'summary_date' => $this->summaryDate,
            'web_code' => $this->webCode,
            'updated_sections' => $this->updatedSections,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'error' => $e->getMessage(),
        ]);
    }

    private function lockKey(): string
    {
        return sprintf('dashboard-summary:%s:%s', $this->webCode, $this->summaryDate);
    }
}
