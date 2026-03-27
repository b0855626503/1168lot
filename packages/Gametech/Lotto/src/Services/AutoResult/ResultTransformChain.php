<?php

namespace Gametech\Lotto\Services\AutoResult;

use Gametech\Lotto\Exceptions\ResultValidationException;
use Illuminate\Support\Carbon;

class ResultTransformChain
{
    /**
     * @param array<int,mixed> $transforms
     * @return mixed
     */
    public function apply($value, array $transforms)
    {
        $cursor = $value;
        foreach ($transforms as $transform) {
            $cursor = $this->applyOne($cursor, $transform);
        }

        return $cursor;
    }

    /**
     * @param mixed $transform
     * @return mixed
     */
    private function applyOne($value, $transform)
    {
        if ($value === null) {
            return null;
        }

        if (is_string($transform)) {
            return $this->applyStringTransform($value, $transform);
        }

        if (is_array($transform)) {
            $op = strtolower((string) ($transform['op'] ?? ''));
            if ($op === 'date') {
                return $this->applyDateTransform($value, (string) ($transform['from'] ?? ''), (string) ($transform['to'] ?? 'Y-m-d'));
            }
        }

        throw new ResultValidationException('VALIDATION_ERROR: unsupported transform');
    }

    /**
     * @return mixed
     */
    private function applyStringTransform($value, string $transform)
    {
        $stringValue = (string) $value;
        $normalized = strtolower(trim($transform));

        if ($normalized === 'trim') {
            return trim($stringValue);
        }

        if ($normalized === 'digits_only') {
            return preg_replace('/\D+/', '', $stringValue);
        }

        if (preg_match('/^right:(\d+)$/', $normalized, $m) === 1) {
            $len = (int) $m[1];
            return $len > 0 ? substr($stringValue, -$len) : $stringValue;
        }

        if (preg_match('/^left:(\d+)$/', $normalized, $m) === 1) {
            $len = (int) $m[1];
            return $len > 0 ? substr($stringValue, 0, $len) : $stringValue;
        }

        throw new ResultValidationException('VALIDATION_ERROR: unsupported transform ' . $transform);
    }

    /**
     * @return mixed
     */
    private function applyDateTransform($value, string $fromFormat, string $toFormat)
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        try {
            if ($fromFormat !== '') {
                $dt = Carbon::createFromFormat($fromFormat, $raw);
            } else {
                $dt = Carbon::parse($raw);
            }

            return $dt->format($toFormat);
        } catch (\Throwable $e) {
            throw new ResultValidationException('VALIDATION_ERROR: date transform failed');
        }
    }
}
