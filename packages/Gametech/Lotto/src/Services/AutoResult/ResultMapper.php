<?php

namespace Gametech\Lotto\Services\AutoResult;

class ResultMapper
{
    public function __construct(
        private ResultTransformChain $transformChain
    ) {
    }

    /**
     * @param array<string,mixed> $parsed
     * @param array<string,mixed> $mappingConfig
     * @return array<string,mixed>
     */
    public function map(array $parsed, array $mappingConfig): array
    {
        if ($mappingConfig === []) {
            return [
                'first_prize' => $parsed['first_prize'] ?? null,
                'last_2_digits' => $parsed['last_2_digits'] ?? ($parsed['bottom_2'] ?? null),
                'draw_date' => $parsed['draw_date'] ?? null,
            ];
        }

        $mapped = [];
        foreach ($mappingConfig as $field => $mapRule) {
            if (! is_string($field) || trim($field) === '') {
                continue;
            }

            $mapped[$field] = $this->resolveMapping($parsed, $mapRule);
        }

        if (! array_key_exists('first_prize', $mapped)) {
            $mapped['first_prize'] = $parsed['first_prize'] ?? null;
        }

        if (! array_key_exists('last_2_digits', $mapped)) {
            $mapped['last_2_digits'] = $parsed['last_2_digits'] ?? ($parsed['bottom_2'] ?? null);
        }

        return $mapped;
    }

    /**
     * @param array<string,mixed> $data
     * @return mixed
     */
    private function readPath(array $data, string $path)
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }

        $segments = explode('.', $path);
        $cursor = $data;

        foreach ($segments as $segment) {
            if (! is_array($cursor) || ! array_key_exists($segment, $cursor)) {
                return null;
            }

            $cursor = $cursor[$segment];
        }

        return $cursor;
    }

    /**
     * @param array<string,mixed> $data
     * @param mixed $mapRule
     * @return mixed
     */
    private function resolveMapping(array $data, $mapRule)
    {
        if (is_string($mapRule)) {
            return $this->readPath($data, $mapRule);
        }

        if (! is_array($mapRule)) {
            return null;
        }

        $path = (string) ($mapRule['from'] ?? '');
        $value = $this->readPath($data, $path);

        $transforms = $this->resolveTransforms($mapRule);
        if ($transforms === [] || $value === null) {
            return $value;
        }

        return $this->transformChain->apply($value, $transforms);
    }

    /**
     * @param array<string,mixed> $mapRule
     * @return array<int,mixed>
     */
    private function resolveTransforms(array $mapRule): array
    {
        $transforms = $mapRule['transforms'] ?? null;
        if (is_array($transforms)) {
            return array_values($transforms);
        }

        $legacy = $mapRule['transform'] ?? null;
        if (is_string($legacy) && trim($legacy) !== '') {
            return [trim($legacy)];
        }

        return [];
    }
}
