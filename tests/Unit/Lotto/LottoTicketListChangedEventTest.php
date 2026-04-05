<?php

namespace Tests\Unit\Lotto;

use App\Events\LottoTicketListChanged;
use PHPUnit\Framework\TestCase;

class LottoTicketListChangedEventTest extends TestCase
{
    public function test_resulted_message_includes_market_name_and_draw_date(): void
    {
        $event = new LottoTicketListChanged('resulted', 12, 'หวยออมสิน', '2026-04-04');

        $this->assertSame('มีโพยหวยถูกตัดสินผลแล้ว: หวยออมสิน งวดวันที่ 2026-04-04', $event->message);
        $this->assertSame([
            'action' => 'resulted',
            'total' => 12,
            'message' => 'มีโพยหวยถูกตัดสินผลแล้ว: หวยออมสิน งวดวันที่ 2026-04-04',
            'market_name' => 'หวยออมสิน',
            'draw_date' => '2026-04-04',
            'owner_id' => null,
            'actor_id' => null,
            'amount' => null,
            'datatable_id' => 'lottoTicketsTable',
            'menu_badge_key' => 'lotto_tickets',
            'badge_id' => 'badge_lotto_tickets',
            'path' => '/lotto/tickets',
        ], $event->broadcastWith());
    }

    public function test_message_falls_back_to_base_text_when_context_missing(): void
    {
        $event = new LottoTicketListChanged('resulted', 3);

        $this->assertSame('มีโพยหวยถูกตัดสินผลแล้ว', $event->message);
        $this->assertNull($event->broadcastWith()['market_name']);
        $this->assertNull($event->broadcastWith()['draw_date']);
    }
}
