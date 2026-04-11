<?php

namespace Tests\Unit\Lotto;

use PHPUnit\Framework\TestCase;

class LottoDrawObserverRegistrationTest extends TestCase
{
    private string $rootPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rootPath = dirname(__DIR__, 3);
    }

    public function test_realtime_and_auto_result_observers_are_registered_only_on_lotto_draw_model(): void
    {
        $content = file_get_contents($this->rootPath.'/packages/Gametech/Lotto/src/Providers/LottoServiceProvider.php');

        $this->assertNotFalse($content);
        $this->assertStringContainsString('LottoDraw::observe(LottoDrawRealtimeObserver::class);', $content);
        $this->assertStringContainsString('LottoDraw::observe(LottoDashboardSummaryObserver::class);', $content);
        $this->assertStringContainsString('LottoDraw::observe(LottoDrawAutoResultObserver::class);', $content);
        $this->assertStringNotContainsString('LottoDrawProxy::observe(LottoDrawRealtimeObserver::class);', $content);
        $this->assertStringNotContainsString('LottoDrawProxy::observe(LottoDashboardSummaryObserver::class);', $content);
        $this->assertStringNotContainsString('LottoDrawProxy::observe(LottoDrawAutoResultObserver::class);', $content);
    }
}
