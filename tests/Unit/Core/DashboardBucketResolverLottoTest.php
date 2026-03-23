<?php

namespace Tests\Unit\Core;

use App\Services\Dashboard\DashboardBucketResolver;
use App\Services\Dashboard\DashboardWebCodeResolver;
use PHPUnit\Framework\TestCase;

class DashboardBucketResolverLottoTest extends TestCase
{
    public function test_resolve_lotto_maps_dates_to_sections(): void
    {
        $resolver = new DashboardBucketResolver(new DashboardWebCodeResolver());

        $buckets = $resolver->resolve('lotto', [
            'web_code' => 'lotto-web',
            'cash_dates' => ['2026-03-20 10:11:12'],
            'product_dates' => ['2026-03-21 08:00:00'],
            'risk_dates' => ['2026-03-21 09:00:00'],
        ]);

        $this->assertCount(2, $buckets);

        $byDate = [];
        foreach ($buckets as $bucket) {
            $byDate[$bucket['summary_date']] = $bucket;
        }

        $this->assertSame(['lotto_cash', 'net'], $byDate['2026-03-20']['updated_sections']);
        $this->assertSame(
            ['lotto_product', 'lotto_risk'],
            $byDate['2026-03-21']['updated_sections']
        );
    }

    public function test_resolve_lotto_with_override_sections_applies_to_all_dates(): void
    {
        $resolver = new DashboardBucketResolver(new DashboardWebCodeResolver());

        $buckets = $resolver->resolve('lotto', [
            'web_code' => 'lotto-web',
            'cash_dates' => ['2026-03-20 10:11:12'],
            'product_dates' => ['2026-03-21 08:00:00'],
        ], ['lotto_cash', 'lotto_product']);

        $this->assertCount(2, $buckets);
        foreach ($buckets as $bucket) {
            $this->assertSame(['lotto_cash', 'lotto_product'], $bucket['updated_sections']);
            $this->assertSame('lotto-web', $bucket['web_code']);
        }
    }
}
