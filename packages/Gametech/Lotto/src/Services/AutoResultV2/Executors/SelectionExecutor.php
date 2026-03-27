<?php

namespace Gametech\Lotto\Services\AutoResultV2\Executors;

use Gametech\Lotto\Services\AutoResultV2\ConfigData\SelectionConfigData;
use Illuminate\Support\Facades\Log;

class SelectionExecutor
{
    private const DATE_COMPARE_DIAGNOSTIC_ROUNDS = 200;

    /**
     * @param array<string,mixed> $extracted
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function execute(array $extracted, SelectionConfigData $config, array $context = []): array
    {
        $candidates = is_array($extracted['candidates'] ?? null) ? $extracted['candidates'] : [];
        if ($candidates === []) {
            return [
                'decision' => 'rejected',
                'rejection_reason' => 'NO_CANDIDATES',
                'selected_candidate' => null,
            ];
        }

        $required = $config->requiredFields();
        $expectedDrawDate = trim((string) ($context['expected_draw_date'] ?? ''));
        $dateField = trim((string) ($config->dateField() ?? 'draw_date'));
        $dateCompareDebug = [];

        $valid = [];
        foreach ($candidates as $candidate) {
            $fields = is_array($candidate['fields'] ?? null) ? $candidate['fields'] : [];
            if (! $this->hasRequired($fields, $required)) {
                continue;
            }

            if ($expectedDrawDate !== '' && $dateField !== '') {
                $candidateDate = $this->scalarToTrimmedString($fields[$dateField] ?? null);
                $compare = $this->buildDateComparisonDebug($candidateDate, $expectedDrawDate);
                $dateCompareDebug[] = [
                    'candidate_index' => (int) ($candidate['index'] ?? 0),
                    'date_field' => $dateField,
                    'candidate_date_raw' => $candidateDate,
                    'expected_draw_date' => $expectedDrawDate,
                    'matched' => (bool) $compare['matched'],
                    'direct_match' => (bool) $compare['direct_match'],
                    'format_attempts' => $compare['format_attempts'],
                    'consistency' => $compare['consistency'],
                ];
                $this->logDateCompareStep($context, $dateCompareDebug[count($dateCompareDebug) - 1]);

                if (! (bool) $compare['matched']) {
                    continue;
                }
            }

            $valid[] = $candidate;
        }

        if ($valid === []) {
            return [
                'decision' => 'rejected',
                'rejection_reason' => $expectedDrawDate !== '' ? 'NO_CANDIDATE_MATCHES_EXPECTED_DRAW_DATE' : 'REQUIRED_FIELD_MISSING',
                'selected_candidate' => null,
                'date_compare_debug' => $dateCompareDebug,
            ];
        }

        if (count($valid) > 1 && $config->strategy() === 'strict_single_match') {
            return [
                'decision' => 'rejected',
                'rejection_reason' => 'AMBIGUOUS_CANDIDATES',
                'selected_candidate' => null,
            ];
        }

        return [
            'decision' => 'selected',
            'rejection_reason' => null,
            'selected_candidate' => $valid[0],
        ];
    }

    /**
     * @param array<string,mixed> $fields
     * @param array<int,string> $required
     */
    private function hasRequired(array $fields, array $required): bool
    {
        foreach ($required as $field) {
            $value = $fields[$field] ?? null;
            if ($this->isBlank($value)) {
                return false;
            }
        }

        return true;
    }

    private function dateMatches(string $candidateDate, string $expectedDate): bool
    {
        return (bool) ($this->buildDateComparisonDebug($candidateDate, $expectedDate)['matched'] ?? false);
    }

    private function isBlank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return $value === [];
        }

        if (is_object($value)) {
            return false;
        }

        return trim((string) $value) === '';
    }

    private function scalarToTrimmedString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return '';
    }

    /**
     * @return array<string,mixed>
     */
    private function buildDateComparisonDebug(string $candidateDate, string $expectedDate): array
    {
        $directMatch = $candidateDate === $expectedDate;
        if ($directMatch) {
            return [
                'matched' => true,
                'direct_match' => true,
                'format_attempts' => [],
                'consistency' => $this->runConsistencyCheck($candidateDate, $expectedDate),
            ];
        }

        $matched = false;
        $attempts = [];
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'Ymd'];
        foreach ($formats as $format) {
            $candidateNormalized = $this->normalizeDateByFormat($candidateDate, $format);
            $expectedNormalized = $this->normalizeDateByFormat($expectedDate, $format);
            $formatMatched = $candidateNormalized !== null
                && $expectedNormalized !== null
                && $candidateNormalized === $expectedNormalized;

            $attempts[] = [
                'format' => $format,
                'candidate_normalized' => $candidateNormalized,
                'expected_normalized' => $expectedNormalized,
                'matched' => $formatMatched,
            ];

            if ($formatMatched) {
                $matched = true;
                break;
            }
        }

        return [
            'matched' => $matched,
            'direct_match' => false,
            'format_attempts' => $attempts,
            'consistency' => $this->runConsistencyCheck($candidateDate, $expectedDate),
        ];
    }

    private function normalizeDateByFormat(string $value, string $format): ?string
    {
        try {
            return \Illuminate\Support\Carbon::createFromFormat($format, $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function runConsistencyCheck(string $candidateDate, string $expectedDate): array
    {
        $baseline = $this->dateMatchesCore($candidateDate, $expectedDate);
        $stable = true;
        $firstMismatchRound = null;

        for ($round = 1; $round <= self::DATE_COMPARE_DIAGNOSTIC_ROUNDS; $round++) {
            $current = $this->dateMatchesCore($candidateDate, $expectedDate);
            if ($current !== $baseline) {
                $stable = false;
                $firstMismatchRound = $round;
                break;
            }
        }

        return [
            'rounds' => self::DATE_COMPARE_DIAGNOSTIC_ROUNDS,
            'baseline' => $baseline,
            'stable' => $stable,
            'first_mismatch_round' => $firstMismatchRound,
        ];
    }

    private function dateMatchesCore(string $candidateDate, string $expectedDate): bool
    {
        if ($candidateDate === $expectedDate) {
            return true;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'Ymd'];
        foreach ($formats as $format) {
            $left = $this->normalizeDateByFormat($candidateDate, $format);
            $right = $this->normalizeDateByFormat($expectedDate, $format);
            if ($left !== null && $right !== null && $left === $right) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string,mixed> $context
     * @param array<string,mixed> $payload
     */
    private function logDateCompareStep(array $context, array $payload): void
    {
        $message = 'lotto.v2.selection.date_compare.step';
        $logPayload = [
            'run_id' => $context['run_id'] ?? null,
            'draw_id' => $context['draw_id'] ?? null,
            'source_id' => $context['source_id'] ?? null,
            'expected_draw_date' => $context['expected_draw_date'] ?? null,
            'payload' => $payload,
        ];

        try {
            Log::channel('api')->info($message, $logPayload);
        } catch (\Throwable $e) {
            Log::info($message, $logPayload);
        }
    }
}
