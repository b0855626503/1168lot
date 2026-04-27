<?php

declare(strict_types=1);

namespace App\Services\PaymentProviderGeneratorV3;

use InvalidArgumentException;

final class PaymentProviderName
{
    public function __construct(
        public readonly string $key,
        public readonly string $studly,
        public readonly string $camel,
        public readonly string $upperSnake,
    ) {
    }

    public static function from(string $name): self
    {
        $key = strtolower(trim($name));
        $key = str_replace('-', '_', $key);

        if ($key === '') {
            throw new InvalidArgumentException('Provider name is required.');
        }

        if (!preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
            throw new InvalidArgumentException('Provider name must be lowercase snake_case, e.g. boat_pay.');
        }

        $studly = str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
        $camel = lcfirst($studly);
        $upperSnake = strtoupper($key);

        return new self($key, $studly, $camel, $upperSnake);
    }
}
