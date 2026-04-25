<?php

namespace Tests\Unit\Admin;

use Gametech\Admin\Http\Controllers\FixController;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class FixControllerCashbackStartTest extends TestCase
{
    public function test_cashbackstart_uses_configured_mode_and_target(): void
    {
        config()->set('gametech.cashback.start.mode', 'daily');
        config()->set('gametech.cashback.start.target', 'cashback');

        Artisan::shouldReceive('call')
            ->once()
            ->with('cashback:start', [
                '--mode' => 'daily',
                '--target' => 'cashback',
            ]);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('queued');

        $controller = new class extends FixController
        {
            protected function getCoreConfig()
            {
                return ['seamless' => 'Y'];
            }
        };

        $response = $controller->cashbackstart();

        $payload = $response->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertSame('queued', $payload['data']);
        $this->assertSame('คำนวนและมอบ Cashback ให้ลูกค้า (mode: daily, target: cashback)', $payload['message']);
    }

    public function test_cashbackstart_falls_back_to_default_values_when_config_is_invalid(): void
    {
        config()->set('gametech.cashback.start.mode', 'broken');
        config()->set('gametech.cashback.start.target', 'broken');

        Artisan::shouldReceive('call')
            ->once()
            ->with('cashback:start', [
                '--mode' => 'range',
                '--target' => 'wallet',
            ]);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('queued');

        $controller = new class extends FixController
        {
            protected function getCoreConfig()
            {
                return ['seamless' => 'Y'];
            }
        };

        $response = $controller->cashbackstart();

        $payload = $response->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertSame('คำนวนและมอบ Cashback ให้ลูกค้า (mode: range, target: wallet)', $payload['message']);
    }
}
