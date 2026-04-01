<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Jobs\SendDrawResultSummaryTelegramJob;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LotteryMarket;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SendDrawResultSummaryTelegramJobTest extends TestCase
{
    public function test_format_message_uses_right_three_digits_for_top_three_display(): void
    {
        $market = new LotteryMarket();
        $market->setRawAttributes([
            'id' => 12,
            'name' => 'ฮานอยปกติ',
        ], true);

        $draw = new LottoDraw();
        $draw->setRawAttributes([
            'market_id' => 12,
            'draw_date' => null,
            'status' => 'resulted',
            'result_number' => json_encode([
                'first_prize' => '37188',
                'last_2_digits' => '95',
            ], JSON_THROW_ON_ERROR),
        ], true);
        $draw->setRelation('market', $market);

        $job = new SendDrawResultSummaryTelegramJob(999);
        $method = new ReflectionMethod($job, 'formatMessage');
        $method->setAccessible(true);

        $message = (string) $method->invoke($job, $draw, [
            'ticket_count' => 0,
            'winning_ticket_count' => 0,
            'losing_ticket_count' => 0,
            'total_win_amount' => 0.0,
            'total_lose_amount' => 0.0,
            'net_amount' => 0.0,
        ]);

        $this->assertStringContainsString('🎯 3 ตัวบน: 188', $message);
        $this->assertStringContainsString('🎯 2 ตัวล่าง: 95', $message);
        $this->assertStringNotContainsString('🎯 3 ตัวบน: 37188', $message);
    }
}
