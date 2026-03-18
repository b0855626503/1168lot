<?php

namespace App\Services\Dashboard;

use App\Jobs\SyncDashboardSummaryBucket;
use App\Models\DashboardSummaryDaily;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DashboardSummarySyncService
{
    private const CACHE_VERSION_KEY = 'dashboard:summary:version';

    private DashboardBucketResolver $bucketResolver;
    private DashboardWebCodeResolver $webCodeResolver;
    private DashboardSummaryProjector $projector;
    private DashboardSummaryBroadcastNotifier $notifier;

    public function __construct(
        DashboardBucketResolver $bucketResolver,
        DashboardWebCodeResolver $webCodeResolver,
        DashboardSummaryProjector $projector,
        DashboardSummaryBroadcastNotifier $notifier,
    ) {
        $this->bucketResolver = $bucketResolver;
        $this->webCodeResolver = $webCodeResolver;
        $this->projector = $projector;
        $this->notifier = $notifier;
    }

    public function dispatchForModelChange(string $domain, $model, array $overrideSections = []): void
    {
        $buckets = $this->bucketResolver->resolve($domain, $model, $overrideSections);

        $this->dispatchBuckets(
            buckets: $buckets,
            sourceType: $domain,
            sourceId: (string) ($model->getKey() ?? ''),
        );
    }

    public function dispatchBuckets(array $buckets, string $sourceType, string $sourceId = ''): void
    {
        $defaultWebCode = $this->webCodeResolver->resolve();

        foreach ($buckets as $bucket) {
            if (empty($bucket['summary_date'])) {
                continue;
            }

            $bucketWebCode = trim((string) ($bucket['web_code'] ?? ''));
            $targetWebCode = $bucketWebCode !== ''
                ? $this->webCodeResolver->resolve($bucketWebCode)
                : $defaultWebCode;

            SyncDashboardSummaryBucket::dispatch(
                summaryDate: (string) $bucket['summary_date'],
                webCode: $targetWebCode,
                updatedSections: (array) ($bucket['updated_sections'] ?? []),
                sourceType: $sourceType,
                sourceId: $sourceId,
            );
        }
    }

    public function syncBucket(
        string $summaryDate,
        string $webCode,
        array $updatedSections = [],
        ?string $sourceType = null,
        ?string $sourceId = null,
    ): array {
        $requestedWebCode = trim((string) $webCode);
        $webCode = $requestedWebCode !== ''
            ? $this->webCodeResolver->resolve($requestedWebCode)
            : $this->webCodeResolver->resolve();

        if (!Schema::hasTable('dashboard_summary_daily')) {
            Log::warning('Dashboard summary table does not exist; skip sync', [
                'summary_date' => $summaryDate,
                'web_code' => $webCode,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);

            return [];
        }

        $payload = $this->projector->projectDaily($summaryDate, $webCode);

        DB::transaction(function () use ($payload) {
            $updateColumns = array_keys($payload);
            $updateColumns = array_values(array_filter($updateColumns, fn ($column) => !in_array($column, ['summary_date', 'web_code'], true)));

            DashboardSummaryDaily::query()->upsert(
                [$payload],
                ['summary_date', 'web_code'],
                $updateColumns,
            );
        });

        $this->touchDashboardCacheVersion();

        try {
            $this->notifier->notify(
                webCode: $webCode,
                summaryDate: $summaryDate,
                updatedSections: array_values(array_unique($updatedSections)),
                lastSyncedAt: (string) ($payload['last_synced_at'] ?? now()->toDateTimeString()),
            );
        } catch (\Throwable $e) {
            Log::warning('Dashboard summary broadcast failed', [
                'summary_date' => $summaryDate,
                'web_code' => $webCode,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'error' => $e->getMessage(),
            ]);
        }

        return $payload;
    }

    private function touchDashboardCacheVersion(): void
    {
        try {
            Cache::forever(self::CACHE_VERSION_KEY, sprintf('%.6f', microtime(true)));
        } catch (\Throwable $e) {
            Log::warning('Dashboard cache version touch failed', [
                'cache_key' => self::CACHE_VERSION_KEY,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
