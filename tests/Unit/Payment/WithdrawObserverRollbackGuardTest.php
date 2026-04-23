<?php

namespace Tests\Unit\Payment;

use PHPUnit\Framework\TestCase;

class WithdrawObserverRollbackGuardTest extends TestCase
{
    /**
     * @return array<int, array{0: string}>
     */
    public static function observerFilesProvider(): array
    {
        return [
            [dirname(__DIR__, 3).'/packages/Gametech/Payment/src/Observers/WithdrawObserver.php'],
            [dirname(__DIR__, 3).'/packages/Gametech/Payment/src/Observers/WithdrawFreeObserver.php'],
            [dirname(__DIR__, 3).'/packages/Gametech/Payment/src/Observers/WithdrawSeamlessObserver.php'],
            [dirname(__DIR__, 3).'/packages/Gametech/Payment/src/Observers/WithdrawSeamlessFreeObserver.php'],
        ];
    }

    /**
     * @dataProvider observerFilesProvider
     */
    public function test_rollback_event_requires_transition_to_rejected_status(string $filePath): void
    {
        $content = file_get_contents($filePath);

        $this->assertNotFalse($content);
        $this->assertStringContainsString('in_array($oldStatus, [0, 1], true) && $newStatus === 2', $content);
        $this->assertStringNotContainsString('($oldEnable === \'Y\' && $newEnable !== \'Y\')', $content);
    }
}
