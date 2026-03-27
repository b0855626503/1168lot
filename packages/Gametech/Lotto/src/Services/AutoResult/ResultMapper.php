<?php

namespace Gametech\Lotto\Services\AutoResult;

class ResultMapper
{
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
            ];
        }

        $firstPrizeMap = $mappingConfig['first_prize'] ?? 'first_prize';
        $last2DigitsMap = $mappingConfig['last_2_digits'] ?? 'last_2_digits';

        return [
            'first_prize' => $this->resolveMapping($parsed, $firstPrizeMap),
            'last_2_digits' => $this->resolveMapping($parsed, $last2DigitsMap),
        ];
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
        $transform = (string) ($mapRule['transform'] ?? '');

        if ($transform === '' || $value === null) {
            return $value;
        }

        $stringValue = (string) $value;
        if (preg_match('/^right:(\d+)$/', $transform, $m) === 1) {
            $len = (int) $m[1];
            return $len > 0 ? substr($stringValue, -$len) : $stringValue;
        }

        if (preg_match('/^left:(\d+)$/', $transform, $m) === 1) {
            $len = (int) $m[1];
            return $len > 0 ? substr($stringValue, 0, $len) : $stringValue;
        }

        return $value;
    }
}
