<?php

namespace Gametech\Lotto\Services\AutoResultV2\Executors;

use Gametech\Lotto\Services\AutoResultV2\ConfigData\SelectionConfigData;

class SelectionExecutor
{
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

        $valid = [];
        foreach ($candidates as $candidate) {
            $fields = is_array($candidate['fields'] ?? null) ? $candidate['fields'] : [];
            if (! $this->hasRequired($fields, $required)) {
                continue;
            }

            if ($expectedDrawDate !== '' && $dateField !== '') {
                $candidateDate = trim((string) ($fields[$dateField] ?? ''));
                if (! $this->dateMatches($candidateDate, $expectedDrawDate)) {
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
            if ($value === null || trim((string) $value) === '') {
                return false;
            }
        }

        return true;
    }

    private function dateMatches(string $candidateDate, string $expectedDate): bool
    {
        if ($candidateDate === $expectedDate) {
            return true;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'Ymd'];
        foreach ($formats as $format) {
            try {
                $left = \Illuminate\Support\Carbon::createFromFormat($format, $candidateDate)->format('Y-m-d');
                $right = \Illuminate\Support\Carbon::createFromFormat($format, $expectedDate)->format('Y-m-d');
                if ($left === $right) {
                    return true;
                }
            } catch (\Throwable $e) {
            }
        }

        return false;
    }
}
