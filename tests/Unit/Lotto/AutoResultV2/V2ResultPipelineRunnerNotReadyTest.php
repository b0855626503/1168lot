<?php

namespace Tests\Unit\Lotto\AutoResultV2;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoResultSource;
use Gametech\Lotto\Services\AutoResultV2\Config\SourcePipelineConfigCompiler;
use Gametech\Lotto\Services\AutoResultV2\ConfigData\FetchConfigData;
use Gametech\Lotto\Services\AutoResultV2\ConfigData\ParserConfigData;
use Gametech\Lotto\Services\AutoResultV2\Executors\ExtractExecutor;
use Gametech\Lotto\Services\AutoResultV2\Executors\FetchExecutor;
use Gametech\Lotto\Services\AutoResultV2\V2ResultPipelineRunner;
use Tests\TestCase;

class V2ResultPipelineRunnerNotReadyTest extends TestCase
{
    public function test_empty_payload_is_classified_as_not_ready_before_validation_error(): void
    {
        $runner = new V2ResultPipelineRunner(
            new class extends FetchExecutor
            {
                public function execute(FetchConfigData $config, array $runtimeContext = []): array
                {
                    return [
                        'ok' => true,
                        'status' => 'FETCHED',
                        'response_body' => '{"date":"2026-04-04","results":[]}',
                        'response_content_type' => 'application/json',
                        'selected_driver' => 'JSON_HTTP',
                        'meta' => [],
                    ];
                }
            },
            new class extends ExtractExecutor
            {
                public function execute(string $body, ParserConfigData $config): array
                {
                    return [
                        'candidates' => [[
                            'index' => 0,
                            'raw_record' => '{"date":"2026-04-04","results":[]}',
                            'fields' => [
                                'draw_date_raw' => '2026-04-04',
                                'first_prize_raw_1' => null,
                                'last_2_raw_1' => null,
                            ],
                        ]],
                        'record_selector_match_count' => 1,
                        'first_matched_block_preview' => '{"date":"2026-04-04","results":[]}',
                    ];
                }
            }
        );

        $compiled = (new SourcePipelineConfigCompiler())->compile([
            'pipeline_version' => 'V2_CUTOVER',
            'fetch_strategy' => 'JSON_HTTP',
            'endpoint_url' => 'http://example.test/lotto',
            'http_method' => 'GET',
            'timeout_seconds' => 10,
            'parser_type' => 'JSON_PATH',
            'parser_config_json' => [
                'version' => 2,
                'mode' => 'single_payload',
                'parser_type' => 'JSON_PATH',
                'fields' => [
                    'draw_date_raw' => ['type' => 'JSON_PATH', 'path' => '$.date'],
                    'first_prize_raw_1' => ['type' => 'JSON_PATH', 'path' => '$.results[0].first'],
                    'last_2_raw_1' => ['type' => 'JSON_PATH', 'path' => '$.results[0].last2'],
                ],
            ],
            'mapping_config_json' => [
                'fields' => [
                    'draw_date' => ['from' => 'draw_date_raw', 'transforms' => [['op' => 'trim']]],
                    'first_prize' => ['from' => 'first_prize_raw_1', 'transforms' => [['op' => 'digits_only']]],
                    'last_2_digits' => ['from' => 'last_2_raw_1', 'transforms' => [['op' => 'digits_only'], ['op' => 'right', 'length' => 2]]],
                ],
            ],
            'selection_config_json' => [
                'selection_stage' => 'PRE_MAPPING',
                'strategy' => 'strict_single_match',
                'date_field' => 'draw_date_raw',
                'required_fields' => [],
            ],
            'validation_config_json' => [
                'required_fields' => ['draw_date', 'first_prize', 'last_2_digits'],
                'expected_draw_date' => ['field' => 'draw_date'],
            ],
            'readiness_config_json' => [
                'enabled' => true,
                'minimum_required_keys' => ['draw_date', 'first_prize', 'last_2_digits'],
            ],
        ]);

        $draw = new LottoDraw([
            'id' => 214,
            'market_id' => 1,
            'draw_date' => '2026-04-04',
        ]);
        $source = new LottoResultSource([
            'id' => 1,
            'market_id' => 1,
            'supports_partial' => false,
        ]);

        $result = $runner->run($draw, $source, $compiled, [
            'run_id' => 'test_run',
            'expected_draw_date' => '2026-04-04',
        ]);

        $this->assertSame('NOT_READY', $result['status'] ?? null);
        $this->assertSame('NOT_READY_BUSINESS_RULE', $result['error_code'] ?? null);
        $this->assertSame('READINESS', $result['error_stage'] ?? null);
        $this->assertSame(['first_prize', 'last_2_digits'], $result['readiness']['missing_fields'] ?? null);
    }
}
