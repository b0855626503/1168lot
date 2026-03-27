<?php

namespace Tests\Unit\Lotto\AutoResultV2;

use Gametech\Lotto\Services\AutoResultV2\ConfigData\CompiledSourcePipelineData;
use Gametech\Lotto\Services\AutoResultV2\ConfigData\FetchConfigData;
use Gametech\Lotto\Services\AutoResultV2\ConfigData\SelectionConfigData;
use Gametech\Lotto\Services\AutoResultV2\PipelineRunTrace;
use PHPUnit\Framework\TestCase;

class EnumContractsTest extends TestCase
{
    public function test_fixed_pipeline_version_values(): void
    {
        $this->assertSame([
            'LEGACY',
            'V2_SHADOW',
            'V2_CUTOVER',
        ], CompiledSourcePipelineData::allowedPipelineVersions());
    }

    public function test_fixed_fetch_strategy_values(): void
    {
        $this->assertSame([
            'JSON_HTTP',
            'HTML_HTTP',
            'RENDERED_BROWSER',
            'EMBEDDED_JSON',
            'MANUAL_INPUT',
        ], FetchConfigData::allowedStrategies());
    }

    public function test_fixed_selection_stage_values(): void
    {
        $this->assertSame([
            'PRE_MAPPING',
            'POST_MAPPING',
        ], SelectionConfigData::allowedStages());
    }

    public function test_fixed_shadow_compare_status_values(): void
    {
        $this->assertSame([
            'MATCH',
            'MISMATCH',
            'ERROR',
            'SKIPPED',
        ], PipelineRunTrace::allowedShadowCompareStatuses());
    }
}
