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
    private array $columnListingCache = [];

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

        $sourceId = '';
        if (is_object($model) && method_exists($model, 'getKey')) {
            $sourceId = (string) ($model->getKey() ?? '');
        } elseif (is_array($model)) {
            $sourceId = (string) ($model['id'] ?? $model['source_id'] ?? '');
        }

        $this->dispatchBuckets(
            buckets: $buckets,
            sourceType: $domain,
            sourceId: $sourceId,
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
        $lottoPayload = $this->projector->projectLotto($summaryDate, $webCode);

        DB::transaction(function () use ($payload) {
            $payload = $this->filterPayloadByExistingColumns('dashboard_summary_daily', $payload, ['summary_date', 'web_code']);
            if (empty($payload)) {
                return;
            }

            $updateColumns = array_keys($payload);
            $updateColumns = array_values(array_filter($updateColumns, fn ($column) => !in_array($column, ['summary_date', 'web_code'], true)));

            DashboardSummaryDaily::query()->upsert(
                [$payload],
                ['summary_date', 'web_code'],
                $updateColumns,
            );
        });

        DB::transaction(function () use ($lottoPayload, $summaryDate): void {
            $dailyPayload = $this->filterPayloadByExistingColumns(
                'lotto_dashboard_summary_daily',
                (array) ($lottoPayload['daily'] ?? []),
                ['summary_date', 'web_code']
            );
            if (!empty($dailyPayload) && Schema::hasTable('lotto_dashboard_summary_daily')) {
                $updateColumns = array_values(array_filter(array_keys($dailyPayload), fn ($column) => !in_array($column, ['summary_date', 'web_code'], true)));
                DB::table('lotto_dashboard_summary_daily')->upsert(
                    [$dailyPayload],
                    ['summary_date', 'web_code'],
                    $updateColumns
                );
            }

            if (Schema::hasTable('lotto_dashboard_market_summary')) {
                $rows = [];
                foreach ((array) ($lottoPayload['markets'] ?? []) as $row) {
                    $filtered = $this->filterPayloadByExistingColumns(
                        'lotto_dashboard_market_summary',
                        (array) $row,
                        ['summary_date', 'web_code', 'market_id', 'round_id']
                    );
                    if (!empty($filtered)) {
                        $rows[] = $filtered;
                    }
                }

                if (!empty($rows)) {
                    $updateColumns = array_values(array_filter(array_keys($rows[0]), fn ($column) => !in_array($column, ['summary_date', 'web_code', 'market_id', 'round_id'], true)));
                    DB::table('lotto_dashboard_market_summary')->upsert(
                        $rows,
                        ['summary_date', 'web_code', 'market_id', 'round_id'],
                        $updateColumns
                    );
                }
            }

            if (Schema::hasTable('lotto_dashboard_risk_snapshot')) {
                $rows = [];
                foreach ((array) ($lottoPayload['risk'] ?? []) as $row) {
                    $filtered = $this->filterPayloadByExistingColumns(
                        'lotto_dashboard_risk_snapshot',
                        (array) $row,
                        ['web_code', 'market_id', 'round_id', 'bet_type', 'number', 'snapshot_at']
                    );
                    if (!empty($filtered)) {
                        $rows[] = $filtered;
                    }
                }

                if (!empty($rows)) {
                    $updateColumns = array_values(array_filter(array_keys($rows[0]), fn ($column) => !in_array($column, ['web_code', 'market_id', 'round_id', 'bet_type', 'number', 'snapshot_at'], true)));
                    DB::table('lotto_dashboard_risk_snapshot')->upsert(
                        $rows,
                        ['web_code', 'market_id', 'round_id', 'bet_type', 'number', 'snapshot_at'],
                        $updateColumns
                    );
                }
            }

            if (Schema::hasTable('lotto_dashboard_bet_type_summary_daily')) {
                DB::table('lotto_dashboard_bet_type_summary_daily')
                    ->where('summary_date', $summaryDate)
                    ->delete();

                $rows = [];
                foreach ((array) data_get($lottoPayload, 'insights.daily', []) as $row) {
                    $filtered = $this->filterPayloadByExistingColumns(
                        'lotto_dashboard_bet_type_summary_daily',
                        (array) $row,
                        ['summary_date', 'bet_type']
                    );
                    if (!empty($filtered)) {
                        $rows[] = $filtered;
                    }
                }

                if (!empty($rows)) {
                    $updateColumns = array_values(array_filter(
                        array_keys($rows[0]),
                        fn ($column) => !in_array($column, ['summary_date', 'bet_type'], true)
                    ));
                    DB::table('lotto_dashboard_bet_type_summary_daily')->upsert(
                        $rows,
                        ['summary_date', 'bet_type'],
                        $updateColumns
                    );
                }
            }

            if (Schema::hasTable('lotto_dashboard_bet_type_number_daily')) {
                DB::table('lotto_dashboard_bet_type_number_daily')
                    ->where('summary_date', $summaryDate)
                    ->delete();

                $rows = [];
                foreach ((array) data_get($lottoPayload, 'insights.numbers', []) as $row) {
                    $filtered = $this->filterPayloadByExistingColumns(
                        'lotto_dashboard_bet_type_number_daily',
                        (array) $row,
                        ['summary_date', 'bet_type', 'number']
                    );
                    if (!empty($filtered)) {
                        $rows[] = $filtered;
                    }
                }

                if (!empty($rows)) {
                    $updateColumns = array_values(array_filter(
                        array_keys($rows[0]),
                        fn ($column) => !in_array($column, ['summary_date', 'bet_type', 'number'], true)
                    ));
                    DB::table('lotto_dashboard_bet_type_number_daily')->upsert(
                        $rows,
                        ['summary_date', 'bet_type', 'number'],
                        $updateColumns
                    );
                }
            }
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

    /**
     * @param array<string, mixed> $payload
     * @param string[] $requiredColumns
     * @return array<string, mixed>
     */
    private function filterPayloadByExistingColumns(string $table, array $payload, array $requiredColumns): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        $availableColumns = $this->tableColumns($table);
        $filtered = [];
        foreach ($payload as $key => $value) {
            if (!in_array($key, $availableColumns, true)) {
                continue;
            }
            $filtered[$key] = $value;
        }

        foreach ($requiredColumns as $column) {
            if (!array_key_exists($column, $filtered)) {
                return [];
            }
        }

        return $filtered;
    }

    /**
     * @return string[]
     */
    private function tableColumns(string $table): array
    {
        if (!array_key_exists($table, $this->columnListingCache)) {
            $this->columnListingCache[$table] = Schema::hasTable($table)
                ? Schema::getColumnListing($table)
                : [];
        }

        return $this->columnListingCache[$table];
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
