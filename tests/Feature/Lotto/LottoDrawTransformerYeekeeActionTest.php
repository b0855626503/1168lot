<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Transformers\LottoDrawTransformer;
use Tests\TestCase;

class LottoDrawTransformerYeekeeActionTest extends TestCase
{
    public function test_closed_yeekee_draw_does_not_show_manual_result_action(): void
    {
        $market = new LotteryMarket;
        $market->id = 10;
        $market->name = 'Yeekee';
        $market->result_mode = 'yeekee';
        $market->exists = true;

        $draw = new LottoDraw;
        $draw->id = 100;
        $draw->status = 'closed';
        $draw->setRelation('market', $market);
        $draw->result_number = [];
        $draw->blocked_numbers_count = 0;
        $draw->tickets_count = 0;

        $payload = (new LottoDrawTransformer)->transform($draw);
        $actionHtml = (string) ($payload['action'] ?? '');

        $this->assertStringNotContainsString('ออกผล', $actionHtml);
    }

    public function test_yeekee_market_name_includes_round_number_when_available(): void
    {
        $market = new LotteryMarket;
        $market->id = 10;
        $market->name = 'ยี่กี่แสนรัก';
        $market->result_mode = 'yeekee';
        $market->exists = true;

        $draw = new LottoDraw;
        $draw->id = 100;
        $draw->status = 'open';
        $draw->yeekee_round_no = 21;
        $draw->setRelation('market', $market);
        $draw->result_number = [];
        $draw->blocked_numbers_count = 0;
        $draw->tickets_count = 0;

        $payload = (new LottoDrawTransformer)->transform($draw);

        $this->assertStringContainsString('ยี่กี่แสนรัก (21)', (string) $payload['market_name']);
    }
}
