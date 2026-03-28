<?php

namespace Gametech\Lotto\Services\AutoResultV2\Trace;

use Gametech\Lotto\Services\AutoResultV2\ConfigData\CompiledSourcePipelineData;
use Gametech\Lotto\Services\AutoResultV2\ConfigData\SelectionConfigData;
use Gametech\Lotto\Services\AutoResultV2\PipelineErrorCode;
use Gametech\Lotto\Services\AutoResultV2\PipelineRunTrace;
use Gametech\Lotto\Services\AutoResultV2\PipelineStage;
use InvalidArgumentException;

final class PipelineTraceNormalizer
{
    private const PREVIEW_LIMIT = 1000;

    /**
     * @var array<int,string>
     */
    private const REQUIRED_KEYS = [
        'run_id',
        'draw_id',
        'source_id',
        'pipeline_version',
        'pipeline_stage',
        'fetch_strategy',
        'selection_stage',
        'fetched_url',
        'response_content_type',
        'response_preview',
        'record_selector_match_count',
        'first_matched_block_preview',
        'parsed_raw_fields',
        'mapped_fields',
        'selection_result',
        'validation_result',
        'readiness_result',
        'final_decision',
        'error_code',
        'error_stage',
        'status',
        'selected_driver',
        'payload_origin',
        'phase_timing',
        'selected_capture',
        'artifact_refs',
    ];

    /**
     * @return array<string,mixed>
     */
    public static function normalize(array $trace): array
    {
        $normalized = [];
        foreach (self::REQUIRED_KEYS as $key) {
            $normalized[$key] = $trace[$key] ?? self::defaultValue($key);
        }

        $normalized['run_id'] = (string) $normalized['run_id'];
        if ($normalized['run_id'] === '') {
            throw new InvalidArgumentException(PipelineErrorCode::MISSING_REQUIRED_KEY . ': run_id');
        }

        $normalized['pipeline_version'] = self::normalizePipelineVersion((string) $normalized['pipeline_version']);
        $normalized['pipeline_stage'] = PipelineStage::normalize((string) $normalized['pipeline_stage']);
        $normalized['fetch_strategy'] = self::normalizeFetchStrategy((string) $normalized['fetch_strategy']);
        $normalized['selection_stage'] = self::normalizeSelectionStage((string) $normalized['selection_stage']);

        $normalized['response_preview'] = self::truncatePreview($normalized['response_preview']);
        $normalized['first_matched_block_preview'] = self::truncatePreview($normalized['first_matched_block_preview']);

        $normalized['draw_id'] = self::integerOrNull($normalized['draw_id']);
        $normalized['source_id'] = self::integerOrNull($normalized['source_id']);
        $normalized['record_selector_match_count'] = max(0, (int) ($normalized['record_selector_match_count'] ?? 0));

        $normalized['parsed_raw_fields'] = is_array($normalized['parsed_raw_fields']) ? $normalized['parsed_raw_fields'] : [];
        $normalized['mapped_fields'] = is_array($normalized['mapped_fields']) ? $normalized['mapped_fields'] : [];
        $normalized['selection_result'] = is_array($normalized['selection_result']) ? $normalized['selection_result'] : [];
        $normalized['validation_result'] = is_array($normalized['validation_result']) ? $normalized['validation_result'] : [];
        $normalized['readiness_result'] = is_array($normalized['readiness_result']) ? $normalized['readiness_result'] : [];
        $normalized['phase_timing'] = is_array($normalized['phase_timing']) ? $normalized['phase_timing'] : [];
        $normalized['selected_capture'] = is_array($normalized['selected_capture']) ? $normalized['selected_capture'] : [];
        $normalized['artifact_refs'] = is_array($normalized['artifact_refs']) ? $normalized['artifact_refs'] : [];

        $normalized['status'] = (string) $normalized['status'];
        $normalized['final_decision'] = (string) $normalized['final_decision'];
        $normalized['error_code'] = self::stringOrNull($normalized['error_code']);
        $normalized['error_stage'] = self::stringOrNull($normalized['error_stage']);
        $normalized['fetched_url'] = self::stringOrNull($normalized['fetched_url']);
        $normalized['response_content_type'] = self::stringOrNull($normalized['response_content_type']);
        $normalized['selected_driver'] = self::stringOrNull($normalized['selected_driver']);
        $normalized['payload_origin'] = self::stringOrNull($normalized['payload_origin']);

        ksort($normalized);

        return $normalized;
    }

    private static function defaultValue(string $key): mixed
    {
        return match ($key) {
            'record_selector_match_count' => 0,
            'parsed_raw_fields', 'mapped_fields', 'selection_result', 'validation_result', 'readiness_result' => [],
            'phase_timing', 'selected_capture', 'artifact_refs' => [],
            'pipeline_version' => CompiledSourcePipelineData::VERSION_LEGACY,
            'pipeline_stage' => PipelineStage::FETCH,
            'fetch_strategy' => 'JSON_HTTP',
            'selection_stage' => SelectionConfigData::STAGE_POST_MAPPING,
            'final_decision' => 'PENDING',
            'status' => 'PENDING',
            default => null,
        };
    }

    private static function normalizePipelineVersion(string $value): string
    {
        $normalized = strtoupper(trim($value));
        if (! in_array($normalized, CompiledSourcePipelineData::allowedPipelineVersions(), true)) {
            throw new InvalidArgumentException(PipelineErrorCode::INVALID_PIPELINE_VERSION . ': ' . $value);
        }

        return $normalized;
    }

    private static function normalizeFetchStrategy(string $value): string
    {
        $normalized = strtoupper(trim($value));
        $allowed = ['JSON_HTTP', 'HTML_HTTP', 'RENDERED_BROWSER', 'EMBEDDED_JSON', 'MANUAL_INPUT'];
        if (! in_array($normalized, $allowed, true)) {
            throw new InvalidArgumentException(PipelineErrorCode::INVALID_FETCH_STRATEGY . ': ' . $value);
        }

        return $normalized;
    }

    private static function normalizeSelectionStage(string $value): string
    {
        $normalized = strtoupper(trim($value));
        if (! in_array($normalized, SelectionConfigData::allowedStages(), true)) {
            throw new InvalidArgumentException(PipelineErrorCode::INVALID_SELECTION_STAGE . ': ' . $value);
        }

        return $normalized;
    }

    private static function integerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException(PipelineErrorCode::INVALID_TRACE_SHAPE . ': expected integer');
        }

        return (int) $value;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function truncatePreview(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = is_scalar($value) ? (string) $value : (json_encode($value, JSON_UNESCAPED_UNICODE) ?: '');
        if (mb_strlen($text) <= self::PREVIEW_LIMIT) {
            return $text;
        }

        return mb_substr($text, 0, self::PREVIEW_LIMIT) . '...';
    }
}
