<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Transformers\LottoTicketsCancelReportTransformer;
use stdClass;
use Tests\TestCase;

class LottoTicketsCancelReasonFallbackTest extends TestCase
{
    public function test_transformer_reads_reason_from_cancel_transaction_meta_when_ticket_column_is_empty(): void
    {
        $row = new stdClass();
        $row->id = 202;
        $row->member_id = 52;
        $row->member_user_name = 'member52';
        $row->member_name = 'Member 52';
        $row->market_name = 'หวยรัฐบาล';
        $row->market_logo = null;
        $row->market_icon = null;
        $row->draw_date = '2026-04-05';
        $row->total_bet_amount = 23.24;
        $row->total_discount_amount = 0;
        $row->total_net_amount = 23.24;
        $row->total_win_amount = 0;
        $row->status = 'cancelled';
        $row->reason = null;
        $row->cancelled_at = '2026-04-05 16:42:12';
        $row->created_at = '2026-04-05 16:40:00';
        $row->cancel_tx_meta = json_encode(['reason' => 'เทส'], JSON_UNESCAPED_UNICODE);
        $row->cancel_tx_created_by_type = 'admin';
        $row->cancel_tx_admin_user_name = 'staff01';
        $row->cancel_tx_member_user_name = null;
        $row->cancel_tx_member_name = null;
        $row->cancel_admin_user_name = null;
        $row->cancel_member_user_name = null;
        $row->cancel_member_name = null;
        $row->items = collect();

        $payload = (new LottoTicketsCancelReportTransformer())->transform($row);

        $this->assertSame('เทส', $payload['reason']);
    }
}
