<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Enums\BetType;
use PHPUnit\Framework\TestCase;

class BetTypeTest extends TestCase
{
    public function test_all_returns_six_bet_types(): void
    {
        $all = BetType::all();

        $this->assertCount(6, $all);
    }

    public function test_all_contains_every_expected_constant(): void
    {
        $all = BetType::all();

        $this->assertContains(BetType::TOP_3, $all);
        $this->assertContains(BetType::TOD_3, $all);
        $this->assertContains(BetType::TOP_2, $all);
        $this->assertContains(BetType::BOTTOM_2, $all);
        $this->assertContains(BetType::RUN_TOP, $all);
        $this->assertContains(BetType::RUN_BOTTOM, $all);
    }

    public function test_label_returns_thai_for_each_type(): void
    {
        $this->assertSame('3 ตัวบน', BetType::label(BetType::TOP_3));
        $this->assertSame('3 ตัวโต๊ด', BetType::label(BetType::TOD_3));
        $this->assertSame('2 ตัวบน', BetType::label(BetType::TOP_2));
        $this->assertSame('2 ตัวล่าง', BetType::label(BetType::BOTTOM_2));
        $this->assertSame('วิ่งบน', BetType::label(BetType::RUN_TOP));
        $this->assertSame('วิ่งล่าง', BetType::label(BetType::RUN_BOTTOM));
    }

    public function test_label_returns_unknown_for_invalid_type(): void
    {
        $this->assertSame('Unknown', BetType::label('invalid_type'));
        $this->assertSame('Unknown', BetType::label(''));
        $this->assertSame('Unknown', BetType::label('top3'));
    }

    public function test_all_types_have_non_empty_non_unknown_labels(): void
    {
        foreach (BetType::all() as $type) {
            $label = BetType::label($type);

            $this->assertNotEmpty($label, "Type '{$type}' has no label");
            $this->assertNotSame('Unknown', $label, "Type '{$type}' returned fallback Unknown label");
        }
    }

    public function test_all_type_constants_are_distinct(): void
    {
        $all = BetType::all();
        $unique = array_unique($all);

        $this->assertCount(count($all), $unique, 'BetType constants must all be distinct strings');
    }

    public function test_all_type_constants_are_snake_case_strings(): void
    {
        foreach (BetType::all() as $type) {
            $this->assertMatchesRegularExpression(
                '/^[a-z][a-z0-9_]*$/',
                $type,
                "BetType '{$type}' must be a snake_case string"
            );
        }
    }
}

