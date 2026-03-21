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
            'top_3' => '123',
            'bottom_2' => '45',
        ]);

        $this->assertSame([
            'top_3' => '123',
            'bottom_2' => '45',
        ], $result);
    }

    public function test_is_winning_bet_matches_all_supported_bet_types(): void
    {
        $service = new SettlementService();
        $result = [
            'top_3' => '123',
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
            '3 ตัวบน 123 / 2 ตัวล่าง 45',
            $service->describeResultNumber([
                'top_3' => '123',
                'bottom_2' => '45',
            ])
        );
    }
}

