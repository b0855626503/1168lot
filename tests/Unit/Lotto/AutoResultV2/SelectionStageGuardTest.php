<?php

namespace Tests\Unit\Lotto\AutoResultV2;

use Gametech\Lotto\Services\AutoResultV2\Config\SourcePipelineConfigCompiler;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SelectionStageGuardTest extends TestCase
{
    public function test_pre_mapping_rejects_mapping_reference(): void
    {
        $compiler = new SourcePipelineConfigCompiler();

        $this->expectException(InvalidArgumentException::class);

        $compiler->compile([
            'pipeline_version' => 'V2_SHADOW',
            'fetch_strategy' => 'JSON_HTTP',
            'endpoint_url' => 'https://example.com',
            'http_method' => 'GET',
            'parser_config_json' => [
                'parser_type' => 'JSON_PATH',
                'fields' => [
                    'draw_date_raw' => '$.date',
                ],
            ],
            'mapping_config_json' => [
                'draw_date' => 'draw_date_raw',
            ],
            'selection_config_json' => [
                'selection_stage' => 'PRE_MAPPING',
                'references' => ['draw_date'],
            ],
        ]);
    }
}
