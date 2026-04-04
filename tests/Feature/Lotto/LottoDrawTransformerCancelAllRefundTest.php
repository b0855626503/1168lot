<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Http\Controllers\Admin\LottoDrawController;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Transformers\LottoDrawTransformer;
use ReflectionMethod;
use Tests\TestCase;

class LottoDrawTransformerCancelAllRefundTest extends TestCase
{
    public function test_transform_hides_cancel_all_refund_button_after_manual_cancel_marker(): void
    {
        $draw = new LottoDraw();
        $draw->forceFill([
            'id' => 99,
            'status' => 'resulted',
            'draw_date' => '2026-04-04',
            'open_at' => '2026-04-04 09:00:00',
            'close_at' => '2026-04-04 15:00:00',
            'result_number' => [
                'no_result' => true,
                'status' => 'no_result',
                'manual_cancelled_all_tickets' => true,
            ],
        ]);
        $draw->setRelation('market', new LotteryMarket(['name' => 'หวยออมสิน']));

        $payload = (new LottoDrawTransformer())->transform($draw);

        $this->assertFalse((bool) str_contains($payload['action'], 'คืนเงิน'));
        $this->assertFalse((bool) str_contains($payload['action'], 'cancelAllTicketsAndRefund'));
    }

    public function test_transform_keeps_cancel_all_refund_button_for_no_result_before_refund(): void
    {
        $transformer = new LottoDrawTransformer();
        $method = new ReflectionMethod($transformer, 'canCancelAllRefundAction');
        $method->setAccessible(true);

        $this->assertTrue(
            $method->invoke($transformer, 'resulted', true, [])
        );
        $this->assertFalse(
            $method->invoke($transformer, 'resulted', true, ['manual_cancelled_all_tickets' => true])
        );
    }

    public function test_admin_cancel_all_refund_guard_rejects_repeated_refund_after_marker(): void
    {
        $draw = new LottoDraw();
        $draw->forceFill([
            'status' => 'resulted',
            'result_number' => [
                'no_result' => true,
                'status' => 'no_result',
                'manual_cancelled_all_tickets' => true,
            ],
        ]);

        $controller = app(LottoDrawController::class);
        $method = new ReflectionMethod($controller, 'canCancelAllRefundByDraw');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($controller, $draw));
    }
}
