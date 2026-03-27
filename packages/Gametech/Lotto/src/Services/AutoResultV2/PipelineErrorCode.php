<?php

namespace Gametech\Lotto\Services\AutoResultV2;

use InvalidArgumentException;

final class PipelineErrorCode
{
    public const FETCH_FAILED = 'FETCH_FAILED';
    public const FETCH_DEFERRED = 'FETCH_DEFERRED';
    public const APP_SHELL_ONLY = 'APP_SHELL_ONLY';
    public const RECORD_SELECTOR_NO_MATCH = 'RECORD_SELECTOR_NO_MATCH';
    public const FIELD_NOT_PARSED = 'FIELD_NOT_PARSED';
    public const NO_CANDIDATE_MATCHES_EXPECTED_DRAW_DATE = 'NO_CANDIDATE_MATCHES_EXPECTED_DRAW_DATE';
    public const TRANSFORM_FAILED = 'TRANSFORM_FAILED';
    public const REQUIRED_FIELD_MISSING = 'REQUIRED_FIELD_MISSING';
    public const NOT_READY_PARTIAL_RESULT = 'NOT_READY_PARTIAL_RESULT';
    public const NOT_READY_BUSINESS_RULE = 'NOT_READY_BUSINESS_RULE';
    public const MISSING_REQUIRED_KEY = 'MISSING_REQUIRED_KEY';
    public const INVALID_ENUM = 'INVALID_ENUM';
    public const INVALID_STAGE_REFERENCE = 'INVALID_STAGE_REFERENCE';
    public const INVALID_FIELD_REFERENCE = 'INVALID_FIELD_REFERENCE';
    public const INVALID_TRACE_SHAPE = 'INVALID_TRACE_SHAPE';
    public const INVALID_PREVIEW_PAYLOAD = 'INVALID_PREVIEW_PAYLOAD';
    public const INVALID_PIPELINE_VERSION = 'INVALID_PIPELINE_VERSION';
    public const INVALID_FETCH_STRATEGY = 'INVALID_FETCH_STRATEGY';
    public const INVALID_SELECTION_STAGE = 'INVALID_SELECTION_STAGE';
    public const INVALID_SHADOW_COMPARE_STATUS = 'INVALID_SHADOW_COMPARE_STATUS';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [
            self::MISSING_REQUIRED_KEY,
            self::FETCH_FAILED,
            self::FETCH_DEFERRED,
            self::APP_SHELL_ONLY,
            self::RECORD_SELECTOR_NO_MATCH,
            self::FIELD_NOT_PARSED,
            self::NO_CANDIDATE_MATCHES_EXPECTED_DRAW_DATE,
            self::TRANSFORM_FAILED,
            self::REQUIRED_FIELD_MISSING,
            self::NOT_READY_PARTIAL_RESULT,
            self::NOT_READY_BUSINESS_RULE,
            self::INVALID_ENUM,
            self::INVALID_STAGE_REFERENCE,
            self::INVALID_FIELD_REFERENCE,
            self::INVALID_TRACE_SHAPE,
            self::INVALID_PREVIEW_PAYLOAD,
            self::INVALID_PIPELINE_VERSION,
            self::INVALID_FETCH_STRATEGY,
            self::INVALID_SELECTION_STAGE,
            self::INVALID_SHADOW_COMPARE_STATUS,
        ];
    }

    public static function isValid(?string $value): bool
    {
        return is_string($value) && in_array(strtoupper(trim($value)), self::values(), true);
    }

    public static function normalize(string $value): string
    {
        $normalized = strtoupper(trim($value));

        if (! in_array($normalized, self::values(), true)) {
            throw new InvalidArgumentException('pipeline_error_code ไม่ถูกต้อง: ' . $value);
        }

        return $normalized;
    }
}
