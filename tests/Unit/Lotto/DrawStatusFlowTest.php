<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Support\DrawStatusFlow;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DrawStatusFlowTest extends TestCase
{
    public function test_transition_steps_for_valid_paths(): void
    {
        $this->assertSame([], DrawStatusFlow::transitionSteps('draft', 'draft'));
        $this->assertSame(['open'], DrawStatusFlow::transitionSteps('draft', 'open'));
        $this->assertSame(['open', 'close'], DrawStatusFlow::transitionSteps('draft', 'closed'));
        $this->assertSame(['close'], DrawStatusFlow::transitionSteps('open', 'closed'));
        $this->assertSame(['open'], DrawStatusFlow::transitionSteps('closed', 'open'));
    }

    public function test_transition_steps_rejects_invalid_paths(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ไม่สามารถเปลี่ยนสถานะงวดตามลำดับนี้ได้');

        DrawStatusFlow::transitionSteps('open', 'draft');
    }

    public function test_transition_steps_rejects_resulted_target(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('การประกาศผลต้องใช้ปุ่มประกาศผลเท่านั้น');

        DrawStatusFlow::transitionSteps('closed', 'resulted');
    }
}

