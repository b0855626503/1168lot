<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoResultCorrection;
use Gametech\Lotto\Models\LottoResultCorrectionItem;
use Gametech\Lotto\Models\LottoTicket;
use Tests\TestCase;

class LottoResultCorrectionModelRelationsTest extends TestCase
{
    public function test_result_correction_model_relations_are_defined(): void
    {
        $correction = new LottoResultCorrection;
        $item = new LottoResultCorrectionItem;

        $this->assertSame('draw_id', $correction->draw()->getForeignKeyName());
        $this->assertSame('correction_id', $correction->items()->getForeignKeyName());
        $this->assertSame('correction_id', $item->correction()->getForeignKeyName());
        $this->assertSame('draw_id', $item->draw()->getForeignKeyName());
        $this->assertSame('ticket_id', $item->ticket()->getForeignKeyName());
    }

    public function test_draw_and_ticket_have_result_correction_relations(): void
    {
        $draw = new LottoDraw;
        $ticket = new LottoTicket;

        $this->assertSame('draw_id', $draw->resultCorrections()->getForeignKeyName());
        $this->assertSame('ticket_id', $ticket->resultCorrectionItems()->getForeignKeyName());
    }
}
