<?php

namespace Tests\Feature\Lotto;

use Gametech\Lotto\Services\BetService;
use Tests\TestCase;

class BetServiceContainerBindingTest extends TestCase
{
    public function test_container_can_resolve_bet_service(): void
    {
        $service = $this->app->make(BetService::class);

        $this->assertInstanceOf(BetService::class, $service);
    }
}
