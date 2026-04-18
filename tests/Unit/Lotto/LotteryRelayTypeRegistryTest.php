<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Services\Relay\LotteryRelayTypeRegistry;
use Tests\TestCase;

class LotteryRelayTypeRegistryTest extends TestCase
{
    public function test_resolves_market_code_to_canonical_type(): void
    {
        $registry = new LotteryRelayTypeRegistry;

        $this->assertSame('dji', $registry->canonicalTypeForMarketCode('downjone-stock'));
        $this->assertSame('dowjones-midnight', $registry->canonicalTypeForMarketCode('downjone-midnight'));
        $this->assertSame('dowjones-vip', $registry->canonicalTypeForMarketCode('downjone-vip'));
        $this->assertNull($registry->canonicalTypeForMarketCode('unknown-market'));
    }

    public function test_resolves_canonical_type_back_to_market_codes(): void
    {
        $registry = new LotteryRelayTypeRegistry;

        $this->assertSame(['downjone-stock'], $registry->marketCodesForCanonicalType('dji'));
        $this->assertContains('downjone-vip', $registry->marketCodesForCanonicalType('dowjones-vip'));
        $this->assertNotContains('downjone-vip', $registry->marketCodesForCanonicalType('mlnhngo'));
    }
}
