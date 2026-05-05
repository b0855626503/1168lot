<?php

namespace App\Jobs;

use App\Services\Dashboard\DashboardSummarySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncDashboardSummaryBucket implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 120;
    public int $uniqueFor = 180;

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
        public array $auditContext = [],
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
            'dashboard-summary:'.$this->webCode,
            'dashboard-summary:'.$this->summaryDate,
        ];
    }

    public function handle(DashboardSummarySyncService $syncService): void
    {
        $payload = $syncService->consumePendingBucketPayload(
            summaryDate: $this->summaryDate,
            webCode: $this->webCode,
            fallbackUpdatedSections: $this->updatedSections,
            fallbackSourceType: $this->sourceType,
            fallbackSourceId: $this->sourceId,
            fallbackAuditContext: $this->auditContext,
        );

        $syncService->syncBucket(
            summaryDate: $this->summaryDate,
            webCode: $this->webCode,
            updatedSections: (array) ($payload['updated_sections'] ?? $this->updatedSections),
            sourceType: $payload['source_type'] ?? $this->sourceType,
            sourceId: $payload['source_id'] ?? $this->sourceId,
            auditContext: (array) ($payload['audit_context'] ?? $this->auditContext),
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
            'audit_context' => $this->auditContext,
            'error' => $e->getMessage(),
        ]);
    }

    private function lockKey(): string
    {
        return sprintf('dashboard-summary:%s:%s', $this->webCode, $this->summaryDate);
    }

    public function uniqueId(): string
    {
        return $this->lockKey();
    }
}
