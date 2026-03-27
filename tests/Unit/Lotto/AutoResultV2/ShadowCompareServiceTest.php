<?php

namespace Tests\Unit\Lotto\AutoResultV2;

use Gametech\Lotto\Services\AutoResultV2\ShadowCompareService;
use PHPUnit\Framework\TestCase;

class ShadowCompareServiceTest extends TestCase
{
    public function test_compare_is_deterministic_and_match_status(): void
    {
        $service = new ShadowCompareService();

        $left = [
            'canonical_outcome' => [
                'draw_date' => '2026-03-26',
                'first_prize' => '12345',
            ],
        ];
        $right = [
            'canonical_outcome' => [
                'first_prize' => '12345',
                'draw_date' => '2026-03-26',
            ],
        ];

        $result = $service->compare($left, $right);

        $this->assertTrue($result['matched']);
        $this->assertSame('MATCH', $result['shadow_compare_status']);
        $this->assertSame([], $result['mismatches']);
    }
}
