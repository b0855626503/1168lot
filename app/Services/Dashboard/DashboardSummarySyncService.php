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
    private const PENDING_BUCKET_KEY_PREFIX = 'dashboard:summary:pending';
    private const RISK_SNAPSHOT_UPSERT_CHUNK_SIZE = 200;

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

            $pendingPayload = $this->mergePendingBucketPayload(
                summaryDate: (string) $bucket['summary_date'],
                webCode: $targetWebCode,
                updatedSections: (array) ($bucket['updated_sections'] ?? []),
                sourceType: $sourceType,
                sourceId: $sourceId,
            );

            SyncDashboardSummaryBucket::dispatch(
                summaryDate: (string) $pendingPayload['summary_date'],
                webCode: $targetWebCode,
                updatedSections: (array) ($pendingPayload['updated_sections'] ?? []),
                sourceType: $pendingPayload['source_type'] ?? $sourceType,
                sourceId: $pendingPayload['source_id'] ?? $sourceId,
            );
        }
    }

    /**
     * @return array{summary_date:string,web_code:string,updated_sections:array<int,string>,source_type:?string,source_id:?string,revision:string}
     */
    public function mergePendingBucketPayload(
        string $summaryDate,
        string $webCode,
        array $updatedSections,
        ?string $sourceType = null,
        ?string $sourceId = null,
    ): array {
        $cacheKey = $this->pendingBucketCacheKey($summaryDate, $webCode);
        $existing = Cache::get($cacheKey, []);
        $existing = is_array($existing) ? $existing : [];

        $resolvedSourceType = $this->normalizeNullableString($sourceType)
            ?? $this->normalizeNullableString($existing['source_type'] ?? null);
        $resolvedSourceId = $this->normalizeNullableString($sourceId)
            ?? $this->normalizeNullableString($existing['source_id'] ?? null);

        $payload = [
            'summary_date' => $summaryDate,
            'web_code' => $webCode,
            'updated_sections' => $this->normalizeUpdatedSections(array_merge(
                (array) ($existing['updated_sections'] ?? []),
                $updatedSections
            )),
            'source_type' => $resolvedSourceType,
            'source_id' => $resolvedSourceId,
            'revision' => sprintf('%.6f', microtime(true)),
        ];

        Cache::put($cacheKey, $payload, now()->addMinutes(10));

        return $payload;
    }

    /**
     * @return array{summary_date:string,web_code:string,updated_sections:array<int,string>,source_type:?string,source_id:?string}
     */
    public function consumePendingBucketPayload(
        string $summaryDate,
        string $webCode,
        array $fallbackUpdatedSections = [],
        ?string $fallbackSourceType = null,
        ?string $fallbackSourceId = null,
    ): array {
        $cacheKey = $this->pendingBucketCacheKey($summaryDate, $webCode);
        $existing = Cache::pull($cacheKey, []);
        $existing = is_array($existing) ? $existing : [];

        return [
            'summary_date' => $summaryDate,
            'web_code' => $webCode,
            'updated_sections' => $this->normalizeUpdatedSections(array_merge(
                $fallbackUpdatedSections,
                (array) ($existing['updated_sections'] ?? [])
            )),
            'source_type' => $this->normalizeNullableString($existing['source_type'] ?? null)
                ?? $this->normalizeNullableString($fallbackSourceType),
            'source_id' => $this->normalizeNullableString($existing['source_id'] ?? null)
                ?? $this->normalizeNullableString($fallbackSourceId),
        ];
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

        if (! Schema::hasTable('dashboard_summary_daily')) {
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
            $updateColumns = array_values(array_filter($updateColumns, fn ($column) => ! in_array($column, ['summary_date', 'web_code'], true)));

            DashboardSummaryDaily::query()->upsert(
                [$payload],
                ['summary_date', 'web_code'],
                $updateColumns,
            );
        });

        DB::transaction(function () use ($lottoPayload, $summaryDate, $webCode): void {
            $dailyPayload = $this->filterPayloadByExistingColumns(
                'lotto_dashboard_summary_daily',
                (array) ($lottoPayload['daily'] ?? []),
                ['summary_date', 'web_code']
            );
            if (! empty($dailyPayload) && Schema::hasTable('lotto_dashboard_summary_daily')) {
                $updateColumns = array_values(array_filter(array_keys($dailyPayload), fn ($column) => ! in_array($column, ['summary_date', 'web_code'], true)));
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
                    if (! empty($filtered)) {
                        $rows[] = $filtered;
                    }
                }

                if (! empty($rows)) {
                    $updateColumns = array_values(array_filter(array_keys($rows[0]), fn ($column) => ! in_array($column, ['summary_date', 'web_code', 'market_id', 'round_id'], true)));
                    DB::table('lotto_dashboard_market_summary')->upsert(
                        $rows,
                        ['summary_date', 'web_code', 'market_id', 'round_id'],
                        $updateColumns
                    );
                }
            }

            $riskRows = [];
            foreach ((array) ($lottoPayload['risk'] ?? []) as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $riskRows[] = $row;
            }

            $this->upsertRiskCurrentRows($riskRows);

            if (Schema::hasTable('lotto_dashboard_risk_snapshot')) {
                $rows = [];
                foreach ($riskRows as $row) {
                    $filtered = $this->filterPayloadByExistingColumns(
                        'lotto_dashboard_risk_snapshot',
                        (array) $row,
                        ['web_code', 'market_id', 'round_id', 'bet_type', 'number', 'snapshot_at']
                    );
                    if (! empty($filtered)) {
                        $rows[] = $filtered;
                    }
                }
                $rows = $this->deduplicateRiskSnapshotRows($rows);

                if (! empty($rows)) {
                    $updateColumns = array_values(array_filter(array_keys($rows[0]), fn ($column) => ! in_array($column, ['web_code', 'market_id', 'round_id', 'bet_type', 'number', 'snapshot_at'], true)));
                    foreach (array_chunk($rows, self::RISK_SNAPSHOT_UPSERT_CHUNK_SIZE) as $chunk) {
                        DB::table('lotto_dashboard_risk_snapshot')->upsert(
                            $chunk,
                            ['web_code', 'market_id', 'round_id', 'bet_type', 'number', 'snapshot_at'],
                            $updateColumns
                        );
                    }
                }
            }

            if (Schema::hasTable('lotto_dashboard_risk_aggregates')) {
                DB::table('lotto_dashboard_risk_aggregates')
                    ->where('web_code', $webCode)
                    ->where('summary_date', $summaryDate)
                    ->delete();

                $rows = [];
                foreach ((array) ($lottoPayload['risk_aggregate'] ?? []) as $row) {
                    $filtered = $this->filterPayloadByExistingColumns(
                        'lotto_dashboard_risk_aggregates',
                        (array) $row,
                        ['web_code', 'summary_date', 'bet_type', 'number']
                    );
                    if (! empty($filtered)) {
                        $rows[] = $filtered;
                    }
                }

                if (! empty($rows)) {
                    $updateColumns = array_values(array_filter(
                        array_keys($rows[0]),
                        fn ($column) => ! in_array($column, ['web_code', 'summary_date', 'bet_type', 'number'], true)
                    ));
                    DB::table('lotto_dashboard_risk_aggregates')->upsert(
                        $rows,
                        ['web_code', 'summary_date', 'bet_type', 'number'],
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
                    if (! empty($filtered)) {
                        $rows[] = $filtered;
                    }
                }

                if (! empty($rows)) {
                    $updateColumns = array_values(array_filter(
                        array_keys($rows[0]),
                        fn ($column) => ! in_array($column, ['summary_date', 'bet_type'], true)
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
                    if (! empty($filtered)) {
                        $rows[] = $filtered;
                    }
                }

                if (! empty($rows)) {
                    $updateColumns = array_values(array_filter(
                        array_keys($rows[0]),
                        fn ($column) => ! in_array($column, ['summary_date', 'bet_type', 'number'], true)
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
     * @param  array<string, mixed>  $payload
     * @param  string[]  $requiredColumns
     * @return array<string, mixed>
     */
    private function filterPayloadByExistingColumns(string $table, array $payload, array $requiredColumns): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $availableColumns = $this->tableColumns($table);
        $filtered = [];
        foreach ($payload as $key => $value) {
            if (! in_array($key, $availableColumns, true)) {
                continue;
            }
            $filtered[$key] = $value;
        }

        foreach ($requiredColumns as $column) {
            if (! array_key_exists($column, $filtered)) {
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
        if (! array_key_exists($table, $this->columnListingCache)) {
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

    private function pendingBucketCacheKey(string $summaryDate, string $webCode): string
    {
        return sprintf('%s:%s:%s', self::PENDING_BUCKET_KEY_PREFIX, $webCode, $summaryDate);
    }

    /**
     * @param  array<int,mixed>  $sections
     * @return array<int,string>
     */
    private function normalizeUpdatedSections(array $sections): array
    {
        $sections = array_values(array_unique(array_filter(array_map(
            static fn ($section) => is_string($section) ? trim($section) : '',
            $sections
        ))));

        sort($sections);

        return $sections;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (! is_scalar($value) || $value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function deduplicateRiskSnapshotRows(array $rows): array
    {
        $deduplicated = [];

        foreach ($rows as $row) {
            $key = implode('|', [
                (string) ($row['web_code'] ?? ''),
                (string) ($row['market_id'] ?? ''),
                (string) ($row['round_id'] ?? ''),
                (string) ($row['bet_type'] ?? ''),
                (string) ($row['number'] ?? ''),
                (string) ($row['snapshot_at'] ?? ''),
            ]);

            $deduplicated[$key] = $row;
        }

        return array_values($deduplicated);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function upsertRiskCurrentRows(array $rows): void
    {
        if (! Schema::hasTable('lotto_dashboard_risk_current')) {
            return;
        }

        $currentRows = [];
        foreach ($rows as $row) {
            $filtered = $this->filterPayloadByExistingColumns(
                'lotto_dashboard_risk_current',
                $row,
                ['web_code', 'market_id', 'round_id', 'bet_type', 'number']
            );

            if (! empty($filtered)) {
                $currentRows[] = $filtered;
            }
        }

        $currentRows = $this->filterRiskCurrentRowsBySemantics($currentRows);
        $currentRows = $this->deduplicateRiskCurrentRows($currentRows);
        if (empty($currentRows)) {
            return;
        }

        $updateColumns = array_values(array_filter(
            array_keys($currentRows[0]),
            fn ($column) => ! in_array($column, ['web_code', 'market_id', 'round_id', 'bet_type', 'number'], true)
        ));

        foreach (array_chunk($currentRows, self::RISK_SNAPSHOT_UPSERT_CHUNK_SIZE) as $chunk) {
            DB::table('lotto_dashboard_risk_current')->upsert(
                $chunk,
                ['web_code', 'market_id', 'round_id', 'bet_type', 'number'],
                $updateColumns
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function filterRiskCurrentRowsBySemantics(array $rows): array
    {
        if (empty($rows) || ! Schema::hasTable('lotto_draws')) {
            return [];
        }

        $roundIds = collect($rows)
            ->pluck('round_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($roundIds)) {
            return [];
        }

        $drawQuery = DB::table('lotto_draws')
            ->whereIn('id', $roundIds);
        if (Schema::hasColumn('lotto_draws', 'result_at')) {
            $drawQuery->whereNull('result_at');
        }
        if (Schema::hasColumn('lotto_draws', 'status')) {
            $drawQuery->where('status', '<>', 'resulted');
        }

        $allowedRoundIds = $drawQuery
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->all();

        if (empty($allowedRoundIds)) {
            return [];
        }

        $allowedRoundMap = array_fill_keys(array_map(static fn (int $id): string => (string) $id, $allowedRoundIds), true);
        $filteredRows = [];

        foreach ($rows as $row) {
            $roundId = (int) ($row['round_id'] ?? 0);
            if ($roundId <= 0 || ! isset($allowedRoundMap[(string) $roundId])) {
                continue;
            }

            $stakeTotal = (float) ($row['stake_total'] ?? 0);
            $payoutIfHit = (float) ($row['payout_if_hit'] ?? 0);
            $liability = (float) ($row['liability'] ?? 0);
            if ($stakeTotal <= 0 && $payoutIfHit <= 0 && $liability <= 0) {
                continue;
            }

            $filteredRows[] = $row;
        }

        return $filteredRows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function deduplicateRiskCurrentRows(array $rows): array
    {
        $deduplicated = [];

        foreach ($rows as $row) {
            $key = implode('|', [
                (string) ($row['web_code'] ?? ''),
                (string) ($row['market_id'] ?? ''),
                (string) ($row['round_id'] ?? ''),
                (string) ($row['bet_type'] ?? ''),
                (string) ($row['number'] ?? ''),
            ]);

            $deduplicated[$key] = $row;
        }

        return array_values($deduplicated);
    }
}
