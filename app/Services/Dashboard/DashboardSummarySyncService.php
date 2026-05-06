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
    private LottoRiskSnapshotWritePolicy $lottoRiskSnapshotWritePolicy;
    private array $columnListingCache = [];

    public function __construct(
        DashboardBucketResolver $bucketResolver,
        DashboardWebCodeResolver $webCodeResolver,
        DashboardSummaryProjector $projector,
        DashboardSummaryBroadcastNotifier $notifier,
        LottoRiskSnapshotWritePolicy $lottoRiskSnapshotWritePolicy,
    ) {
        $this->bucketResolver = $bucketResolver;
        $this->webCodeResolver = $webCodeResolver;
        $this->projector = $projector;
        $this->notifier = $notifier;
        $this->lottoRiskSnapshotWritePolicy = $lottoRiskSnapshotWritePolicy;
    }

    public function dispatchForModelChange(
        string $domain,
        $model,
        array $overrideSections = [],
        ?string $sourceTypeOverride = null,
        array $auditContext = [],
    ): void {
        $buckets = $this->bucketResolver->resolve($domain, $model, $overrideSections);

        $sourceId = '';
        if (is_object($model) && method_exists($model, 'getKey')) {
            $sourceId = (string) ($model->getKey() ?? '');
        } elseif (is_array($model)) {
            $sourceId = (string) ($model['id'] ?? $model['source_id'] ?? '');
        }

        $this->dispatchBuckets(
            buckets: $buckets,
            sourceType: $sourceTypeOverride ?? $domain,
            sourceId: $sourceId,
            auditContext: $auditContext,
        );
    }

    public function dispatchBuckets(array $buckets, string $sourceType, string $sourceId = '', array $auditContext = []): void
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
                auditContext: $auditContext,
            );

            SyncDashboardSummaryBucket::dispatch(
                summaryDate: (string) $pendingPayload['summary_date'],
                webCode: $targetWebCode,
                updatedSections: (array) ($pendingPayload['updated_sections'] ?? []),
                sourceType: $pendingPayload['source_type'] ?? $sourceType,
                sourceId: $pendingPayload['source_id'] ?? $sourceId,
                auditContext: (array) ($pendingPayload['audit_context'] ?? []),
            );
        }
    }

    /**
     * @return array{summary_date:string,web_code:string,updated_sections:array<int,string>,source_type:?string,source_id:?string,audit_context:array<string,mixed>,revision:string}
     */
    public function mergePendingBucketPayload(
        string $summaryDate,
        string $webCode,
        array $updatedSections,
        ?string $sourceType = null,
        ?string $sourceId = null,
        array $auditContext = [],
    ): array {
        $cacheKey = $this->pendingBucketCacheKey($summaryDate, $webCode);
        $existing = Cache::get($cacheKey, []);
        $existing = is_array($existing) ? $existing : [];

        $resolvedSourceType = $this->normalizeNullableString($sourceType)
            ?? $this->normalizeNullableString($existing['source_type'] ?? null);
        $resolvedSourceId = $this->normalizeNullableString($sourceId)
            ?? $this->normalizeNullableString($existing['source_id'] ?? null);
        $resolvedAuditContext = $this->mergeAuditContext(
            (array) ($existing['audit_context'] ?? []),
            $auditContext
        );

        $payload = [
            'summary_date' => $summaryDate,
            'web_code' => $webCode,
            'updated_sections' => $this->normalizeUpdatedSections(array_merge(
                (array) ($existing['updated_sections'] ?? []),
                $updatedSections
            )),
            'source_type' => $resolvedSourceType,
            'source_id' => $resolvedSourceId,
            'audit_context' => $resolvedAuditContext,
            'revision' => sprintf('%.6f', microtime(true)),
        ];

        Cache::put($cacheKey, $payload, now()->addMinutes(10));

        return $payload;
    }

    /**
     * @return array{summary_date:string,web_code:string,updated_sections:array<int,string>,source_type:?string,source_id:?string,audit_context:array<string,mixed>}
     */
    public function consumePendingBucketPayload(
        string $summaryDate,
        string $webCode,
        array $fallbackUpdatedSections = [],
        ?string $fallbackSourceType = null,
        ?string $fallbackSourceId = null,
        array $fallbackAuditContext = [],
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
            'audit_context' => $this->mergeAuditContext(
                $fallbackAuditContext,
                (array) ($existing['audit_context'] ?? [])
            ),
        ];
    }

    public function syncBucket(
        string $summaryDate,
        string $webCode,
        array $updatedSections = [],
        ?string $sourceType = null,
        ?string $sourceId = null,
        array $auditContext = [],
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

            // BOA-229: snapshot runtime write removed. Risk dashboard reads from
            // lotto_dashboard_risk_current only. writeRiskSnapshot() is retained as
            // a deprecated legacy helper and is intentionally NOT invoked from any
            // runtime path (sync/rebuild/observer). Do not re-enable.

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

    private function resolveRiskSnapshotSource(?string $sourceType): string
    {
        $normalized = strtolower(trim((string) $sourceType));
        if ($normalized === '') {
            return 'scheduled';
        }

        return match ($normalized) {
            'draw_closed',
            'lotto.draw_closed' => 'draw_closed',
            'draw_resulted',
            'lotto.draw_resulted' => 'draw_resulted',
            'manual_audit',
            'lotto.manual_audit' => 'manual_audit',
            default => $normalized,
        };
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function mergeAuditContext(array $base, array $override): array
    {
        $reason = $this->normalizeNullableString($override['reason'] ?? null)
            ?? $this->normalizeNullableString($base['reason'] ?? null);
        $actorIdRaw = $override['actor_id'] ?? $base['actor_id'] ?? null;
        $actorId = is_numeric($actorIdRaw) ? (int) $actorIdRaw : null;

        $context = [];
        if ($reason !== null) {
            $context['reason'] = $reason;
        }
        if ($actorId !== null && $actorId > 0) {
            $context['actor_id'] = $actorId;
        }

        return $context;
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

    /**
     * @param  array<int, array<string, mixed>>  $riskRows
     * @return array{web_code?:string,market_id?:int,round_id?:int}
     */
    private function extractRiskRowContext(array $riskRows): array
    {
        $firstRow = $riskRows[0] ?? null;
        if (! is_array($firstRow)) {
            return [];
        }

        return [
            'web_code' => isset($firstRow['web_code']) ? (string) $firstRow['web_code'] : null,
            'market_id' => isset($firstRow['market_id']) ? (int) $firstRow['market_id'] : null,
            'round_id' => isset($firstRow['round_id']) ? (int) $firstRow['round_id'] : null,
        ];
    }

    /**
     * @deprecated BOA-229: Snapshot runtime write has been disabled. This helper
     * is retained only for legacy callers (e.g. direct admin/legacy maintenance
     * tooling) and MUST NOT be invoked from any runtime dashboard sync path.
     * The runtime risk dashboard now reads exclusively from
     * lotto_dashboard_risk_current. Calls into this method will emit a warning
     * log and the LOTTO_RISK_SNAPSHOT_LEGACY_WRITE_ENABLED env flag is no
     * longer honored as a runtime rollback path.
     *
     * @param  array<int, array<string, mixed>>  $riskRows
     * @param  array<string, mixed>  $context
     * @return array{allowed:bool, source:string, has_meaningful_risk:bool, reason:string}
     */
    public function writeRiskSnapshot(array $riskRows, array $context = []): array
    {
        Log::warning('lotto_risk_snapshot_write_deprecated', [
            'message' => 'lotto_risk_snapshot_write_deprecated',
            'reason' => 'BOA-229 snapshot runtime write disabled; legacy helper invoked',
            'source' => (string) ($context['source'] ?? 'unknown'),
            'class' => (string) ($context['class'] ?? static::class),
            'file' => (string) ($context['file'] ?? __FILE__),
        ]);

        if (! Schema::hasTable('lotto_dashboard_risk_snapshot')) {
            return [
                'allowed' => false,
                'source' => (string) ($context['source'] ?? 'scheduled'),
                'has_meaningful_risk' => false,
                'reason' => 'snapshot_table_missing',
            ];
        }

        $source = $this->resolveRiskSnapshotSource((string) ($context['source'] ?? 'scheduled'));
        $context['source'] = $source;
        $snapshotWriteDecision = $this->lottoRiskSnapshotWritePolicy->evaluate($riskRows, $context);

        $riskRowContext = $this->extractRiskRowContext($riskRows);
        $logContext = [
            'source' => $source,
            'web_code' => (string) ($riskRowContext['web_code'] ?? ($context['web_code'] ?? '')),
            'market_id' => $riskRowContext['market_id'] ?? null,
            'round_id' => $riskRowContext['round_id'] ?? null,
            'risk_rows_count' => count($riskRows),
            'has_meaningful_risk' => $snapshotWriteDecision['has_meaningful_risk'],
            'reason' => $snapshotWriteDecision['reason'],
        ];

        if ($this->isLegacyRiskSnapshotSource($source) && ! $this->isLegacySnapshotWriteEnabled()) {
            Log::warning('lotto_snapshot_legacy_write_blocked', [
                'message' => 'lotto_snapshot_legacy_write_blocked',
                'file' => (string) ($context['file'] ?? __FILE__),
                'class' => (string) ($context['class'] ?? static::class),
                'reason' => 'legacy_write_disabled',
                'source' => $source,
            ]);

            return [
                'allowed' => false,
                'source' => $source,
                'has_meaningful_risk' => $snapshotWriteDecision['has_meaningful_risk'],
                'reason' => 'legacy_write_disabled',
            ];
        }

        if (! $snapshotWriteDecision['allowed']) {
            Log::info('lotto_risk_snapshot_write_skipped', $logContext);

            return $snapshotWriteDecision;
        }

        Log::info('lotto_risk_snapshot_write_allowed', $logContext);

        $checkpointAt = now()->startOfSecond()->toDateTimeString();
        $snapshotColumns = $this->tableColumns('lotto_dashboard_risk_snapshot');
        $hasCreatedAt = in_array('created_at', $snapshotColumns, true);
        $hasUpdatedAt = in_array('updated_at', $snapshotColumns, true);

        $rows = [];
        foreach ($riskRows as $row) {
            $filtered = $this->filterPayloadByExistingColumns(
                'lotto_dashboard_risk_snapshot',
                (array) $row,
                ['web_code', 'market_id', 'round_id', 'bet_type', 'number', 'snapshot_at']
            );
            if (empty($filtered)) {
                continue;
            }

            $filtered['snapshot_at'] = $checkpointAt;
            if ($hasCreatedAt) {
                $filtered['created_at'] = $checkpointAt;
            }
            if ($hasUpdatedAt) {
                $filtered['updated_at'] = $checkpointAt;
            }

            $rows[] = $filtered;
        }
        $rows = $this->deduplicateRiskSnapshotRows($rows);

        if (empty($rows)) {
            return $snapshotWriteDecision;
        }

        foreach (array_chunk($rows, self::RISK_SNAPSHOT_UPSERT_CHUNK_SIZE) as $chunk) {
            DB::table('lotto_dashboard_risk_snapshot')->insertOrIgnore($chunk);
        }

        return $snapshotWriteDecision;
    }

    private function isLegacySnapshotWriteEnabled(): bool
    {
        return (bool) config('dashboard.lotto.legacy_snapshot_write_enabled', false);
    }

    private function isLegacyRiskSnapshotSource(string $source): bool
    {
        return ! in_array($source, ['scheduled', 'draw_closed', 'draw_resulted', 'manual_audit'], true);
    }
}
