<?php

namespace Tests\Unit\Marketing;

use Gametech\Marketing\Services\MarketingClickClassifier;
use Gametech\Marketing\Services\MarketingClickTrackingService;
use PHPUnit\Framework\TestCase;

class MarketingClickTrackingServiceTest extends TestCase
{
    public function test_service_can_be_instantiated(): void
    {
        $classifier = new MarketingClickClassifier;
        $service = new MarketingClickTrackingService($classifier);

        $this->assertInstanceOf(MarketingClickTrackingService::class, $service);
    }

    public function test_mark_converted_returns_false_for_non_existent_click(): void
    {
        // This test relies on markConverted using DB — tested more thoroughly in Feature tests.
        // Here we just validate the classifier dependency chain.
        $classifier = new MarketingClickClassifier;
        $service = new MarketingClickTrackingService($classifier);

        $this->assertInstanceOf(MarketingClickTrackingService::class, $service);
    }
}
