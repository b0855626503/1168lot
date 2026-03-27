<?php

namespace Gametech\Lotto\Services\AutoResult;

use Gametech\Lotto\Exceptions\ResultValidationException;

class ResultValidator
{
    /**
     * @param array<string,mixed> $mapped
     * @param array<string,mixed> $validationConfig
     * @return array<string,mixed>
     */
    public function validate(array $mapped, array $validationConfig = [], ?string $expectedDrawDate = null): array
    {
        $required = $validationConfig['required'] ?? ['first_prize', 'last_2_digits'];
        if (! is_array($required) || $required === []) {
            $required = ['first_prize', 'last_2_digits'];
        }

        foreach ($required as $field) {
            if (! is_string($field) || trim($field) === '') {
                continue;
            }
            $value = $mapped[$field] ?? null;
            if ($value === null || trim((string) $value) === '') {
                throw new ResultValidationException('VALIDATION_ERROR: required field missing: ' . $field);
            }
        }

        $normalized = $mapped;

        if (array_key_exists('first_prize', $normalized)) {
            $normalized['first_prize'] = preg_replace('/\D+/', '', (string) $normalized['first_prize']);
        }

        if (array_key_exists('last_2_digits', $normalized)) {
            $normalized['last_2_digits'] = preg_replace('/\D+/', '', (string) $normalized['last_2_digits']);
        }

        $fieldRules = $validationConfig['fields'] ?? [];
        if (! is_array($fieldRules)) {
            $fieldRules = [];
        }

        foreach ($fieldRules as $field => $rules) {
            if (! is_string($field) || ! is_array($rules)) {
                continue;
            }
            $this->validateFieldRules($field, $normalized[$field] ?? null, $rules);
        }

        // Backward-compatible default checks.
        $firstPrize = (string) ($normalized['first_prize'] ?? '');
        $last2Digits = (string) ($normalized['last_2_digits'] ?? '');

        if ($firstPrize === '' || $last2Digits === '') {
            throw new ResultValidationException('ผลออกยังไม่พร้อม (NOT_READY)');
        }

        if (! in_array(strlen($firstPrize), [5, 6], true)) {
            throw new ResultValidationException('VALIDATION_ERROR: first_prize ต้องมี 5 หรือ 6 หลัก');
        }

        if (strlen($last2Digits) !== 2) {
            throw new ResultValidationException('VALIDATION_ERROR: last_2_digits ต้องมี 2 หลัก');
        }

        $expectedDateRule = is_array($validationConfig['expected_draw_date'] ?? null)
            ? $validationConfig['expected_draw_date']
            : [];
        $drawDateField = (string) ($expectedDateRule['field'] ?? 'draw_date');

        $this->validateExpectedDrawDate($normalized, $drawDateField, $expectedDrawDate);

        return $normalized;
    }

    /**
     * @param mixed $value
     * @param array<string,mixed> $rules
     */
    private function validateFieldRules(string $field, $value, array $rules): void
    {
        $raw = (string) ($value ?? '');

        if (isset($rules['digits']) && is_array($rules['digits']) && $rules['digits'] !== []) {
            $digits = preg_replace('/\D+/', '', $raw);
            $allowedLengths = [];
            foreach ($rules['digits'] as $length) {
                if (is_int($length) || ctype_digit((string) $length)) {
                    $allowedLengths[] = (int) $length;
                }
            }

            if ($allowedLengths !== [] && ! in_array(strlen($digits), $allowedLengths, true)) {
                throw new ResultValidationException('VALIDATION_ERROR: ' . $field . ' digit length invalid');
            }
        }

        if (isset($rules['regex']) && is_string($rules['regex']) && $rules['regex'] !== '') {
            $ok = @preg_match($rules['regex'], $raw);
            if ($ok === false || preg_last_error() !== PREG_NO_ERROR) {
                throw new ResultValidationException('VALIDATION_ERROR: invalid regex rule for ' . $field);
            }
            if ($ok !== 1) {
                throw new ResultValidationException('VALIDATION_ERROR: ' . $field . ' format invalid');
            }
        }

        if (($rules['date_format'] ?? null) !== null) {
            $format = (string) $rules['date_format'];
            if ($format !== '' && ! $this->isDateInFormat($raw, $format)) {
                throw new ResultValidationException('VALIDATION_ERROR: ' . $field . ' date format invalid');
            }
        }
    }

    /**
     * @param array<string,mixed> $normalized
     */
    private function validateExpectedDrawDate(array $normalized, string $drawDateField, ?string $expectedDrawDate): void
    {
        $expected = trim((string) $expectedDrawDate);
        if ($expected === '') {
            return;
        }

        $actual = trim((string) ($normalized[$drawDateField] ?? ''));
        if ($actual === '') {
            throw new ResultValidationException('VALIDATION_ERROR: draw_date missing for expected_draw_date check');
        }

        if ($actual === $expected) {
            return;
        }

        $normalizedActual = $this->normalizeDate($actual);
        $normalizedExpected = $this->normalizeDate($expected);
        if ($normalizedActual === null || $normalizedExpected === null || $normalizedActual !== $normalizedExpected) {
            throw new ResultValidationException('VALIDATION_ERROR: draw_date does not match expected_draw_date');
        }
    }

    private function isDateInFormat(string $value, string $format): bool
    {
        try {
            $dt = \Illuminate\Support\Carbon::createFromFormat($format, $value);
            return $dt->format($format) === $value;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function normalizeDate(string $value): ?string
    {
        $raw = trim($value);
        if ($raw === '') {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'Ymd'];
        foreach ($formats as $format) {
            try {
                return \Illuminate\Support\Carbon::createFromFormat($format, $raw)->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }

        try {
            return \Illuminate\Support\Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
