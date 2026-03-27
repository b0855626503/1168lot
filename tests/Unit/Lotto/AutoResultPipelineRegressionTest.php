<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Services\AutoResult\ResultCandidateSelector;
use Gametech\Lotto\Services\AutoResult\ResultMapper;
use Gametech\Lotto\Services\AutoResult\ResultParseContext;
use Gametech\Lotto\Services\AutoResult\ResultParser;
use Gametech\Lotto\Services\AutoResult\ResultTransformChain;
use Gametech\Lotto\Services\AutoResult\ResultValidator;
use PHPUnit\Framework\TestCase;

class AutoResultPipelineRegressionTest extends TestCase
{
    public function test_cross_block_mismatch_fixture_selects_correct_candidate_context(): void
    {
        $parser = new ResultParser();
        $selector = new ResultCandidateSelector();
        $mapper = new ResultMapper(new ResultTransformChain());
        $validator = new ResultValidator();

        $html = file_get_contents(dirname(__DIR__, 2) . '/Fixtures/Lotto/auto_result_multi_block.html');

        $parserConfig = [
            'version' => 2,
            'mode' => 'record_list',
            'record_parser_type' => 'CSS_SELECTOR',
            'record_selector' => '.result-block',
            'record_filters' => ['required_fields' => ['draw_date_raw', 'first_prize_raw', 'last_2_digits_raw']],
            'selection_strategy' => ['type' => 'strict_single_match', 'date_field' => 'draw_date_raw'],
            'fields' => [
                'draw_date_raw' => ['type' => 'CSS_SELECTOR', 'selector' => '.draw-date'],
                'first_prize_raw' => ['type' => 'CSS_SELECTOR', 'selector' => '.first-prize'],
                'last_2_digits_raw' => ['type' => 'CSS_SELECTOR', 'selector' => '.last2'],
            ],
        ];

        $mappingConfig = [
            'draw_date' => [
                'from' => 'draw_date_raw',
                'transforms' => ['trim', ['op' => 'date', 'from' => 'd/m/Y', 'to' => 'Y-m-d']],
            ],
            'first_prize' => [
                'from' => 'first_prize_raw',
                'transforms' => ['trim', 'digits_only'],
            ],
            'last_2_digits' => [
                'from' => 'last_2_digits_raw',
                'transforms' => ['trim', 'digits_only', 'right:2'],
            ],
        ];

        $validationConfig = [
            'required' => ['draw_date', 'first_prize', 'last_2_digits'],
            'fields' => [
                'first_prize' => ['digits' => [6]],
                'last_2_digits' => ['digits' => [2]],
                'draw_date' => ['date_format' => 'Y-m-d'],
            ],
            'expected_draw_date' => ['field' => 'draw_date'],
        ];

        $parsed = $parser->parse('CSS_SELECTOR', $parserConfig, (string) $html);
        $selection = $selector->select($parsed, $parserConfig, $validationConfig, new ResultParseContext('2026-03-27'));

        $this->assertSame('selected', $selection['decision']);

        $mapped = $mapper->map((array) $selection['selected_candidate']['fields'], $mappingConfig);
        $validated = $validator->validate($mapped, $validationConfig, '2026-03-27');

        $this->assertSame('2026-03-27', $validated['draw_date']);
        $this->assertSame('987654', $validated['first_prize']);
        $this->assertSame('54', $validated['last_2_digits']);
    }

    public function test_ambiguous_fixture_is_rejected_in_strict_single_match(): void
    {
        $parser = new ResultParser();
        $selector = new ResultCandidateSelector();

        $html = file_get_contents(dirname(__DIR__, 2) . '/Fixtures/Lotto/auto_result_multi_block_ambiguous.html');

        $parserConfig = [
            'version' => 2,
            'mode' => 'record_list',
            'record_parser_type' => 'CSS_SELECTOR',
            'record_selector' => '.result-block',
            'record_filters' => ['required_fields' => ['draw_date', 'first_prize', 'last_2_digits']],
            'selection_strategy' => ['type' => 'strict_single_match', 'date_field' => 'draw_date'],
            'fields' => [
                'draw_date' => ['type' => 'CSS_SELECTOR', 'selector' => '.draw-date'],
                'first_prize' => ['type' => 'CSS_SELECTOR', 'selector' => '.first-prize'],
                'last_2_digits' => ['type' => 'CSS_SELECTOR', 'selector' => '.last2'],
            ],
        ];

        $validationConfig = [
            'required' => ['draw_date', 'first_prize', 'last_2_digits'],
            'expected_draw_date' => ['field' => 'draw_date'],
        ];

        $parsed = $parser->parse('CSS_SELECTOR', $parserConfig, (string) $html);
        $selection = $selector->select($parsed, $parserConfig, $validationConfig, new ResultParseContext('2026-03-27'));

        $this->assertSame('rejected', $selection['decision']);
        $this->assertSame('ambiguous_candidates_match_expected_draw_date', $selection['rejection_reason']);
    }
}
