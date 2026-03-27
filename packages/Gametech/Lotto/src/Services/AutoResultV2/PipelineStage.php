<?php

namespace Gametech\Lotto\Services\AutoResultV2;

use InvalidArgumentException;

final class PipelineStage
{
    public const FETCH = 'FETCH';
    public const PARSE = 'PARSE';
    public const SELECT = 'SELECT';
    public const MAP = 'MAP';
    public const VALIDATE = 'VALIDATE';
    public const READINESS = 'READINESS';
    public const SHADOW_COMPARE = 'SHADOW_COMPARE';
    public const COMPILER = 'COMPILER';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [
            self::FETCH,
            self::PARSE,
            self::SELECT,
            self::MAP,
            self::VALIDATE,
            self::READINESS,
            self::SHADOW_COMPARE,
            self::COMPILER,
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
            throw new InvalidArgumentException('pipeline_stage ไม่ถูกต้อง: ' . $value);
        }

        return $normalized;
    }
}

