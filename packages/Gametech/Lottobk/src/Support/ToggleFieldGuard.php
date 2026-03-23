<?php

namespace Gametech\Lotto\Support;

use InvalidArgumentException;

final class ToggleFieldGuard
{
    /**
     * @param  array<int, string>  $allowedFields
     */
    public static function resolveField(?string $field, array $allowedFields, string $errorMessage = 'method ไม่ถูกต้อง'): string
    {
        $normalizedField = trim((string) $field);

        if ($normalizedField === '' || ! in_array($normalizedField, $allowedFields, true)) {
            throw new InvalidArgumentException($errorMessage);
        }

        return $normalizedField;
    }

    public static function resolveBoolean(mixed $value, string $errorMessage = 'status ไม่ถูกต้อง'): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($normalized === null) {
            throw new InvalidArgumentException($errorMessage);
        }

        return $normalized;
    }
}

