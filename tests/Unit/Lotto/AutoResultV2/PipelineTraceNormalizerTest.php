<?php

namespace Tests\Unit\Lotto\AutoResultV2;

use Gametech\Lotto\Services\AutoResultV2\Trace\PipelineTraceNormalizer;
use PHPUnit\Framework\TestCase;

class PipelineTraceNormalizerTest extends TestCase
{
    public function test_normalize_fills_required_keys_and_truncates_preview(): void
    {
        $normalized = PipelineTraceNormalizer::normalize([
            'run_id' => 'run_1',
            'pipeline_version' => 'V2_CUTOVER',
            'pipeline_stage' => 'FETCH',
            'fetch_strategy' => 'JSON_HTTP',
            'selection_stage' => 'POST_MAPPING',
            'status' => 'RUNNING',
            'response_preview' => str_repeat('x', 1500),
        ]);

        $this->assertArrayHasKey('final_decision', $normalized);
        $this->assertArrayHasKey('parsed_raw_fields', $normalized);
        $this->assertArrayHasKey('mapped_fields', $normalized);
        $this->assertArrayHasKey('error_code', $normalized);
        $this->assertLessThanOrEqual(1003, strlen((string) $normalized['response_preview']));
    }
}
