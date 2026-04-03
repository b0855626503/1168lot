<?php

namespace Tests\Unit\Lotto\AutoResultV2;

use Gametech\Lotto\Services\AutoResultV2\ConfigData\SelectionConfigData;
use Gametech\Lotto\Services\AutoResultV2\Executors\SelectionExecutor;
use Tests\TestCase;

class SelectionExecutorNoResultTest extends TestCase
{
    public function test_selection_allows_candidate_with_no_result_marker_even_when_required_missing(): void
    {
        $executor = new SelectionExecutor();
        $config = SelectionConfigData::fromArray([
            'required_fields' => ['first_prize', 'last_2_digits'],
            'date_field' => 'draw_date',
        ]);

        $result = $executor->execute([
            'candidates' => [[
                'index' => 0,
                'fields' => [
                    'draw_date' => '2026-04-03',
                    'notice' => 'งดออกผล',
                ],
            ]],
        ], $config, [
            'expected_draw_date' => '2026-04-03',
        ]);

        $this->assertSame('selected', $result['decision']);
        $this->assertNotNull($result['selected_candidate'] ?? null);
    }
}
