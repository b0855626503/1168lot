<?php

namespace Tests\Unit\Lotto\AutoResultV2;

use Gametech\Lotto\Services\AutoResultV2\Compose\ResultComposer;
use PHPUnit\Framework\TestCase;

class ResultComposerNoResultTest extends TestCase
{
    public function test_compose_keeps_no_result_marker_for_first_prize(): void
    {
        $composer = new ResultComposer();

        $result = $composer->compose([
            'fields' => [
                'first_prize' => 'งดออกผล',
                'last_2_digits' => '',
                'draw_date' => '2026-04-03',
            ],
        ]);

        $canonical = (array) ($result['canonical_outcome'] ?? []);
        $this->assertSame('งดออกผล', $canonical['first_prize'] ?? null);
    }
}

