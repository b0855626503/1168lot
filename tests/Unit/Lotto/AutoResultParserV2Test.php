<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Exceptions\ResultParseException;
use Gametech\Lotto\Services\AutoResult\ResultParser;
use PHPUnit\Framework\TestCase;

class AutoResultParserV2Test extends TestCase
{
    public function test_parse_v2_html_record_list_returns_record_scoped_candidates(): void
    {
        $parser = new ResultParser();
        $html = file_get_contents(dirname(__DIR__, 2) . '/Fixtures/Lotto/auto_result_multi_block.html');

        $parsed = $parser->parse('CSS_SELECTOR', [
            'version' => 2,
            'mode' => 'record_list',
            'record_parser_type' => 'CSS_SELECTOR',
            'record_selector' => '.result-block',
            'fields' => [
                'draw_date' => ['type' => 'CSS_SELECTOR', 'selector' => '.draw-date'],
                'first_prize' => ['type' => 'CSS_SELECTOR', 'selector' => '.first-prize'],
                'last_2_digits' => ['type' => 'CSS_SELECTOR', 'selector' => '.last2'],
            ],
        ], (string) $html);

        $this->assertSame(2, $parsed['candidate_count']);
        $this->assertSame('26/03/2026', $parsed['candidates'][0]['fields']['draw_date']);
        $this->assertSame('987654', $parsed['candidates'][1]['fields']['first_prize']);
        $this->assertSame('54', $parsed['candidates'][1]['fields']['last_2_digits']);
        $this->assertSame(strlen((string) $html), $parsed['_debug']['raw_response_length']);
        $this->assertNotEmpty($parsed['_debug']['raw_response_preview']);
        $this->assertSame(2, $parsed['_debug']['record_selector_match_count']);
        $this->assertNotEmpty($parsed['_debug']['first_matched_block']);
    }

    public function test_parse_v2_regex_record_list_with_invalid_pattern_throws(): void
    {
        $parser = new ResultParser();

        $this->expectException(ResultParseException::class);
        $this->expectExceptionMessage('record_selector');

        $parser->parse('REGEX', [
            'version' => 2,
            'mode' => 'record_list',
            'record_parser_type' => 'REGEX',
            'record_selector' => '/([a-z]+/',
            'fields' => [
                'value' => '/(\d+)/',
            ],
        ], 'A=12');
    }
}
