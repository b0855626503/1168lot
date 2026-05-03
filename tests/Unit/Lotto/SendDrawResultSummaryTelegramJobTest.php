<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Jobs\SendDrawResultSummaryTelegramJob;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\YeekeeRound;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SendDrawResultSummaryTelegramJobTest extends TestCase
{
    public function test_format_message_uses_right_three_digits_for_top_three_display(): void
    {
        $market = new LotteryMarket;
        $market->setRawAttributes([
            'id' => 12,
            'name' => 'ฮานอยปกติ',
        ], true);

        $draw = new LottoDraw;
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
        $this->assertStringNotContainsString('รอบที่', $message);
    }

    public function test_format_message_yeekee_includes_round_label(): void
    {
        $market = new LotteryMarket;
        $market->setRawAttributes([
            'id' => 20,
            'name' => 'หวยยี่กี่',
            'result_mode' => LotteryMarket::RESULT_MODE_YEEKEE,
        ], true);

        $round = new YeekeeRound;
        $round->setRawAttributes(['id' => 1, 'lotto_draw_id' => 888, 'round_no' => 55], true);

        $draw = new LottoDraw;
        $draw->setRawAttributes([
            'market_id' => 20,
            'draw_date' => '2026-05-04',
            'status' => 'resulted',
            'result_number' => json_encode(['first_prize' => '12345', 'last_2_digits' => '45'], JSON_THROW_ON_ERROR),
        ], true);
        $draw->setRelation('market', $market);
        $draw->setRelation('yeekeeRound', $round);

        $job = new SendDrawResultSummaryTelegramJob(888);
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

        $this->assertStringContainsString('หวยยี่กี่ รอบที่ 55', $message);
    }

    public function test_format_message_normal_draw_has_no_round_label(): void
    {
        $market = new LotteryMarket;
        $market->setRawAttributes([
            'id' => 21,
            'name' => 'หวยออมสิน',
            'result_mode' => 'normal',
        ], true);

        $draw = new LottoDraw;
        $draw->setRawAttributes([
            'market_id' => 21,
            'draw_date' => '2026-05-04',
            'status' => 'resulted',
            'result_number' => json_encode(['first_prize' => '12345', 'last_2_digits' => '45'], JSON_THROW_ON_ERROR),
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

        $this->assertStringContainsString('หวยออมสิน', $message);
        $this->assertStringNotContainsString('รอบที่', $message);
    }
}
