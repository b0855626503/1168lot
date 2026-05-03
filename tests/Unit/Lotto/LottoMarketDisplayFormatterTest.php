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

    public function test_format_draw_subject_yeekee_includes_round_no(): void
    {
        $formatter = new LottoMarketDisplayFormatter;

        $subject = $formatter->formatDrawSubject('หวยยี่กี่', '2026-05-04', LotteryMarket::RESULT_MODE_YEEKEE, 88);

        $this->assertSame('หวยยี่กี่ รอบที่ 88 งวดวันที่ 4', $subject);
    }

    public function test_format_draw_subject_non_yeekee_has_no_round(): void
    {
        $formatter = new LottoMarketDisplayFormatter;

        $subject = $formatter->formatDrawSubject('หวยดาวโจนส์ VIP', '2026-05-04', LotteryMarket::RESULT_MODE_NORMAL, 88);

        $this->assertSame('หวยดาวโจนส์ VIP งวดวันที่ 4', $subject);
    }

    public function test_format_draw_subject_yeekee_without_round_falls_back(): void
    {
        $formatter = new LottoMarketDisplayFormatter;

        $subject = $formatter->formatDrawSubject('หวยยี่กี่', '2026-05-04', LotteryMarket::RESULT_MODE_YEEKEE, null);

        $this->assertSame('หวยยี่กี่ งวดวันที่ 4', $subject);
    }

    public function test_format_status_message_yeekee_closed(): void
    {
        $formatter = new LottoMarketDisplayFormatter;

        $message = $formatter->formatStatusMessage('หวยยี่กี่', '2026-05-04', 'ปิดรับแล้ว', LotteryMarket::RESULT_MODE_YEEKEE, 88);

        $this->assertSame('หวยยี่กี่ รอบที่ 88 งวดวันที่ 4 ปิดรับแล้ว', $message);
    }

    public function test_format_status_message_yeekee_resulted(): void
    {
        $formatter = new LottoMarketDisplayFormatter;

        $message = $formatter->formatStatusMessage('หวยยี่กี่', '2026-05-04', 'ออกผลแล้ว', LotteryMarket::RESULT_MODE_YEEKEE, 33);

        $this->assertSame('หวยยี่กี่ รอบที่ 33 งวดวันที่ 4 ออกผลแล้ว', $message);
    }

    public function test_format_status_message_normal_draw_unchanged(): void
    {
        $formatter = new LottoMarketDisplayFormatter;

        $message = $formatter->formatStatusMessage('หวยดาวโจนส์ VIP', '2026-05-04', 'ปิดรับแล้ว', LotteryMarket::RESULT_MODE_NORMAL, 88);

        $this->assertSame('หวยดาวโจนส์ VIP งวดวันที่ 4 ปิดรับแล้ว', $message);
    }

    public function test_format_status_message_yeekee_missing_round_falls_back_gracefully(): void
    {
        $formatter = new LottoMarketDisplayFormatter;

        $message = $formatter->formatStatusMessage('หวยยี่กี่', '2026-05-04', 'ออกผลแล้ว', LotteryMarket::RESULT_MODE_YEEKEE, null);

        $this->assertSame('หวยยี่กี่ งวดวันที่ 4 ออกผลแล้ว', $message);
        $this->assertStringNotContainsString('รอบที่', $message);
    }
}
