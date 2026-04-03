<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Exceptions\ResultValidationException;
use Gametech\Lotto\Services\AutoResult\ResultValidator;
use PHPUnit\Framework\TestCase;

class AutoResultValidatorTest extends TestCase
{
    public function test_validator_rejects_expected_draw_date_mismatch(): void
    {
        $validator = new ResultValidator();

        $this->expectException(ResultValidationException::class);
        $this->expectExceptionMessage('draw_date does not match expected_draw_date');

        $validator->validate([
            'first_prize' => '987654',
            'last_2_digits' => '54',
            'draw_date' => '2026-03-26',
        ], [
            'required' => ['first_prize', 'last_2_digits', 'draw_date'],
            'fields' => [
                'first_prize' => ['digits' => [6]],
                'last_2_digits' => ['digits' => [2]],
                'draw_date' => ['date_format' => 'Y-m-d'],
            ],
            'expected_draw_date' => ['field' => 'draw_date'],
        ], '2026-03-27');
    }

    public function test_validator_rejects_invalid_validation_regex_rule(): void
    {
        $validator = new ResultValidator();

        $this->expectException(ResultValidationException::class);
        $this->expectExceptionMessage('invalid regex rule');

        $validator->validate([
            'first_prize' => '987654',
            'last_2_digits' => '54',
        ], [
            'fields' => [
                'first_prize' => ['regex' => '/([0-9]+/'],
            ],
        ]);
    }

    public function test_validator_accepts_valid_payload_with_expected_date(): void
    {
        $validator = new ResultValidator();

        $result = $validator->validate([
            'first_prize' => '987654',
            'last_2_digits' => '54',
            'draw_date' => '27/03/2026',
        ], [
            'required' => ['first_prize', 'last_2_digits', 'draw_date'],
            'fields' => [
                'first_prize' => ['digits' => [6]],
                'last_2_digits' => ['digits' => [2]],
            ],
            'expected_draw_date' => ['field' => 'draw_date'],
        ], '2026-03-27');

        $this->assertSame('987654', $result['first_prize']);
        $this->assertSame('54', $result['last_2_digits']);
        $this->assertSame('27/03/2026', $result['draw_date']);
    }

    public function test_validator_accepts_no_result_marker_payload(): void
    {
        $validator = new ResultValidator();

        $result = $validator->validate([
            'first_prize' => 'งดออกผล',
            'last_2_digits' => '-',
            'draw_date' => '2026-04-03',
        ], [
            'required' => ['first_prize', 'last_2_digits', 'draw_date'],
            'expected_draw_date' => ['field' => 'draw_date'],
        ], '2026-04-03');

        $this->assertTrue((bool) ($result['no_result'] ?? false));
        $this->assertSame('งดออกผล', $result['no_result_reason']);
        $this->assertSame('', $result['first_prize']);
        $this->assertSame('', $result['last_2_digits']);
    }
}
