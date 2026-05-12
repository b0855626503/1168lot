<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Services\ArchiveNormalizerService;
use Tests\TestCase;

class ArchiveNormalizerServiceTest extends TestCase
{
    private ArchiveNormalizerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ArchiveNormalizerService;
    }

    public function test_normalize_preserves_leading_zero(): void
    {
        $draw = new NormalizerTestDraw(
            id: 1,
            marketCode: 'chaina-morning',
            drawDate: '2026-05-12',
            resultNumber: ['top_3' => '014', 'top_2' => '14', 'bottom_2' => '07'],
            betTypes: ['top_3', 'top_2', 'bottom_2'],
        );

        $rows = $this->service->normalizeDraw($draw);

        $bottom2 = collect($rows)->firstWhere('draw_key', 'two_down');
        $this->assertNotNull($bottom2);
        $this->assertSame(['07'], $bottom2['result_set']);
    }

    public function test_single_result_wraps_to_array(): void
    {
        $draw = new NormalizerTestDraw(
            id: 1,
            marketCode: 'dowjones-visa',
            drawDate: '2026-05-12',
            resultNumber: ['top_3' => '832'],
            betTypes: ['top_3'],
        );

        $rows = $this->service->normalizeDraw($draw);

        $this->assertCount(1, $rows);
        $this->assertIsArray($rows[0]['result_set']);
        $this->assertSame(['832'], $rows[0]['result_set']);
    }

    public function test_multiple_known_bet_types(): void
    {
        $draw = new NormalizerTestDraw(
            id: 1,
            marketCode: 'chaina-morning',
            drawDate: '2026-05-12',
            resultNumber: ['top_3' => '785', 'top_2' => '85', 'bottom_2' => '71'],
            betTypes: ['top_3', 'top_2', 'bottom_2', 'run_top', 'run_bottom', 'tod_3'],
        );

        $rows = $this->service->normalizeDraw($draw);

        $this->assertCount(3, $rows);
        $keys = collect($rows)->pluck('draw_key')->sort()->values()->all();
        $this->assertSame(['three_up', 'two_down', 'two_up'], $keys);
    }

    public function test_unknown_bet_type_skipped(): void
    {
        $draw = new NormalizerTestDraw(
            id: 1,
            marketCode: 'chaina-morning',
            drawDate: '2026-05-12',
            resultNumber: ['top_3' => '785'],
            betTypes: ['top_3', 'unknown_future_type'],
        );

        $rows = $this->service->normalizeDraw($draw);

        $this->assertCount(1, $rows);
        $this->assertSame('three_up', $rows[0]['draw_key']);
    }

    public function test_empty_result_number_returns_empty(): void
    {
        $draw = new NormalizerTestDraw(
            id: 1,
            marketCode: 'chaina-morning',
            drawDate: '2026-05-12',
            resultNumber: null,
            betTypes: ['top_3'],
        );

        $rows = $this->service->normalizeDraw($draw);

        $this->assertEmpty($rows);
    }

    public function test_missing_result_key_skipped(): void
    {
        $draw = new NormalizerTestDraw(
            id: 1,
            marketCode: 'chaina-morning',
            drawDate: '2026-05-12',
            resultNumber: ['top_3' => '785'],
            betTypes: ['top_3', 'bottom_2'],
        );

        $rows = $this->service->normalizeDraw($draw);

        $this->assertCount(1, $rows);
        $this->assertSame('three_up', $rows[0]['draw_key']);
    }

    public function test_result_hash_included_in_output(): void
    {
        $draw = new NormalizerTestDraw(
            id: 1,
            marketCode: 'chaina-morning',
            drawDate: '2026-05-12',
            resultNumber: ['top_3' => '785'],
            betTypes: ['top_3'],
        );

        $rows = $this->service->normalizeDraw($draw);

        $this->assertArrayHasKey('result_hash', $rows[0]);
        $this->assertNotEmpty($rows[0]['result_hash']);
    }

    public function test_market_code_matches_draw_market(): void
    {
        $draw = new NormalizerTestDraw(
            id: 1,
            marketCode: 'chaina-afternoon',
            drawDate: '2026-05-12',
            resultNumber: ['top_3' => '832'],
            betTypes: ['top_3'],
        );

        $rows = $this->service->normalizeDraw($draw);

        $this->assertSame('chaina-afternoon', $rows[0]['market_code']);
    }
}
