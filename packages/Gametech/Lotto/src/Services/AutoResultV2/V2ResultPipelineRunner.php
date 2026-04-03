<?php

namespace Gametech\Lotto\Services\AutoResultV2;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoResultSource;
use Gametech\Lotto\Services\AutoResultV2\ConfigData\CompiledSourcePipelineData;
use Gametech\Lotto\Services\AutoResultV2\Executors\ExtractExecutor;
use Gametech\Lotto\Services\AutoResultV2\Executors\FetchExecutor;
use Gametech\Lotto\Services\AutoResultV2\Executors\NormalizeComposeExecutor;
use Gametech\Lotto\Services\AutoResultV2\Executors\ReadinessExecutor;
use Gametech\Lotto\Services\AutoResultV2\Executors\SelectionExecutor;
use Gametech\Lotto\Services\AutoResultV2\Executors\ValidationExecutor;
use Gametech\Lotto\Services\AutoResultV2\Trace\PipelineTraceNormalizer;

class V2ResultPipelineRunner
{
    public function __construct(
        private ?FetchExecutor $fetchExecutor = null,
        private ?ExtractExecutor $extractExecutor = null,
        private ?SelectionExecutor $selectionExecutor = null,
        private ?NormalizeComposeExecutor $normalizeComposeExecutor = null,
        private ?ValidationExecutor $validationExecutor = null,
        private ?ReadinessExecutor $readinessExecutor = null
    ) {
        $this->fetchExecutor = $this->fetchExecutor ?: new FetchExecutor();
        $this->extractExecutor = $this->extractExecutor ?: new ExtractExecutor();
        $this->selectionExecutor = $this->selectionExecutor ?: new SelectionExecutor();
        $this->normalizeComposeExecutor = $this->normalizeComposeExecutor ?: new NormalizeComposeExecutor();
        $this->validationExecutor = $this->validationExecutor ?: new ValidationExecutor();
        $this->readinessExecutor = $this->readinessExecutor ?: new ReadinessExecutor();
    }

    /**
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function run(LottoDraw $draw, LottoResultSource $source, CompiledSourcePipelineData $compiled, array $options = []): array
    {
        $expectedDrawDate = $options['expected_draw_date'] ?? optional($draw->draw_date)->format('Y-m-d');
        $selectionMeta = $compiled->selection()->meta();
        $ignoreExpectedDrawDate = (bool) ($selectionMeta['ignore_expected_draw_date'] ?? false);
        $runId = $this->stringValue($options['run_id'] ?? ('v2_' . $draw->id . '_' . now()->format('YmdHisv')));

        $trace = [
            'run_id' => $runId,
            'draw_id' => (int) $draw->id,
            'source_id' => (int) $source->id,
            'pipeline_version' => $compiled->pipelineVersion(),
            'pipeline_stage' => PipelineStage::FETCH,
            'selection_stage' => $compiled->selection()->selectionStage(),
            'fetch_strategy' => $compiled->fetch()->strategy(),
            'status' => 'RUNNING',
            'fetched_url' => $compiled->fetch()->endpointUrl(),
            'response_content_type' => null,
            'response_preview' => null,
            'record_selector_match_count' => 0,
            'first_matched_block_preview' => null,
            'parsed_raw_fields' => [],
            'mapped_fields' => [],
            'selection_result' => [],
            'validation_result' => [],
            'readiness_result' => [],
            'final_decision' => 'PENDING',
            'error_code' => null,
            'error_stage' => null,
            'selected_driver' => null,
            'payload_origin' => null,
            'phase_timing' => [],
            'selected_capture' => null,
            'artifact_refs' => null,
        ];

        $fetch = $this->fetchExecutor->execute($compiled->fetch(), [
            'run_id' => $runId,
            'draw_id' => (int) $draw->id,
            'source_id' => (int) $source->id,
            'strategy' => $compiled->fetch()->strategy(),
            'endpoint_url' => $compiled->fetch()->endpointUrl(),
            'parser_type' => $compiled->parser()->type(),
            'expected_draw_date' => is_string($expectedDrawDate) ? $expectedDrawDate : null,
            'lookup_date' => is_string($options['lookup_date'] ?? null) ? (string) $options['lookup_date'] : null,
            'lookup_date_compact' => is_string($options['lookup_date_compact'] ?? null) ? (string) $options['lookup_date_compact'] : null,
        ]);
        $trace['response_content_type'] = $this->stringValue($fetch['response_content_type'] ?? '');
        $trace['response_preview'] = mb_substr($this->stringValue($fetch['response_body'] ?? ''), 0, 1000);
        $trace['selected_driver'] = $this->stringValue($fetch['selected_driver'] ?? '');
        $trace['payload_origin'] = $this->stringValue($fetch['meta']['payload_origin'] ?? '');
        $trace['phase_timing'] = is_array($fetch['meta']['phase_timing'] ?? null) ? $fetch['meta']['phase_timing'] : [];
        $trace['selected_capture'] = is_array($fetch['meta']['selected_capture'] ?? null) ? $fetch['meta']['selected_capture'] : [];
        $trace['artifact_refs'] = is_array($fetch['meta']['artifact_refs'] ?? null) ? $fetch['meta']['artifact_refs'] : [];

        if (! (bool) ($fetch['ok'] ?? false)) {
            $status = $this->stringValue($fetch['status'] ?? 'FETCH_FAILED');
            $trace['pipeline_stage'] = PipelineStage::FETCH;
            $trace['status'] = $status;
            $trace['final_decision'] = 'REJECTED';
            $fetchErrorCode = $this->stringValue($fetch['error_code'] ?? '');
            $trace['error_code'] = $fetchErrorCode !== ''
                ? $fetchErrorCode
                : (in_array($status, ['APP_SHELL_ONLY', 'FETCH_DEFERRED'], true) ? $status : 'FETCH_FAILED');
            $trace['error_stage'] = PipelineStage::FETCH;

            return [
                'status' => $status,
                'error_code' => $this->stringValue($trace['error_code']),
                'error_stage' => $this->stringValue($trace['error_stage']),
                'error_message' => $this->stringValue($fetch['error_message'] ?? ''),
                'trace_json' => PipelineTraceNormalizer::normalize($trace),
                'fetch' => $fetch,
            ];
        }

        $extract = $this->extractExecutor->execute($this->stringValue($fetch['response_body'] ?? ''), $compiled->parser());
        $trace['pipeline_stage'] = PipelineStage::PARSE;
        $trace['record_selector_match_count'] = (int) ($extract['record_selector_match_count'] ?? 0);
        $trace['first_matched_block_preview'] = mb_substr($this->stringValue($extract['first_matched_block_preview'] ?? ''), 0, 1000);
        $trace['parsed_raw_fields'] = (array) (($extract['candidates'][0]['fields'] ?? []));

        $selection = $this->selectionExecutor->execute($extract, $compiled->selection(), [
            'run_id' => $runId,
            'draw_id' => (int) $draw->id,
            'source_id' => (int) $source->id,
            'candidate_draw_date_offset_days' => (int) ($selectionMeta['candidate_draw_date_offset_days'] ?? 0),
            'expected_draw_date_offset_days' => (int) ($selectionMeta['expected_draw_date_offset_days'] ?? 0),
            'expected_draw_date' => $ignoreExpectedDrawDate
                ? null
                : (is_string($expectedDrawDate) ? $expectedDrawDate : null),
        ]);
        $trace['selection_result'] = $selection;
        if ($this->stringValue($selection['decision'] ?? '') !== 'selected') {
            $trace['pipeline_stage'] = PipelineStage::SELECT;
            $trace['status'] = 'VALIDATION_ERROR';
            $trace['final_decision'] = 'REJECTED';
            $trace['error_code'] = 'NO_CANDIDATE_MATCHES_EXPECTED_DRAW_DATE';
            $trace['error_stage'] = PipelineStage::SELECT;

            return [
                'status' => 'VALIDATION_ERROR',
                'error_code' => 'NO_CANDIDATE_MATCHES_EXPECTED_DRAW_DATE',
                'error_stage' => PipelineStage::SELECT,
                'trace_json' => PipelineTraceNormalizer::normalize($trace),
                'selection' => $selection,
                'extract' => $extract,
            ];
        }

        $compose = $this->normalizeComposeExecutor->execute((array) ($selection['selected_candidate'] ?? []), $compiled->mapping(), [
            'expected_draw_date_field' => $this->stringValue($compiled->validation()->expectedDrawDate()['field'] ?? 'draw_date'),
        ]);
        $mapped = (array) ($compose['canonical_outcome'] ?? []);
        $trace['pipeline_stage'] = PipelineStage::MAP;
        $trace['mapped_fields'] = $mapped;

        $validation = $this->validationExecutor->execute($mapped, $compiled->validation());
        $trace['validation_result'] = $validation;
        if (! (bool) ($validation['valid'] ?? false)) {
            $trace['pipeline_stage'] = PipelineStage::VALIDATE;
            $trace['status'] = 'VALIDATION_ERROR';
            $trace['final_decision'] = 'REJECTED';
            $trace['error_code'] = $this->stringValue($validation['error_code'] ?? 'REQUIRED_FIELD_MISSING');
            $trace['error_stage'] = PipelineStage::VALIDATE;

            return [
                'status' => 'VALIDATION_ERROR',
                'error_code' => $this->stringValue($trace['error_code']),
                'error_stage' => PipelineStage::VALIDATE,
                'trace_json' => PipelineTraceNormalizer::normalize($trace),
                'validation' => $validation,
                'mapped' => $mapped,
            ];
        }

        $readiness = $this->readinessExecutor->execute((array) ($validation['normalized'] ?? []), $compiled->readiness(), (bool) ($source->supports_partial ?? false));
        $trace['pipeline_stage'] = PipelineStage::READINESS;
        $trace['readiness_result'] = $readiness;

        if (! (bool) ($readiness['ready'] ?? false)) {
            $trace['status'] = 'NOT_READY';
            $trace['final_decision'] = 'REJECTED';
            $trace['error_code'] = $this->stringValue($readiness['error_code'] ?? 'NOT_READY_PARTIAL_RESULT');
            $trace['error_stage'] = PipelineStage::READINESS;

            return [
                'status' => 'NOT_READY',
                'error_code' => $this->stringValue($trace['error_code']),
                'error_stage' => PipelineStage::READINESS,
                'trace_json' => PipelineTraceNormalizer::normalize($trace),
                'readiness' => $readiness,
                'validated' => $validation['normalized'] ?? [],
            ];
        }

        $trace['status'] = 'VALID';
        $trace['final_decision'] = 'ACCEPTED';
        $trace['error_code'] = null;
        $trace['error_stage'] = null;

        return [
            'status' => 'VALID',
            'error_code' => null,
            'error_stage' => null,
            'trace_json' => PipelineTraceNormalizer::normalize($trace),
            'validated' => (array) ($validation['normalized'] ?? []),
            'mapped' => $mapped,
            'selection' => $selection,
            'extract' => $extract,
            'fetch' => $fetch,
            'compose' => $compose,
            'readiness' => $readiness,
        ];
    }

    private function stringValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '' : $encoded;
    }
}
