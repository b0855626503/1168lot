<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Services\SettlementService;
use PHPUnit\Framework\TestCase;

class SettlementServiceTest extends TestCase
{
    public function test_normalize_result_number_requires_valid_lengths(): void
    {
        $service = new SettlementService();

        $result = $service->normalizeResultNumber([
            'first_prize' => '123456',
            'last_2_digits' => '89',
        ]);

        $this->assertSame([
            'first_prize' => '123456',
            'last_2_digits' => '89',
            'top_3' => '456',
            'top_2' => '56',
            'bottom_2' => '89',
        ], $result);
    }

    public function test_normalize_result_number_accepts_five_digit_first_prize(): void
    {
        $service = new SettlementService();

        $result = $service->normalizeResultNumber([
            'first_prize' => '12345',
            'last_2_digits' => '67',
        ]);

        $this->assertSame([
            'first_prize' => '12345',
            'last_2_digits' => '67',
            'top_3' => '345',
            'top_2' => '45',
            'bottom_2' => '67',
        ], $result);
    }

    public function test_is_winning_bet_matches_all_supported_bet_types(): void
    {
        $service = new SettlementService();
        $result = [
            'top_3' => '123',
            'top_2' => '12',
            'bottom_2' => '45',
        ];

        $this->assertTrue($service->isWinningBet(BetType::TOP_3, '123', $result));
        $this->assertTrue($service->isWinningBet(BetType::TOD_3, '321', $result));
        $this->assertTrue($service->isWinningBet(BetType::TOP_2, '12', $result));
        $this->assertTrue($service->isWinningBet(BetType::BOTTOM_2, '45', $result));
        $this->assertTrue($service->isWinningBet(BetType::RUN_TOP, '1', $result));
        $this->assertTrue($service->isWinningBet(BetType::RUN_BOTTOM, '5', $result));

        $this->assertFalse($service->isWinningBet(BetType::TOP_3, '999', $result));
        $this->assertFalse($service->isWinningBet(BetType::TOD_3, '124', $result));
        $this->assertFalse($service->isWinningBet(BetType::TOP_2, '23', $result));
        $this->assertFalse($service->isWinningBet(BetType::BOTTOM_2, '54', $result));
        $this->assertFalse($service->isWinningBet(BetType::RUN_TOP, '9', $result));
        $this->assertFalse($service->isWinningBet(BetType::RUN_BOTTOM, '9', $result));
    }

    public function test_describe_result_number_returns_human_readable_text(): void
    {
        $service = new SettlementService();

        $this->assertSame(
            'รางวัลที่ 1 123456 / เลขท้าย 2 ตัว 89 / 3 ตัวบน 456 / 2 ตัวบน 56',
            $service->describeResultNumber([
                'first_prize' => '123456',
                'last_2_digits' => '89',
            ])
        );
    }
}
