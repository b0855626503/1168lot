<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Exceptions\ResultValidationException;
use Gametech\Lotto\Services\AutoResult\ResultMapper;
use Gametech\Lotto\Services\AutoResult\ResultTransformChain;
use PHPUnit\Framework\TestCase;

class AutoResultMapperTest extends TestCase
{
    public function test_map_supports_transform_chain_and_legacy_transform(): void
    {
        $mapper = new ResultMapper(new ResultTransformChain());

        $mapped = $mapper->map([
            'draw_date_raw' => ' 27/03/2026 ',
            'first_prize_raw' => 'รางวัลที่ 1 : 987654',
            'last2_raw' => 'ท้ายสองตัว 154',
        ], [
            'draw_date' => [
                'from' => 'draw_date_raw',
                'transforms' => ['trim', ['op' => 'date', 'from' => 'd/m/Y', 'to' => 'Y-m-d']],
            ],
            'first_prize' => [
                'from' => 'first_prize_raw',
                'transforms' => ['digits_only'],
            ],
            'last_2_digits' => [
                'from' => 'last2_raw',
                'transform' => 'right:2',
            ],
        ]);

        $this->assertSame('2026-03-27', $mapped['draw_date']);
        $this->assertSame('1987654', $mapped['first_prize']);
        $this->assertSame('54', $mapped['last_2_digits']);
    }

    public function test_map_throws_on_invalid_transform(): void
    {
        $mapper = new ResultMapper(new ResultTransformChain());

        $this->expectException(ResultValidationException::class);

        $mapper->map([
            'x' => '123',
        ], [
            'first_prize' => [
                'from' => 'x',
                'transforms' => ['unknown_op'],
            ],
        ]);
    }
}
