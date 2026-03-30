<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LottoResultSource;
use Gametech\Lotto\Services\InternalResultSources\InternalResultSourceMigrationPlanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateInternalResultEndpointsCommand extends Command
{
    protected $signature = 'lotto:migrate-internal-result-endpoints
        {--apply : Persist migrated endpoint configuration}
        {--source-id=* : Limit to specific source IDs}
        {--report-only : Skip write even when --apply is passed}';

    protected $description = 'Plan or apply migration/backfill from upstream endpoints to internal lottery result endpoints';

    public function handle(InternalResultSourceMigrationPlanner $planner): int
    {
        $apply = (bool) $this->option('apply') && ! (bool) $this->option('report-only');
        $internalBaseUrl = rtrim((string) config('app.url', ''), '/');
        if ($internalBaseUrl === '') {
            $this->error('APP_URL is required to build internal endpoint URLs.');

            return self::FAILURE;
        }
        $sharedKey = (string) config('lotto_auto_result.internal_result_sources.shared_key', '');
        $sharedHeader = (string) config('lotto_auto_result.internal_result_sources.header_name', 'X-Lotto-Internal-Key');

        $sourceIds = array_values(array_filter(array_map(
            static fn ($value): int => (int) $value,
            (array) $this->option('source-id')
        ), static fn (int $id): bool => $id > 0));

        $query = LottoResultSource::query()->orderBy('id');
        if ($sourceIds !== []) {
            $query->whereIn('id', $sourceIds);
        }

        $items = $query->get();
        $report = [
            'timestamp' => now()->toIso8601String(),
            'apply' => $apply,
            'internal_base_url' => $internalBaseUrl,
            'scanned_total' => (int) $items->count(),
            'migrated' => [],
            'skipped' => [],
        ];

        foreach ($items as $source) {
            $plan = $planner->plan((string) $source->endpoint_url, $internalBaseUrl);
            if ($plan === null) {
                $report['skipped'][] = [
                    'source_id' => (int) $source->id,
                    'endpoint_url' => (string) $source->endpoint_url,
                    'reason' => 'UNSUPPORTED_OR_ALREADY_INTERNAL',
                ];
                continue;
            }

            $nextQueryTemplate = is_array($source->request_query_template_json) ? $source->request_query_template_json : [];
            foreach (($plan['recommended_query_template'] ?? []) as $key => $value) {
                if (! array_key_exists($key, $nextQueryTemplate) || $nextQueryTemplate[$key] === '' || $nextQueryTemplate[$key] === null) {
                    $nextQueryTemplate[$key] = $value;
                }
            }

            $nextFetchConfig = is_array($source->fetch_config_json) ? $source->fetch_config_json : [];
            if ($nextFetchConfig !== []) {
                $nextFetchConfig['endpoint_url'] = (string) $plan['target_endpoint_url'];
                $requestNode = is_array($nextFetchConfig['request'] ?? null) ? $nextFetchConfig['request'] : [];
                $requestNode['url'] = (string) $plan['target_endpoint_url'];
                $requestQuery = is_array($requestNode['query'] ?? null) ? $requestNode['query'] : [];
                foreach (($plan['recommended_query_template'] ?? []) as $key => $value) {
                    if (! array_key_exists($key, $requestQuery) || $requestQuery[$key] === '' || $requestQuery[$key] === null) {
                        $requestQuery[$key] = $value;
                    }
                }
                $requestNode['query'] = $requestQuery;
                if ($sharedKey !== '') {
                    $requestHeaders = is_array($requestNode['headers'] ?? null) ? $requestNode['headers'] : [];
                    $requestHeaders[$sharedHeader] = $sharedKey;
                    $requestNode['headers'] = $requestHeaders;
                }
                $nextFetchConfig['request'] = $requestNode;
                $topLevelQuery = is_array($nextFetchConfig['query'] ?? null) ? $nextFetchConfig['query'] : [];
                foreach (($plan['recommended_query_template'] ?? []) as $key => $value) {
                    if (! array_key_exists($key, $topLevelQuery) || $topLevelQuery[$key] === '' || $topLevelQuery[$key] === null) {
                        $topLevelQuery[$key] = $value;
                    }
                }
                $nextFetchConfig['query'] = $topLevelQuery;
                if ($sharedKey !== '') {
                    $topLevelHeaders = is_array($nextFetchConfig['headers'] ?? null) ? $nextFetchConfig['headers'] : [];
                    $topLevelHeaders[$sharedHeader] = $sharedKey;
                    $nextFetchConfig['headers'] = $topLevelHeaders;
                }
            }

            $row = [
                'source_id' => (int) $source->id,
                'market_id' => (int) $source->market_id,
                'source_key' => (string) $plan['source_key'],
                'old_endpoint_url' => (string) $source->endpoint_url,
                'new_endpoint_url' => (string) $plan['target_endpoint_url'],
                'recommended_query_template' => $nextQueryTemplate,
            ];

            if ($apply) {
                $source->endpoint_url = (string) $plan['target_endpoint_url'];
                $source->request_query_template_json = $nextQueryTemplate;
                if ($nextFetchConfig !== []) {
                    $source->fetch_config_json = $nextFetchConfig;
                }
                $source->save();
                $row['applied'] = true;
            } else {
                $row['applied'] = false;
            }

            $report['migrated'][] = $row;
        }

        $this->line(sprintf(
            'Scanned: %d | Migratable: %d | Skipped: %d | Applied: %s',
            $report['scanned_total'],
            count($report['migrated']),
            count($report['skipped']),
            $apply ? 'yes' : 'no'
        ));

        $this->writeReport($report);
        $this->table(
            ['source_id', 'source_key', 'old_endpoint_url', 'new_endpoint_url', 'applied'],
            array_map(static fn (array $item): array => [
                $item['source_id'],
                $item['source_key'],
                $item['old_endpoint_url'],
                $item['new_endpoint_url'],
                $item['applied'] ? 'yes' : 'no',
            ], $report['migrated'])
        );

        return self::SUCCESS;
    }

    /**
     * @param array<string,mixed> $report
     */
    private function writeReport(array $report): void
    {
        $dir = storage_path('app/lotto/internal_result_migration');
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $timestamp = now()->format('Ymd_His');
        $path = $dir . '/migration_report_' . $timestamp . '.json';
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('Migration report written: ' . $path);
    }
}
