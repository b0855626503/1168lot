<?php

namespace Gametech\Lotto\Services\AutoResultV2;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoResultSource;
use Gametech\Lotto\Services\AutoResultV2\Config\SourcePipelineConfigCompiler;
use Gametech\Lotto\Services\AutoResultV2\ConfigData\CompiledSourcePipelineData;

class LottoResultPipelineRunner
{
    public function __construct(
        private ?SourcePipelineConfigCompiler $compiler = null,
        private ?V2ResultPipelineRunner $v2Runner = null,
        private ?LegacyResultPipelineRunner $legacyRunner = null,
        private ?ShadowCompareService $shadowCompareService = null
    ) {
        $this->compiler = $this->compiler ?: new SourcePipelineConfigCompiler();
        $this->v2Runner = $this->v2Runner ?: new V2ResultPipelineRunner();
        $this->legacyRunner = $this->legacyRunner ?: new LegacyResultPipelineRunner();
        $this->shadowCompareService = $this->shadowCompareService ?: new ShadowCompareService();
    }

    /**
     * @param array<string,mixed> $options
     * @param callable|null $legacyCallback
     * @return array<string,mixed>
     */
    public function run(LottoDraw $draw, LottoResultSource $source, array $options = [], ?callable $legacyCallback = null): array
    {
        $compiled = $this->compiler->compile($this->buildSourceConfig($source));

        if ((bool) $source->cutover_enabled || $compiled->pipelineVersion() === CompiledSourcePipelineData::VERSION_V2_CUTOVER) {
            return $this->v2Runner->run($draw, $source, $compiled, $options);
        }

        if ((bool) $source->shadow_enabled || $compiled->pipelineVersion() === CompiledSourcePipelineData::VERSION_V2_SHADOW) {
            $legacy = $this->legacyRunner->run($draw, $source, $options, $legacyCallback);
            $v2 = $this->v2Runner->run($draw, $source, $compiled, $options);

            $shadow = $this->shadowCompareService->compare($legacy, $v2, [
                'draw_id' => (int) $draw->id,
                'source_id' => (int) $source->id,
            ]);

            $legacy['shadow_compare'] = $shadow;
            $legacy['v2_shadow_result'] = $v2;

            return $legacy;
        }

        return $this->legacyRunner->run($draw, $source, $options, $legacyCallback);
    }

    /**
     * @return array<string,mixed>
     */
    private function buildSourceConfig(LottoResultSource $source): array
    {
        return [
            'pipeline_version' => (string) ($source->pipeline_version ?: CompiledSourcePipelineData::VERSION_LEGACY),
            'fetch_strategy' => (string) ($source->fetch_strategy ?: 'JSON_HTTP'),
            'fetch_config_json' => (array) ($source->fetch_config_json ?? []),
            'endpoint_url' => (string) $source->endpoint_url,
            'http_method' => (string) $source->http_method,
            'request_headers_json' => (array) ($source->request_headers_json ?? []),
            'request_query_template_json' => (array) ($source->request_query_template_json ?? []),
            'request_body_template_json' => (array) ($source->request_body_template_json ?? []),
            'timeout_seconds' => (int) $source->timeout_seconds,
            'parser_type' => (string) ($source->parser_type ?: 'JSON_PATH'),
            'parser_config_json' => (array) ($source->parser_config_json ?? []),
            'mapping_config_json' => (array) ($source->mapping_config_json ?? []),
            'selection_config_json' => (array) ($source->selection_config_json ?? []),
            'validation_config_json' => (array) ($source->validation_config_json ?? []),
            'readiness_config_json' => (array) ($source->readiness_config_json ?? []),
            'selection_stage' => (string) ($source->selection_stage ?: 'POST_MAPPING'),
            'supports_partial' => (bool) $source->supports_partial,
            'shadow_enabled' => (bool) $source->shadow_enabled,
            'cutover_enabled' => (bool) $source->cutover_enabled,
        ];
    }
}
