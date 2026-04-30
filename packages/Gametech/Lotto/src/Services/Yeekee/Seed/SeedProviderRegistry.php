<?php

namespace Gametech\Lotto\Services\Yeekee\Seed;

use InvalidArgumentException;

class SeedProviderRegistry
{
    /**
     * @return array<string,mixed>
     */
    public function resolve(string $providerKey): array
    {
        $key = trim($providerKey);
        $providers = (array) config('yeekee.external_seed.providers', []);
        if ($key === '' || ! array_key_exists($key, $providers)) {
            throw new InvalidArgumentException('ไม่รองรับ seed provider ที่ระบุ');
        }

        return (array) $providers[$key];
    }
}
