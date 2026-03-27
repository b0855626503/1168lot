<?php

namespace Gametech\Lotto\Services\AutoResultV2\ConfigData;

use InvalidArgumentException;

final class ReadinessConfigData
{
    public const SHADOW_COMPARE_MATCH = 'MATCH';
    public const SHADOW_COMPARE_MISMATCH = 'MISMATCH';
    public const SHADOW_COMPARE_ERROR = 'ERROR';
    public const SHADOW_COMPARE_SKIPPED = 'SKIPPED';

    private bool $enabled;
    private array $minimumRequiredKeys;
    private array $requiredReferences;
    private string $shadowCompareStatus;
    private array $meta;

    private function __construct(bool $enabled, array $minimumRequiredKeys, array $requiredReferences, string $shadowCompareStatus, array $meta)
    {
        $this->enabled = $enabled;
        $this->minimumRequiredKeys = $minimumRequiredKeys;
        $this->requiredReferences = $requiredReferences;
        $this->shadowCompareStatus = $shadowCompareStatus;
        $this->meta = $meta;
    }

    public static function fromArray(array $data): self
    {
        $enabled = self::boolean(self::read($data, ['enabled'], true));
        $minimumRequiredKeys = self::arrayOfStrings(self::read($data, ['minimum_required_keys', 'required_keys'], []), 'minimum_required_keys');
        $requiredReferences = self::arrayOfStrings(self::read($data, ['required_references', 'references'], []), 'required_references');
        $shadowCompareStatus = self::normalizeShadowCompareStatus((string) self::read($data, ['shadow_compare_status'], self::SHADOW_COMPARE_SKIPPED));
        $meta = self::arrayOrEmpty(self::read($data, ['meta'], []), 'meta');

        return new self($enabled, $minimumRequiredKeys, $requiredReferences, $shadowCompareStatus, $meta);
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'minimum_required_keys' => $this->minimumRequiredKeys,
            'required_references' => $this->requiredReferences,
            'shadow_compare_status' => $this->shadowCompareStatus,
            'meta' => $this->meta,
        ];
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return array<int, string>
     */
    public function minimumRequiredKeys(): array
    {
        return $this->minimumRequiredKeys;
    }

    /**
     * @return array<int, string>
     */
    public function requiredReferences(): array
    {
        return $this->requiredReferences;
    }

    public function shadowCompareStatus(): string
    {
        return $this->shadowCompareStatus;
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * @return array<int, string>
     */
    public function fieldNames(): array
    {
        return $this->minimumRequiredKeys;
    }

    /**
     * @return array<int, string>
     */
    public static function allowedShadowCompareStatuses(): array
    {
        return [
            self::SHADOW_COMPARE_MATCH,
            self::SHADOW_COMPARE_MISMATCH,
            self::SHADOW_COMPARE_ERROR,
            self::SHADOW_COMPARE_SKIPPED,
        ];
    }

    private static function normalizeShadowCompareStatus(string $value): string
    {
        $normalized = strtoupper(trim($value));

        if (! in_array($normalized, self::allowedShadowCompareStatuses(), true)) {
            throw new InvalidArgumentException('shadow_compare_status ไม่ถูกต้อง: ' . $value);
        }

        return $normalized;
    }

    private static function boolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($filtered === null) {
            throw new InvalidArgumentException('enabled ต้องเป็น boolean');
        }

        return $filtered;
    }

    /**
     * @return array<int, string>
     */
    private static function arrayOfStrings(mixed $value, string $field): array
    {
        if ($value === null) {
            return [];
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException($field . ' ต้องเป็น array');
        }

        $result = [];
        foreach ($value as $item) {
            $item = trim((string) $item);
            if ($item === '') {
                continue;
            }

            $result[] = $item;
        }

        return array_values(array_unique($result));
    }

    /**
     * @return array<string, mixed>
     */
    private static function arrayOrEmpty(mixed $value, string $field): array
    {
        if ($value === null) {
            return [];
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException($field . ' ต้องเป็น array');
        }

        return $value;
    }

    private static function read(array $data, array $keys, mixed $default): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }

        return $default;
    }
}

