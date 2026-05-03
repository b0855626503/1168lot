<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Support\LottoMarketDisplayFormatter;
use PHPUnit\Framework\TestCase;

class LottoMarketDisplayFormatterTest extends TestCase
{
    public function test_plain_text_includes_round_for_yeekee_when_round_exists(): void
    {
        $formatter = new LottoMarketDisplayFormatter;

        $label = $formatter->formatPlain('หวยยี่กี่', LotteryMarket::RESULT_MODE_YEEKEE, 12);

        $this->assertSame('หวยยี่กี่ (รอบ 12)', $label);
    }

    public function test_plain_text_falls_back_without_round_when_missing(): void
    {
        $formatter = new LottoMarketDisplayFormatter;

        $label = $formatter->formatPlain('หวยยี่กี่', LotteryMarket::RESULT_MODE_YEEKEE, null);

        $this->assertSame('หวยยี่กี่', $label);
    }

    public function test_plain_text_does_not_append_round_for_non_yeekee(): void
    {
        $formatter = new LottoMarketDisplayFormatter;

        $label = $formatter->formatPlain('หวยรัฐบาล', LotteryMarket::RESULT_MODE_NORMAL, 8);

        $this->assertSame('หวยรัฐบาล', $label);
    }

    public function test_html_output_contains_badge_only_for_yeekee_with_round(): void
    {
        $formatter = new LottoMarketDisplayFormatter;

        $withBadge = $formatter->formatHtml('หวยยี่กี่', null, null, LotteryMarket::RESULT_MODE_YEEKEE, 5);
        $withoutBadge = $formatter->formatHtml('หวยรัฐบาล', null, null, LotteryMarket::RESULT_MODE_NORMAL, 5);

        $this->assertStringContainsString('รอบ 5', $withBadge);
        $this->assertStringNotContainsString('รอบ 5', $withoutBadge);
    }
}
