<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoResultSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BootstrapMissingResultSourcesCommand extends Command
{
    protected $signature = 'lotto:bootstrap-missing-result-sources
        {--apply : Persist missing sources}
        {--market-id=* : Limit to specific market IDs}';

    protected $description = 'Create safe placeholder lotto_result_sources for markets that still have no source row';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $marketIds = array_values(array_filter(array_map(
            static fn ($value): int => (int) $value,
            (array) $this->option('market-id')
        ), static fn (int $id): bool => $id > 0));

        $query = LotteryMarket::query()
            ->withCount('resultSources')
            ->orderBy('id');

        if ($marketIds !== []) {
            $query->whereIn('id', $marketIds);
        }

        $markets = $query->get();
        $missing = $markets->filter(static fn (LotteryMarket $market): bool => (int) ($market->result_sources_count ?? 0) === 0)->values();

        $this->line(sprintf(
            'Scanned markets: %d | Missing sources: %d | Apply: %s',
            $markets->count(),
            $missing->count(),
            $apply ? 'yes' : 'no'
        ));

        $rows = [];
        foreach ($missing as $market) {
            $draft = $this->buildSourceDraft($market);
            $rows[] = [
                'market_id' => (int) $market->id,
                'market_code' => (string) $market->code,
                'market_name' => (string) $market->name,
                'source_type' => $draft['source_type'],
                'endpoint_url' => $draft['endpoint_url'],
                'active' => $draft['is_active'] ? 'yes' : 'no',
            ];

            if ($apply) {
                DB::transaction(function () use ($draft): void {
                    LottoResultSource::query()->create($draft);
                });
            }
        }

        if ($rows !== []) {
            $this->table(['market_id', 'market_code', 'market_name', 'source_type', 'endpoint_url', 'active'], $rows);
        }

        $this->info($apply
            ? 'Bootstrap completed: missing sources were inserted as safe placeholders.'
            : 'Dry-run completed: use --apply to insert missing sources.');

        return self::SUCCESS;
    }

    /**
     * @return array<string,mixed>
     */
    private function buildSourceDraft(LotteryMarket $market): array
    {
        $marketCode = strtolower((string) $market->code);
        $baseUrl = $this->resolveInternalApiBaseUrl();
        $sourceType = 'api';
        $endpointUrl = $baseUrl . '/internal/lottery/results/exphuay/list';
        $fetchStrategy = 'JSON_HTTP';
        $parserType = 'JSON_PATH';
        $parserConfig = [
            'version' => 2,
            'mode' => 'single_payload',
            'parser_type' => 'JSON_PATH',
            'fields' => [
                'draw_date_raw' => ['type' => 'JSON_PATH', 'path' => '$.draw_date'],
                'first_prize_raw_1' => ['type' => 'JSON_PATH', 'path' => '$.normalized_result.first_prize'],
                'last_2_raw_1' => ['type' => 'JSON_PATH', 'path' => '$.normalized_result.bottom_2'],
            ],
        ];

        if ($marketCode === 'downjone-midnight') {
            $endpointUrl = $baseUrl . '/internal/lottery/results/dowjones-midnight';
        } elseif ($marketCode === 'downjone-extra') {
            $endpointUrl = $baseUrl . '/internal/lottery/results/dowjones-extra';
        }

        $queryTemplate = ['date' => '{{lookup_date}}'];
        if ($marketCode !== 'downjone-midnight' && $marketCode !== 'downjone-extra') {
            $queryTemplate['page'] = 1;
        }

        return [
            'market_id' => (int) $market->id,
            'is_active' => false,
            'priority' => 100,
            'source_type' => $sourceType,
            'endpoint_url' => $endpointUrl,
            'http_method' => 'GET',
            'request_headers_json' => [],
            'request_query_template_json' => $queryTemplate,
            'request_body_template_json' => [],
            'lookup_date_mode' => 'ROUND_DATE',
            'lookup_date_offset_days' => 0,
            'parser_type' => $parserType,
            'parser_config_json' => $parserConfig,
            'mapping_config_json' => [
                'fields' => [
                    'draw_date' => ['from' => 'draw_date_raw', 'transforms' => [['op' => 'trim']]],
                    'first_prize' => ['from' => 'first_prize_raw_1', 'transforms' => [['op' => 'digits_only']]],
                    'last_2_digits' => ['from' => 'last_2_raw_1', 'transforms' => [['op' => 'digits_only'], ['op' => 'right', 'length' => 2]]],
                ],
            ],
            'validation_config_json' => [
                'required_fields' => ['draw_date', 'first_prize', 'last_2_digits'],
            ],
            'retry_policy_json' => [
                'max_attempts' => 5,
                'backoff_seconds' => [300, 300, 300, 300, 300],
            ],
            'timeout_seconds' => 10,
            'effective_from' => null,
            'effective_to' => null,
            'fetch_config_json' => [
                'fetch_strategy' => $fetchStrategy,
                'endpoint_url' => $endpointUrl,
                'http_method' => 'GET',
                'headers' => [],
                'query' => $queryTemplate,
                'timeout_seconds' => 10,
                'meta' => [
                    'runtime' => [
                        'fetch_capability' => 'http_only',
                        'allow_dom_fallback' => false,
                    ],
                    'bootstrap_placeholder' => true,
                ],
            ],
            'selection_config_json' => [
                'selection_stage' => 'PRE_MAPPING',
                'strategy' => 'strict_single_match',
                'date_field' => 'draw_date_raw',
                'required_fields' => [],
                'meta' => [
                    'bootstrap_placeholder' => true,
                ],
            ],
            'readiness_config_json' => [
                'enabled' => true,
                'minimum_required_keys' => ['draw_date', 'first_prize', 'last_2_digits'],
            ],
            'pipeline_version' => 'V2_CUTOVER',
            'fetch_strategy' => $fetchStrategy,
            'selection_stage' => 'PRE_MAPPING',
            'supports_partial' => false,
            'requires_browser' => false,
            'shadow_enabled' => false,
            'cutover_enabled' => true,
        ];
    }

    private function resolveInternalApiBaseUrl(): string
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $scheme = (string) parse_url($appUrl, PHP_URL_SCHEME);
        if ($scheme === '') {
            $scheme = 'http';
        }

        $apiDomain = trim((string) env('APP_API_DOMAIN_URL', ''));
        $adminDomain = trim((string) config('app.admin_domain_url', ''));
        $domain = $apiDomain !== '' ? $apiDomain : $adminDomain;
        $apiSubdomain = trim((string) config('gametech.api_url', 'api'), '.');

        if ($domain !== '') {
            $host = $apiSubdomain !== '' ? ($apiSubdomain . '.' . ltrim($domain, '.')) : ltrim($domain, '.');

            return sprintf('%s://%s', $scheme, $host);
        }

        return $appUrl;
    }
}
