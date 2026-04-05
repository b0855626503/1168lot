<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\DataTables\LottoTicketDataTable;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LottoTicketItem;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Transformers\LottoTicketTransformer;
use Gametech\Member\Models\Member;
use ReflectionMethod;
use Tests\TestCase;

class LottoTicketTablePresentationTest extends TestCase
{
    public function test_transformer_splits_draw_market_and_package_columns(): void
    {
        $member = new Member([
            'code' => 52,
            'user_name' => '0855626503',
        ]);

        $market = new LotteryMarket([
            'name' => 'หวยมาเลเซีย',
            'logo' => 'https://example.com/malaysia.png',
        ]);

        $draw = new LottoDraw([
            'draw_date' => '2026-04-05',
        ]);
        $draw->setRelation('market', $market);

        $ticket = new LottoTicket([
            'id' => 132,
            'member_id' => 52,
            'total_bet_amount' => 200,
            'total_discount_amount' => 0,
            'total_net_amount' => 200,
            'status' => 'active',
        ]);
        $ticket->setRelation('member', $member);
        $ticket->setRelation('draw', $draw);
        $ticket->setRelation('items', collect([
            new LottoTicketItem(['package_name_at_time' => 'แพกเกจ VIP']),
            new LottoTicketItem(['package_name_at_time' => 'แพกเกจ VIP']),
        ]));

        $payload = (new LottoTicketTransformer())->transform($ticket);

        $this->assertSame('05/04/2026', $payload['draw_date']);
        $this->assertStringContainsString('หวยมาเลเซีย', $payload['market']);
        $this->assertStringContainsString('https://example.com/malaysia.png', $payload['market']);
        $this->assertSame('แพกเกจ VIP', $payload['package_name']);
        $this->assertStringContainsString('ยกเลิกโพย', $payload['action']);
        $this->assertArrayNotHasKey('draw', $payload);
        $this->assertArrayNotHasKey('total_win_amount', $payload);
    }

    public function test_datatable_columns_match_ticket_table_requirement(): void
    {
        $dataTable = app(LottoTicketDataTable::class);
        $method = new ReflectionMethod($dataTable, 'getColumns');
        $method->setAccessible(true);

        $columns = $method->invoke($dataTable);
        $titles = array_column($columns, 'title');
        $dataKeys = array_column($columns, 'data');

        $this->assertSame(
            ['#', 'สมาชิก', 'งวดหวย', 'รายการหวย', 'แพกเกจ', 'ยอดแทง', 'ส่วนลด', 'สุทธิ', 'สถานะ', 'จัดการ'],
            $titles
        );
        $this->assertContains('draw_date', $dataKeys);
        $this->assertContains('market', $dataKeys);
        $this->assertContains('package_name', $dataKeys);
        $this->assertNotContains('total_win_amount', $dataKeys);
    }
}
