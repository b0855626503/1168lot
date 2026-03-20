<?php

namespace Tests\Unit\Lotto;

use Gametech\Lotto\Support\ToggleFieldGuard;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ToggleFieldGuardTest extends TestCase
{
    public function test_resolve_field_accepts_only_allowlisted_field(): void
    {
        $this->assertSame(
            'is_enabled',
            ToggleFieldGuard::resolveField('is_enabled', ['is_enabled'])
        );
    }

    public function test_resolve_field_rejects_unknown_field(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('method ไม่ถูกต้อง');

        ToggleFieldGuard::resolveField('created_by', ['is_enabled']);
    }

    public function test_resolve_boolean_supports_common_truthy_and_falsy_values(): void
    {
        $this->assertTrue(ToggleFieldGuard::resolveBoolean(true));
        $this->assertTrue(ToggleFieldGuard::resolveBoolean('1'));
        $this->assertTrue(ToggleFieldGuard::resolveBoolean('true'));
        $this->assertFalse(ToggleFieldGuard::resolveBoolean(false));
        $this->assertFalse(ToggleFieldGuard::resolveBoolean('0'));
        $this->assertFalse(ToggleFieldGuard::resolveBoolean('false'));
    }

    public function test_resolve_boolean_rejects_invalid_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('status ไม่ถูกต้อง');

        ToggleFieldGuard::resolveBoolean('not-a-boolean');
    }
}

