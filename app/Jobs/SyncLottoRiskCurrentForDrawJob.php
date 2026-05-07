<?php

namespace App\Jobs;

use App\Services\Dashboard\DashboardSummarySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncLottoRiskCurrentForDrawJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 90;
    public int $uniqueFor = 120;

    /**
     * @var int[]
     */
    public array $backoff = [2, 5, 10, 20];

    public function __construct(
        public int $drawId,
        public ?string $webCode = null,
        public ?string $sourceType = null,
        public ?string $sourceId = null,
        public array $auditContext = [],
    ) {}

    public function tags(): array
    {
        return [
            'lotto-risk-current',
            'lotto-risk-current:draw:'.$this->drawId,
        ];
    }

    public function handle(DashboardSummarySyncService $syncService): void
    {
        $syncService->syncRiskCurrentForDraw(
            drawId: $this->drawId,
            webCode: $this->webCode,
            sourceType: $this->sourceType,
            sourceId: $this->sourceId,
            auditContext: $this->auditContext,
        );
    }

    public function failed(Throwable $e): void
    {
        Log::error('Lotto risk current draw sync job failed', [
            'draw_id' => $this->drawId,
            'web_code' => $this->webCode,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'audit_context' => $this->auditContext,
            'error' => $e->getMessage(),
        ]);
    }

    public function uniqueId(): string
    {
        return sprintf('lotto-risk-current:draw:%d:%s', $this->drawId, (string) ($this->webCode ?? 'default'));
    }
}
