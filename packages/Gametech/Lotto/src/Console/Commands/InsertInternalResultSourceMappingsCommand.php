<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoResultSource;
use Illuminate\Console\Command;

class InsertInternalResultSourceMappingsCommand extends Command
{
    protected $signature = 'lotto:insert-internal-result-source-mappings
        {--apply : Persist new mapping rows}
        {--market-id=* : Limit to specific market IDs}
        {--market-code=* : Limit to specific market codes}
        {--priority=100 : Priority for newly inserted rows}
        {--activate-new : Set new rows to active}';

    protected $description = 'Insert-only canonical internal result source mappings without overwriting existing source rows';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $priority = max(1, (int) $this->option('priority'));
        $activateNew = (bool) $this->option('activate-new');

        $marketIds = array_values(array_filter(array_map(
            static fn ($value): int => (int) $value,
            (array) $this->option('market-id')
        ), static fn (int $id): bool => $id > 0));

        $marketCodes = array_values(array_filter(array_map(
            static fn ($value): string => strtolower(trim((string) $value)),
            (array) $this->option('market-code')
        ), static fn (string $code): bool => $code !== ''));

        $query = LotteryMarket::query()->orderBy('id');
        if ($marketIds !== []) {
            $query->whereIn('id', $marketIds);
        }
        if ($marketCodes !== []) {
            $query->whereIn('code', $marketCodes);
        }

        $markets = $query->get();
        $map = $this->marketCodeMap();
        $rows = [];
        $mapped = 0;
        $insertable = 0;
        $inserted = 0;
        $skippedUnmapped = 0;
        $skippedExisting = 0;

        foreach ($markets as $market) {
            $marketCode = strtolower((string) $market->code);
            $target = $map[$marketCode] ?? null;

            if ($target === null) {
                $skippedUnmapped++;
                $rows[] = [
                    'market_id' => (int) $market->id,
                    'market_code' => (string) $market->code,
                    'action' => 'skip(unmapped)',
                    'endpoint_url' => '-',
                ];
                continue;
            }

            $mapped++;
            $draft = $this->buildSourceDraft($market, $target, $priority, $activateNew);

            $exists = LottoResultSource::query()
                ->where('market_id', (int) $market->id)
                ->where('endpoint_url', (string) $draft['endpoint_url'])
                ->exists();

            if ($exists) {
                $skippedExisting++;
                $rows[] = [
                    'market_id' => (int) $market->id,
                    'market_code' => (string) $market->code,
                    'action' => 'skip(exists)',
                    'endpoint_url' => (string) $draft['endpoint_url'],
                ];
                continue;
            }

            $insertable++;
            $rows[] = [
                'market_id' => (int) $market->id,
                'market_code' => (string) $market->code,
                'action' => $apply ? 'inserted' : 'would-insert',
                'endpoint_url' => (string) $draft['endpoint_url'],
            ];

            if ($apply) {
                LottoResultSource::query()->create($draft);
                $inserted++;
            }
        }

        $this->line(sprintf(
            'Scanned: %d | Mapped: %d | Insertable: %d | Inserted: %d | Skipped(unmapped): %d | Skipped(exists): %d | Apply: %s',
            $markets->count(),
            $mapped,
            $insertable,
            $inserted,
            $skippedUnmapped,
            $skippedExisting,
            $apply ? 'yes' : 'no'
        ));

        if ($rows !== []) {
            $this->table(['market_id', 'market_code', 'action', 'endpoint_url'], $rows);
        }

        $this->info($apply
            ? 'Insert-only mapping sync completed.'
            : 'Dry-run completed: use --apply to insert new rows.');

        return self::SUCCESS;
    }

    /**
     * @return array<string,string>
     */
    private function marketCodeMap(): array
    {
        return [
            'gsb-lotto' => 'exphuay:gsb',
            'bacc-lotto' => 'exphuay:baac',
            'glo-lotto' => 'exphuay:goverment',
            'hanoi-special' => 'exphuay:xsthm',
            'hanoi-normal' => 'exphuay:minhngoc',
            'hanoi-vip' => 'exphuay:mlnhngo',
            'malasia-lotto' => 'exphuay:magnum4d',
            'laos-vip' => 'exphuay:laosvip',
            'nikkei-morning' => 'exphuay:nikkei-vip-morning',
            'hanoi-asean' => 'exphuay:hanoiasean',
            'chaina-morning' => 'exphuay:szse-vip-morning',
            'laos-tv' => 'exphuay:laotv',
            'hangseng-morning' => 'exphuay:hsi-vip-morning',
            'hanoi-hd' => 'exphuay:xosohd',
            'taiwan-vip' => 'exphuay:twse-vip',
            'hanoi-star' => 'exphuay:minhngocstar',
            'korea-vip' => 'exphuay:ktop30-vip',
            'nikkei-afternoon' => 'exphuay:nikkei-vip-afternoon',
            'laos-hd' => 'exphuay:laoshd',
            'hanoi-tv' => 'exphuay:minhngoctv',
            'chaina-afternoon' => 'exphuay:szse-vip-afternoon',
            'hangseng-afternoon' => 'exphuay:hsi-vip-afternoon',
            'laos-strar' => 'exphuay:laostars',
            'hanoi-redcross' => 'exphuay:xosoredcross',
            'singapore-vip' => 'exphuay:sgx-vip',
            'hanoi-harmonious' => 'exphuay:xosounion',
            'hanoi-develop' => 'exphuay:xosodevelop',
            'laos-harmonious' => 'exphuay:laounion',
            'laos-asean' => 'exphuay:laosasean',
            'laos-harmoniousvip' => 'exphuay:laounionvip',
            'laos-starvip' => 'exphuay:laostarsvip',
            'england-vip' => 'exphuay:england-vip',
            'hanoi-extra' => 'exphuay:xosoextra',
            'germany-vip' => 'exphuay:germany-vip',
            'laos-redcross' => 'exphuay:laoredcross',
            'russia-vip' => 'exphuay:russia-vip',
            'downjone-vip' => 'exphuay:mlnhngo',
            'downjone-star' => 'exphuay:dowjonestar',
            'downjone-midnight' => 'dowjones-midnight',
            'downjone-extra' => 'dowjones-extra',
            'laos-peace' => 'exphuay:laosantipap',
            'laos-doorwin' => 'exphuay:laopatuxay',
            'laos-population' => 'exphuay:laocitizen',
            'nikkei-stockm' => 'exphuay:nikkei-morning',
            'nikkei-stocka' => 'exphuay:nikkei-afternoon',
            'egypt-stock' => 'exphuay:egx30',
            'china-stockm' => 'exphuay:szse-morning',
            'hangseng-stockm' => 'exphuay:hsi-morning',
            'taiwan-stock' => 'exphuay:twse',
            'korea-stock' => 'exphuay:ktop30',
            'chaina-stocka' => 'exphuay:szse-afternoon',
            'hangseng-stocka' => 'exphuay:hsi-afternoon',
            'singapore-stock' => 'exphuay:sgx',
            'thai-stocke' => 'exphuay:set',
            'india-stock' => 'exphuay:bsesn',
            'england-stock' => 'exphuay:ftse100',
            'germany-stock' => 'exphuay:gdaxi',
            'russia-stock' => 'exphuay:moexbc',
            'downjone-stock' => 'exphuay:dji',
            'laos-phathana' => 'exphuay:laosdevelops',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function buildSourceDraft(LotteryMarket $market, string $target, int $priority, bool $activateNew): array
    {
        $baseUrl = $this->resolveInternalApiBaseUrl();
        $isExphuay = str_starts_with($target, 'exphuay:');
        $resolvedPriority = $isExphuay ? 1 : $priority;
        $resolvedActive = $isExphuay ? true : $activateNew;
        $queryTemplate = ['date' => '{{lookup_date}}'];
        $parserFields = [
            'draw_date_raw' => ['type' => 'JSON_PATH', 'path' => '$.draw_date'],
            'first_prize_raw_1' => ['type' => 'JSON_PATH', 'path' => '$.normalized_result.first_prize'],
            'last_2_raw_1' => ['type' => 'JSON_PATH', 'path' => '$.normalized_result.bottom_2'],
        ];

        if ($isExphuay) {
            $type = substr($target, strlen('exphuay:'));
            $endpointUrl = 'http://203.146.127.170/~anan/get_lottery.php';
            $queryTemplate = [
                'type' => $type,
                'date' => '{{lookup_date}}',
                'page' => 1,
            ];
            $parserFields = [
                'draw_date_raw' => ['type' => 'JSON_PATH', 'path' => '$.date'],
                'first_prize_raw_1' => ['type' => 'JSON_PATH', 'path' => '$.results[0].lottosNumber'],
                'last_2_raw_1' => ['type' => 'JSON_PATH', 'path' => '$.results[0].lottosUnder'],
            ];
        } elseif ($target === 'dowjones-midnight') {
            $endpointUrl = $baseUrl . '/internal/lottery/results/dowjones-midnight';
        } elseif ($target === 'dowjones-extra') {
            $endpointUrl = $baseUrl . '/internal/lottery/results/dowjones-extra';
        } else {
            $type = substr($target, strlen('exphuay:'));
            $endpointUrl = $baseUrl . '/internal/lottery/results/exphuay/' . $type;
            $queryTemplate['page'] = 1;
        }
        return [
            'market_id' => (int) $market->id,
            'is_active' => $resolvedActive,
            'priority' => $resolvedPriority,
            'source_type' => 'api',
            'endpoint_url' => $endpointUrl,
            'http_method' => 'GET',
            'request_headers_json' => [],
            'request_query_template_json' => $queryTemplate,
            'request_body_template_json' => [],
            'lookup_date_mode' => 'ROUND_DATE',
            'lookup_date_offset_days' => 0,
            'parser_type' => 'JSON_PATH',
            'parser_config_json' => [
                'version' => 2,
                'mode' => 'single_payload',
                'parser_type' => 'JSON_PATH',
                'fields' => $parserFields,
            ],
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
                'max_attempts' => 3,
                'backoff_seconds' => [10, 30, 60],
            ],
            'timeout_seconds' => 10,
            'effective_from' => null,
            'effective_to' => null,
            'fetch_config_json' => [
                'fetch_strategy' => 'JSON_HTTP',
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
                    'insert_only_mapping' => true,
                    'target_family' => $isExphuay ? 'external-get-lottery' : 'dowjones',
                ],
            ],
            'selection_config_json' => [
                'selection_stage' => 'PRE_MAPPING',
                'strategy' => 'strict_single_match',
                'date_field' => 'draw_date_raw',
                'required_fields' => [],
                'meta' => [
                    'insert_only_mapping' => true,
                ],
            ],
            'readiness_config_json' => [
                'enabled' => true,
                'minimum_required_keys' => ['draw_date', 'first_prize', 'last_2_digits'],
            ],
            'pipeline_version' => 'V2_CUTOVER',
            'fetch_strategy' => 'JSON_HTTP',
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
