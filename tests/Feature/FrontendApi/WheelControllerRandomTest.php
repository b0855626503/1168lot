<?php

namespace Tests\Feature\FrontendApi;

use Gametech\FrontendApi\Http\Controllers\Api\V1\WheelController;
use ReflectionMethod;
use Tests\TestCase;

class WheelControllerRandomTest extends TestCase
{
    public function test_pick_weighted_returns_null_for_empty_weights(): void
    {
        $controller = app(WheelController::class);
        $method = new ReflectionMethod(WheelController::class, 'pickWeighted');
        $method->setAccessible(true);

        $result = $method->invoke($controller, []);

        $this->assertNull($result);
    }

    public function test_pick_weighted_ignores_non_positive_weight_values(): void
    {
        $controller = app(WheelController::class);
        $method = new ReflectionMethod(WheelController::class, 'pickWeighted');
        $method->setAccessible(true);

        for ($i = 0; $i < 20; $i++) {
            $result = $method->invoke($controller, [
                'zero' => 0,
                'negative' => -5,
                'winner' => 0.1,
            ]);

            $this->assertSame('winner', $result);
        }
    }

    public function test_pick_weighted_fallback_with_no_positive_weights_returns_existing_key(): void
    {
        $controller = app(WheelController::class);
        $method = new ReflectionMethod(WheelController::class, 'pickWeighted');
        $method->setAccessible(true);

        $allowed = ['alpha', 'beta', 'gamma'];

        for ($i = 0; $i < 50; $i++) {
            $result = $method->invoke($controller, [
                'alpha' => 0,
                'beta' => 0,
                'gamma' => -1,
            ]);

            $this->assertContains($result, $allowed);
        }
    }
}
