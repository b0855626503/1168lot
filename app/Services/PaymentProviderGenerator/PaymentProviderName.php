<?php

declare(strict_types=1);

namespace App\Services\PaymentProviderGenerator;

use InvalidArgumentException;

final class PaymentProviderName
{
    public function __construct(
        public readonly string $key,
        public readonly string $studly,
    ) {
    }

    public static function from(string $name): self
    {
        $key = strtolower(trim($name));

        if ($key === '') {
            throw new InvalidArgumentException('Provider name is required.');
        }

        if (!preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
            throw new InvalidArgumentException('Provider name must use lowercase snake_case.');
        }

        $studly = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key)));

        return new self($key, $studly);
    }
}
