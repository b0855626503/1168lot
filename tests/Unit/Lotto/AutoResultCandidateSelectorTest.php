<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Services\AutoResult\ResultCandidateSelector;
use Gametech\Lotto\Services\AutoResult\ResultParseContext;
use PHPUnit\Framework\TestCase;

class AutoResultCandidateSelectorTest extends TestCase
{
    public function test_strict_single_match_selects_only_candidate_matching_expected_date(): void
    {
        $selector = new ResultCandidateSelector();

        $decision = $selector->select($this->parsedCandidates([
            ['draw_date' => '26/03/2026', 'first_prize' => '111111', 'last_2_digits' => '11'],
            ['draw_date' => '27/03/2026', 'first_prize' => '987654', 'last_2_digits' => '54'],
        ]), [
            'selection_strategy' => ['type' => 'strict_single_match'],
        ], [
            'required' => ['first_prize', 'last_2_digits', 'draw_date'],
            'expected_draw_date' => ['field' => 'draw_date'],
        ], new ResultParseContext('2026-03-27'));

        $this->assertSame('selected', $decision['decision']);
        $this->assertSame(1, $decision['selected_index']);
        $this->assertSame('expected_draw_date_matched_and_only_candidate', $decision['decision_reason']);
    }

    public function test_strict_single_match_rejects_when_no_candidate_matches_expected_date(): void
    {
        $selector = new ResultCandidateSelector();

        $decision = $selector->select($this->parsedCandidates([
            ['draw_date' => '26/03/2026', 'first_prize' => '111111', 'last_2_digits' => '11'],
        ]), [
            'selection_strategy' => ['type' => 'strict_single_match'],
        ], [
            'required' => ['first_prize', 'last_2_digits', 'draw_date'],
            'expected_draw_date' => ['field' => 'draw_date'],
        ], new ResultParseContext('2026-03-27'));

        $this->assertSame('rejected', $decision['decision']);
        $this->assertSame('no_candidate_matches_expected_draw_date', $decision['rejection_reason']);
    }

    public function test_strict_single_match_rejects_ambiguity_with_multiple_matches(): void
    {
        $selector = new ResultCandidateSelector();

        $decision = $selector->select($this->parsedCandidates([
            ['draw_date' => '27/03/2026', 'first_prize' => '222222', 'last_2_digits' => '22'],
            ['draw_date' => '27/03/2026', 'first_prize' => '333333', 'last_2_digits' => '33'],
        ]), [
            'selection_strategy' => ['type' => 'strict_single_match'],
        ], [
            'required' => ['first_prize', 'last_2_digits', 'draw_date'],
            'expected_draw_date' => ['field' => 'draw_date'],
        ], new ResultParseContext('2026-03-27'));

        $this->assertSame('rejected', $decision['decision']);
        $this->assertSame('ambiguous_candidates_match_expected_draw_date', $decision['rejection_reason']);
    }

    public function test_score_ranked_rejects_tie_score_among_valid_candidates(): void
    {
        $selector = new ResultCandidateSelector();

        $decision = $selector->select($this->parsedCandidates([
            ['draw_date' => '27/03/2026', 'first_prize' => '123456', 'last_2_digits' => '12'],
            ['draw_date' => '27/03/2026', 'first_prize' => '654321', 'last_2_digits' => '21'],
        ]), [
            'selection_strategy' => ['type' => 'score_ranked', 'require_date_match' => true],
        ], [
            'required' => ['first_prize', 'last_2_digits', 'draw_date'],
            'expected_draw_date' => ['field' => 'draw_date'],
        ], new ResultParseContext('2026-03-27'));

        $this->assertSame('rejected', $decision['decision']);
        $this->assertSame('tie_score_among_valid_candidates', $decision['rejection_reason']);
    }

    public function test_selector_handles_array_field_values_without_type_error(): void
    {
        $selector = new ResultCandidateSelector();

        $decision = $selector->select($this->parsedCandidates([
            ['draw_date' => '27/03/2026', 'first_prize' => '123456', 'last_2_digits' => '12', 'meta' => ['x' => 1]],
        ]), [
            'selection_strategy' => ['type' => 'strict_single_match'],
        ], [
            'required' => ['first_prize', 'last_2_digits', 'draw_date'],
            'expected_draw_date' => ['field' => 'draw_date'],
        ], new ResultParseContext('2026-03-27'));

        $this->assertSame('selected', $decision['decision']);
    }

    /**
     * @param array<int,array<string,mixed>> $candidateFields
     * @return array<string,mixed>
     */
    private function parsedCandidates(array $candidateFields): array
    {
        $candidates = [];
        foreach ($candidateFields as $index => $fields) {
            $candidates[] = [
                'index' => $index,
                'fields' => $fields,
            ];
        }

        return [
            'version' => 2,
            'mode' => 'record_list',
            'candidates' => $candidates,
        ];
    }
}
