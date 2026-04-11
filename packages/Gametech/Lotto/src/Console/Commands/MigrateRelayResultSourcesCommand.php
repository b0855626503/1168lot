<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoResultSource;
use Gametech\Lotto\Services\Relay\LotteryRelayRuntime;
use Gametech\Lotto\Services\Relay\LotteryRelayTypeRegistry;
use Illuminate\Console\Command;

class MigrateRelayResultSourcesCommand extends Command
{
    protected $signature = 'lotto:migrate-relay-result-sources
        {--apply : Persist migrated relay endpoint configuration}
        {--market-id=* : Limit to specific market IDs}
        {--market-code=* : Limit to specific market codes}
        {--activate : Activate migrated rows after update}';

    protected $description = 'Switch lotto_result_sources to the public relay contract at /api/v1/get_lottery';

    public function handle(LotteryRelayRuntime $runtime, LotteryRelayTypeRegistry $typeRegistry): int
    {
        $apply = (bool) $this->option('apply');
        $activate = (bool) $this->option('activate');
        $baseUrl = $runtime->apiBaseUrl();

        if ($baseUrl === '') {
            $this->error('LOTTERY_RESULT_RELAY_API_BASE_URL (or config lottery_result_relay.api_base_url) is required.');

            return self::FAILURE;
        }

        $marketIds = array_values(array_filter(array_map(
            static fn ($value): int => (int) $value,
            (array) $this->option('market-id')
        ), static fn (int $id): bool => $id > 0));

        $marketCodes = array_values(array_filter(array_map(
            static fn ($value): string => strtolower(trim((string) $value)),
            (array) $this->option('market-code')
        ), static fn (string $code): bool => $code !== ''));

        $query = LottoResultSource::query()->with('market')->orderBy('id');
        if ($marketIds !== []) {
            $query->whereIn('market_id', $marketIds);
        }
        if ($marketCodes !== []) {
            $query->whereHas('market', static function ($marketQuery) use ($marketCodes): void {
                $marketQuery->whereIn('code', $marketCodes);
            });
        }

        $rows = [];
        foreach ($query->get() as $source) {
            $market = $source->market;
            if (! $market instanceof LotteryMarket) {
                continue;
            }

            $canonicalType = $typeRegistry->canonicalTypeForMarketCode((string) $market->code);
            if ($canonicalType === null) {
                $rows[] = [
                    'source_id' => (int) $source->id,
                    'market_code' => (string) $market->code,
                    'type' => '-',
                    'action' => 'skip(unmapped)',
                ];

                continue;
            }

            $endpointUrl = $baseUrl.'/api/v1/get_lottery';
            $draft = [
                'endpoint_url' => $endpointUrl,
                'http_method' => 'GET',
                'request_headers_json' => [],
                'request_query_template_json' => [
                    'type' => $canonicalType,
                    'date' => '{{lookup_date}}',
                ],
                'parser_type' => 'JSON_PATH',
                'parser_config_json' => [
                    'version' => 2,
                    'mode' => 'single_payload',
                    'parser_type' => 'JSON_PATH',
                    'fields' => [
                        'draw_date_raw' => ['type' => 'JSON_PATH', 'path' => '$.date'],
                        'first_prize_raw_1' => ['type' => 'JSON_PATH', 'path' => '$.results[0].lottosNumber'],
                        'last_2_raw_1' => ['type' => 'JSON_PATH', 'path' => '$.results[0].lottosUnder'],
                    ],
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
                'selection_config_json' => [
                    'selection_stage' => 'PRE_MAPPING',
                    'strategy' => 'strict_single_match',
                    'date_field' => 'draw_date_raw',
                    'required_fields' => [],
                    'meta' => [
                        'relay_public_contract' => true,
                    ],
                ],
                'readiness_config_json' => [
                    'enabled' => true,
                    'minimum_required_keys' => ['draw_date', 'first_prize', 'last_2_digits'],
                ],
                'fetch_config_json' => [
                    'fetch_strategy' => 'JSON_HTTP',
                    'endpoint_url' => $endpointUrl,
                    'http_method' => 'GET',
                    'headers' => [],
                    'query' => [
                        'type' => $canonicalType,
                        'date' => '{{lookup_date}}',
                    ],
                    'timeout_seconds' => 10,
                    'meta' => [
                        'runtime' => [
                            'fetch_capability' => 'http_only',
                            'allow_dom_fallback' => false,
                        ],
                        'relay_public_contract' => true,
                    ],
                ],
                'pipeline_version' => 'V2_CUTOVER',
                'fetch_strategy' => 'JSON_HTTP',
                'selection_stage' => 'PRE_MAPPING',
                'supports_partial' => false,
                'requires_browser' => false,
                'shadow_enabled' => false,
                'cutover_enabled' => true,
            ];

            if ($apply) {
                $source->fill($draft);
                if ($activate) {
                    $source->is_active = true;
                }
                $source->save();
            }

            $rows[] = [
                'source_id' => (int) $source->id,
                'market_code' => (string) $market->code,
                'type' => $canonicalType,
                'action' => $apply ? 'updated' : 'would-update',
            ];
        }

        if ($rows !== []) {
            $this->table(['source_id', 'market_code', 'type', 'action'], $rows);
        }

        return self::SUCCESS;
    }
}
