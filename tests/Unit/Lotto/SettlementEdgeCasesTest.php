<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Services\SettlementService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Edge case regression tests for SettlementService.
 *
 * The basic happy-path coverage lives in SettlementServiceTest.php.
 * This file covers boundary conditions: leading zeros, non-numeric stripping,
 * TOD_3 permutations, repeated digits, and unknown bet type fallback.
 */
class SettlementEdgeCasesTest extends TestCase
{
    private SettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SettlementService();
    }

    // ------------------------------------------------------------------ //
    // normalizeResultNumber edge cases
    // ------------------------------------------------------------------ //

    public function test_normalize_preserves_leading_zeros(): void
    {
        $result = $this->service->normalizeResultNumber([
            'top_3'    => '007',
            'bottom_2' => '01',
        ]);

        $this->assertSame('007', $result['top_3']);
        $this->assertSame('01', $result['bottom_2']);
    }

    public function test_normalize_strips_non_numeric_separators(): void
    {
        $result = $this->service->normalizeResultNumber([
            'top_3'    => '1-2-3',
            'bottom_2' => '4 5',
        ]);

        $this->assertSame('123', $result['top_3']);
        $this->assertSame('45', $result['bottom_2']);
    }

    public function test_normalize_rejects_top_3_with_only_two_digits(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ผล 3 ตัวบนต้องมี 3 หลัก');

        $this->service->normalizeResultNumber([
            'top_3'    => '12',
            'bottom_2' => '45',
        ]);
    }

    public function test_normalize_rejects_top_3_with_four_digits(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->normalizeResultNumber([
            'top_3'    => '1234',
            'bottom_2' => '45',
        ]);
    }

    public function test_normalize_rejects_bottom_2_with_one_digit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ผล 2 ตัวล่างต้องมี 2 หลัก');

        $this->service->normalizeResultNumber([
            'top_3'    => '123',
            'bottom_2' => '4',
        ]);
    }

    public function test_normalize_rejects_bottom_2_with_three_digits(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->normalizeResultNumber([
            'top_3'    => '123',
            'bottom_2' => '456',
        ]);
    }

    public function test_normalize_rejects_empty_input(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->normalizeResultNumber([]);
    }

    // ------------------------------------------------------------------ //
    // isWinningBet — TOP_3
    // ------------------------------------------------------------------ //

    public function test_top_3_exact_match_with_leading_zero(): void
    {
        $result = ['top_3' => '007', 'bottom_2' => '45'];

        $this->assertTrue($this->service->isWinningBet(BetType::TOP_3, '007', $result));
        $this->assertFalse($this->service->isWinningBet(BetType::TOP_3, '7', $result));
        $this->assertFalse($this->service->isWinningBet(BetType::TOP_3, '07', $result));
    }

    public function test_top_3_strips_non_numeric_from_number_input(): void
    {
        $result = ['top_3' => '123', 'bottom_2' => '45'];

        $this->assertTrue($this->service->isWinningBet(BetType::TOP_3, ' 1 2 3 ', $result));
        $this->assertFalse($this->service->isWinningBet(BetType::TOP_3, ' 9 9 9 ', $result));
    }

    // ------------------------------------------------------------------ //
    // isWinningBet — TOD_3 (โต๊ด: same digits in any order)
    // ------------------------------------------------------------------ //

    public function test_tod_3_matches_all_six_permutations(): void
    {
        $result = ['top_3' => '123', 'bottom_2' => '45'];

        foreach (['123', '132', '213', '231', '312', '321'] as $perm) {
            $this->assertTrue(
                $this->service->isWinningBet(BetType::TOD_3, $perm, $result),
                "Expected TOD_3 '{$perm}' to win against top_3=123"
            );
        }
    }

    public function test_tod_3_rejects_wrong_digits(): void
    {
        $result = ['top_3' => '123', 'bottom_2' => '45'];

        $this->assertFalse($this->service->isWinningBet(BetType::TOD_3, '124', $result));
        $this->assertFalse($this->service->isWinningBet(BetType::TOD_3, '999', $result));
    }

    public function test_tod_3_with_repeated_digits_permits_valid_permutations(): void
    {
        $result = ['top_3' => '112', 'bottom_2' => '45'];

        $this->assertTrue($this->service->isWinningBet(BetType::TOD_3, '112', $result));
        $this->assertTrue($this->service->isWinningBet(BetType::TOD_3, '121', $result));
        $this->assertTrue($this->service->isWinningBet(BetType::TOD_3, '211', $result));
    }

    public function test_tod_3_with_repeated_digits_rejects_different_repeat_count(): void
    {
        $result = ['top_3' => '112', 'bottom_2' => '45'];

        // '111' sorted = [1,1,1] vs '112' sorted = [1,1,2] → mismatch
        $this->assertFalse($this->service->isWinningBet(BetType::TOD_3, '111', $result));
    }

    public function test_tod_3_rejects_non_three_digit_number(): void
    {
        $result = ['top_3' => '123', 'bottom_2' => '45'];

        // strlen after strip must be 3; 2-digit input never matches
        $this->assertFalse($this->service->isWinningBet(BetType::TOD_3, '12', $result));
    }

    // ------------------------------------------------------------------ //
    // isWinningBet — TOP_2 (2 ตัวบน: last two digits of top_3)
    // ------------------------------------------------------------------ //

    public function test_top_2_matches_last_two_digits_of_top_3(): void
    {
        $result = ['top_3' => '123', 'bottom_2' => '45'];

        $this->assertTrue($this->service->isWinningBet(BetType::TOP_2, '23', $result));
        $this->assertFalse($this->service->isWinningBet(BetType::TOP_2, '12', $result));
        $this->assertFalse($this->service->isWinningBet(BetType::TOP_2, '13', $result));
    }

    public function test_top_2_with_leading_zero_result(): void
    {
        $result = ['top_3' => '012', 'bottom_2' => '34'];

        $this->assertTrue($this->service->isWinningBet(BetType::TOP_2, '12', $result));
        $this->assertFalse($this->service->isWinningBet(BetType::TOP_2, '01', $result));
    }

    // ------------------------------------------------------------------ //
    // isWinningBet — BOTTOM_2
    // ------------------------------------------------------------------ //

    public function test_bottom_2_exact_match(): void
    {
        $result = ['top_3' => '123', 'bottom_2' => '45'];

        $this->assertTrue($this->service->isWinningBet(BetType::BOTTOM_2, '45', $result));
        $this->assertFalse($this->service->isWinningBet(BetType::BOTTOM_2, '54', $result));
        $this->assertFalse($this->service->isWinningBet(BetType::BOTTOM_2, '4', $result));
    }

    // ------------------------------------------------------------------ //
    // isWinningBet — RUN_TOP (วิ่งบน: single digit in top_3)
    // ------------------------------------------------------------------ //

    public function test_run_top_matches_each_digit_individually(): void
    {
        $result = ['top_3' => '123', 'bottom_2' => '45'];

        $this->assertTrue($this->service->isWinningBet(BetType::RUN_TOP, '1', $result));
        $this->assertTrue($this->service->isWinningBet(BetType::RUN_TOP, '2', $result));
        $this->assertTrue($this->service->isWinningBet(BetType::RUN_TOP, '3', $result));
        $this->assertFalse($this->service->isWinningBet(BetType::RUN_TOP, '4', $result));
        $this->assertFalse($this->service->isWinningBet(BetType::RUN_TOP, '9', $result));
    }

    public function test_run_top_rejects_multi_digit_number(): void
    {
        $result = ['top_3' => '123', 'bottom_2' => '45'];

        // Even though '1' is in '123', '12' is multi-digit and must not match
        $this->assertFalse($this->service->isWinningBet(BetType::RUN_TOP, '12', $result));
    }

    // ------------------------------------------------------------------ //
    // isWinningBet — RUN_BOTTOM (วิ่งล่าง: single digit in bottom_2)
    // ------------------------------------------------------------------ //

    public function test_run_bottom_matches_each_digit_individually(): void
    {
        $result = ['top_3' => '123', 'bottom_2' => '45'];

        $this->assertTrue($this->service->isWinningBet(BetType::RUN_BOTTOM, '4', $result));
        $this->assertTrue($this->service->isWinningBet(BetType::RUN_BOTTOM, '5', $result));
        $this->assertFalse($this->service->isWinningBet(BetType::RUN_BOTTOM, '1', $result));
        $this->assertFalse($this->service->isWinningBet(BetType::RUN_BOTTOM, '2', $result));
    }

    public function test_run_bottom_rejects_multi_digit_number(): void
    {
        $result = ['top_3' => '123', 'bottom_2' => '45'];

        $this->assertFalse($this->service->isWinningBet(BetType::RUN_BOTTOM, '45', $result));
    }

    // ------------------------------------------------------------------ //
    // isWinningBet — unknown type fallback
    // ------------------------------------------------------------------ //

    public function test_unknown_bet_type_returns_false(): void
    {
        $result = ['top_3' => '123', 'bottom_2' => '45'];

        $this->assertFalse($this->service->isWinningBet('unknown_type', '123', $result));
        $this->assertFalse($this->service->isWinningBet('', '123', $result));
        $this->assertFalse($this->service->isWinningBet('TOP_3', '123', $result)); // uppercase wrong
    }
}
