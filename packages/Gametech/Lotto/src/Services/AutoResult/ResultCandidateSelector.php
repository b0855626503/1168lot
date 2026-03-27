<?php

namespace Gametech\Lotto\Services\AutoResult;

class ResultCandidateSelector
{
    private const STRATEGY_STRICT_SINGLE_MATCH = 'strict_single_match';
    private const STRATEGY_SCORE_RANKED = 'score_ranked';
    private const STRATEGY_FIRST_MATCH = 'first_match';
    private const STRATEGY_LATEST_RECORD = 'latest_record';

    /**
     * @param array<string,mixed> $parsed
     * @param array<string,mixed> $parserConfig
     * @param array<string,mixed> $validationConfig
     * @return array<string,mixed>
     */
    public function select(array $parsed, array $parserConfig, array $validationConfig, ResultParseContext $context): array
    {
        $candidates = $this->extractCandidates($parsed);
        $strategyConfig = is_array($parserConfig['selection_strategy'] ?? null) ? $parserConfig['selection_strategy'] : [];
        $strategy = strtolower((string) ($strategyConfig['type'] ?? self::STRATEGY_STRICT_SINGLE_MATCH));
        $expectedDrawDate = $context->expectedDrawDate;

        $requiredFields = $this->requiredFields($parserConfig, $validationConfig);
        $dateField = (string) (($strategyConfig['date_field'] ?? ($validationConfig['expected_draw_date']['field'] ?? 'draw_date')));

        $evaluated = [];
        foreach ($candidates as $candidate) {
            $fields = is_array($candidate['fields'] ?? null) ? $candidate['fields'] : [];
            $missingRequired = $this->missingRequiredFields($fields, $requiredFields);
            $candidateDate = (string) ($fields[$dateField] ?? '');
            $dateMatched = $this->matchesExpectedDate($candidateDate, $expectedDrawDate);
            $score = $this->scoreCandidate($fields, $dateMatched, $missingRequired);

            $evaluated[] = [
                'index' => (int) ($candidate['index'] ?? 0),
                'fields' => $fields,
                'missing_required_fields' => $missingRequired,
                'date_field' => $dateField,
                'candidate_draw_date' => $candidateDate,
                'date_matched' => $dateMatched,
                'score' => $score,
                'is_required_valid' => $missingRequired === [],
            ];
        }

        if ($evaluated === []) {
            return $this->rejected('no_candidates', $evaluated);
        }

        $decision = match ($strategy) {
            self::STRATEGY_SCORE_RANKED => $this->selectScoreRanked($evaluated, $expectedDrawDate, $strategyConfig),
            self::STRATEGY_FIRST_MATCH => $this->selectFirstMatch($evaluated, $expectedDrawDate),
            self::STRATEGY_LATEST_RECORD => $this->selectLatestRecord($evaluated, $expectedDrawDate),
            default => $this->selectStrictSingleMatch($evaluated, $expectedDrawDate),
        };

        $decision['strategy'] = $strategy;
        $decision['candidate_count'] = count($evaluated);
        $decision['candidates'] = $evaluated;

        return $decision;
    }

    /**
     * @param array<string,mixed> $parsed
     * @return array<int,array<string,mixed>>
     */
    private function extractCandidates(array $parsed): array
    {
        $candidates = $parsed['candidates'] ?? null;
        if (is_array($candidates) && $candidates !== []) {
            return array_values(array_filter($candidates, static fn ($item) => is_array($item)));
        }

        return [[
            'index' => 0,
            'fields' => $parsed,
        ]];
    }

    /**
     * @param array<string,mixed> $fields
     * @param array<int,string> $requiredFields
     * @return array<int,string>
     */
    private function missingRequiredFields(array $fields, array $requiredFields): array
    {
        $missing = [];
        foreach ($requiredFields as $field) {
            $value = $fields[$field] ?? null;
            if ($value === null || trim((string) $value) === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    private function matchesExpectedDate(string $candidateDate, ?string $expectedDrawDate): bool
    {
        $expected = trim((string) $expectedDrawDate);
        if ($expected === '') {
            return true;
        }

        $candidate = trim($candidateDate);
        if ($candidate === '') {
            return false;
        }

        if ($candidate === $expected) {
            return true;
        }

        $normalizedCandidate = $this->normalizeDate($candidate);
        $normalizedExpected = $this->normalizeDate($expected);

        return $normalizedCandidate !== null && $normalizedExpected !== null && $normalizedCandidate === $normalizedExpected;
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
                $dt = \Illuminate\Support\Carbon::createFromFormat($format, $raw);
                return $dt->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }

        try {
            return \Illuminate\Support\Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param array<string,mixed> $fields
     * @param array<int,string> $missingRequired
     */
    private function scoreCandidate(array $fields, bool $dateMatched, array $missingRequired): int
    {
        $nonEmpty = 0;
        foreach ($fields as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                $nonEmpty++;
            }
        }

        $requiredScore = max(0, 20 - (count($missingRequired) * 10));

        return ($dateMatched ? 100 : 0) + $requiredScore + $nonEmpty;
    }

    /**
     * @param array<int,array<string,mixed>> $evaluated
     * @return array<string,mixed>
     */
    private function selectStrictSingleMatch(array $evaluated, ?string $expectedDrawDate): array
    {
        $matched = array_values(array_filter($evaluated, static fn (array $item) => (bool) ($item['date_matched'] ?? false)));

        if (trim((string) $expectedDrawDate) !== '') {
            if ($matched === []) {
                return $this->rejected('no_candidate_matches_expected_draw_date', $evaluated);
            }

            if (count($matched) > 1) {
                return $this->rejected('ambiguous_candidates_match_expected_draw_date', $evaluated);
            }

            $winner = $matched[0];
            if (! (bool) ($winner['is_required_valid'] ?? false)) {
                return $this->rejected('selected_candidate_missing_required_fields', $evaluated);
            }

            return $this->selected($winner, 'expected_draw_date_matched_and_only_candidate', $evaluated);
        }

        $valid = array_values(array_filter($evaluated, static fn (array $item) => (bool) ($item['is_required_valid'] ?? false)));
        if (count($valid) !== 1) {
            return $this->rejected('strict_single_match_requires_exactly_one_valid_candidate', $evaluated);
        }

        return $this->selected($valid[0], 'single_valid_candidate_without_expected_draw_date', $evaluated);
    }

    /**
     * @param array<int,array<string,mixed>> $evaluated
     * @param array<string,mixed> $strategyConfig
     * @return array<string,mixed>
     */
    private function selectScoreRanked(array $evaluated, ?string $expectedDrawDate, array $strategyConfig): array
    {
        $requireDateMatch = (bool) ($strategyConfig['require_date_match'] ?? true);
        $pool = $evaluated;

        if ($requireDateMatch && trim((string) $expectedDrawDate) !== '') {
            $pool = array_values(array_filter($pool, static fn (array $item) => (bool) ($item['date_matched'] ?? false)));
            if ($pool === []) {
                return $this->rejected('no_candidate_matches_expected_draw_date', $evaluated);
            }
        }

        $pool = array_values(array_filter($pool, static fn (array $item) => (bool) ($item['is_required_valid'] ?? false)));
        if ($pool === []) {
            return $this->rejected('no_candidate_pass_required_fields', $evaluated);
        }

        usort($pool, static function (array $a, array $b): int {
            return ((int) $b['score']) <=> ((int) $a['score']);
        });

        $winner = $pool[0];
        if (count($pool) > 1 && (int) ($pool[1]['score'] ?? -1) === (int) ($winner['score'] ?? -1)) {
            return $this->rejected('tie_score_among_valid_candidates', $evaluated);
        }

        if (trim((string) $expectedDrawDate) !== '' && ! (bool) ($winner['date_matched'] ?? false)) {
            return $this->rejected('selected_candidate_draw_date_mismatch', $evaluated);
        }

        return $this->selected($winner, 'highest_score_unique_candidate', $evaluated);
    }

    /**
     * @param array<int,array<string,mixed>> $evaluated
     * @return array<string,mixed>
     */
    private function selectFirstMatch(array $evaluated, ?string $expectedDrawDate): array
    {
        $pool = array_values(array_filter($evaluated, static fn (array $item) => (bool) ($item['is_required_valid'] ?? false)));
        if (trim((string) $expectedDrawDate) !== '') {
            $pool = array_values(array_filter($pool, static fn (array $item) => (bool) ($item['date_matched'] ?? false)));
        }

        if ($pool === []) {
            return $this->rejected('no_candidate_for_first_match', $evaluated);
        }

        return $this->selected($pool[0], 'first_match_selected', $evaluated);
    }

    /**
     * @param array<int,array<string,mixed>> $evaluated
     * @return array<string,mixed>
     */
    private function selectLatestRecord(array $evaluated, ?string $expectedDrawDate): array
    {
        $pool = array_values(array_filter($evaluated, static fn (array $item) => (bool) ($item['is_required_valid'] ?? false)));
        if (trim((string) $expectedDrawDate) !== '') {
            $pool = array_values(array_filter($pool, static fn (array $item) => (bool) ($item['date_matched'] ?? false)));
        }

        if ($pool === []) {
            return $this->rejected('no_candidate_for_latest_record', $evaluated);
        }

        usort($pool, static function (array $a, array $b): int {
            return ((int) ($b['index'] ?? 0)) <=> ((int) ($a['index'] ?? 0));
        });

        return $this->selected($pool[0], 'latest_record_selected', $evaluated);
    }

    /**
     * @param array<string,mixed> $winner
     * @param array<int,array<string,mixed>> $evaluated
     * @return array<string,mixed>
     */
    private function selected(array $winner, string $reason, array $evaluated): array
    {
        return [
            'decision' => 'selected',
            'decision_reason' => $reason,
            'selected_index' => (int) ($winner['index'] ?? 0),
            'selected_candidate' => $winner,
            'rejection_reason' => null,
            'candidates' => $evaluated,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $evaluated
     * @return array<string,mixed>
     */
    private function rejected(string $reason, array $evaluated): array
    {
        return [
            'decision' => 'rejected',
            'decision_reason' => null,
            'selected_index' => null,
            'selected_candidate' => null,
            'rejection_reason' => $reason,
            'candidates' => $evaluated,
        ];
    }

    /**
     * @param array<string,mixed> $parserConfig
     * @param array<string,mixed> $validationConfig
     * @return array<int,string>
     */
    private function requiredFields(array $parserConfig, array $validationConfig): array
    {
        $recordFilters = is_array($parserConfig['record_filters'] ?? null) ? $parserConfig['record_filters'] : [];
        $requiredFromFilter = $recordFilters['required_fields'] ?? null;
        if (is_array($requiredFromFilter) && $requiredFromFilter !== []) {
            $required = $requiredFromFilter;
        } else {
            $required = $validationConfig['required'] ?? ['first_prize', 'last_2_digits'];
        }

        if (! is_array($required) || $required === []) {
            return ['first_prize', 'last_2_digits'];
        }

        return array_values(array_filter(array_map(static fn ($item) => is_string($item) ? trim($item) : '', $required)));
    }
}
