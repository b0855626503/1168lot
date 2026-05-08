<?php

namespace Tests\Unit\Lotto;

use App\Events\LottoTicketListChanged;
use Gametech\Lotto\Models\LotteryMarket;
use PHPUnit\Framework\TestCase;

class LottoTicketListChangedEventTest extends TestCase
{
    public function test_resulted_yeekee_message_includes_round_number(): void
    {
        $event = new LottoTicketListChanged('resulted', 12, 'หวยยี่กี่รวม', '2026-05-08', null, null, null, LotteryMarket::RESULT_MODE_YEEKEE, 18);

        $this->assertStringContainsString('รอบ 18', $event->message);
        $this->assertSame([
            'action' => 'resulted',
            'total' => 12,
            'message' => 'มีโพยหวยถูกตัดสินผลแล้ว: หวยยี่กี่รวม รอบ 18 งวดวันที่ 2026-05-08',
            'market_name' => 'หวยยี่กี่รวม',
            'draw_date' => '2026-05-08',
            'owner_id' => null,
            'actor_id' => null,
            'amount' => null,
            'datatable_id' => 'lottoTicketsTable',
            'menu_badge_key' => 'lotto_tickets',
            'badge_id' => 'badge_lotto_tickets',
            'path' => '/lotto/tickets',
        ], $event->broadcastWith());
    }

    public function test_resulted_non_yeekee_message_does_not_include_round_number(): void
    {
        $event = new LottoTicketListChanged('resulted', 12, 'หวยออมสิน', '2026-04-04', null, null, null, 'normal', 18);

        $this->assertStringNotContainsString('รอบ', $event->message);
        $this->assertSame('มีโพยหวยถูกตัดสินผลแล้ว: หวยออมสิน งวดวันที่ 2026-04-04', $event->message);
    }

    public function test_message_falls_back_to_base_text_when_context_missing(): void
    {
        $event = new LottoTicketListChanged('resulted', 3);

        $this->assertSame('มีโพยหวยถูกตัดสินผลแล้ว', $event->message);
        $this->assertNull($event->broadcastWith()['market_name']);
        $this->assertNull($event->broadcastWith()['draw_date']);
    }
}
