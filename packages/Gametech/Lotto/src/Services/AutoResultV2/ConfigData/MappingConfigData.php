<?php

namespace Gametech\Lotto\Services\AutoResultV2\ConfigData;

use InvalidArgumentException;

final class MappingConfigData
{
    private array $fields;
    private array $references;
    private array $meta;

    private function __construct(array $fields, array $references, array $meta)
    {
        $this->fields = $fields;
        $this->references = $references;
        $this->meta = $meta;
    }

    public static function fromArray(array $data): self
    {
        $fields = self::arrayOrEmpty(self::read($data, ['fields', 'field_map', 'mappings'], []), 'fields');
        $meta = self::arrayOrEmpty(self::read($data, ['meta'], []), 'meta');
        $references = self::arrayOrEmpty(self::read($data, ['references'], []), 'references');

        if ($fields === []) {
            throw new InvalidArgumentException('fields จำเป็นสำหรับ mapping config');
        }

        $normalizedFields = [];
        $derivedReferences = [];

        foreach ($fields as $targetField => $rule) {
            if (! is_string($targetField) || trim($targetField) === '') {
                throw new InvalidArgumentException('mapping field key ไม่ถูกต้อง');
            }

            $normalizedFields[$targetField] = self::normalizeRule($rule, $targetField);
            $from = $normalizedFields[$targetField]['from'] ?? null;
            if (is_string($from) && trim($from) !== '') {
                $derivedReferences[] = trim($from);
            }
            $fromFields = $normalizedFields[$targetField]['from_fields'] ?? [];
            if (is_array($fromFields)) {
                foreach ($fromFields as $fromField) {
                    if (is_string($fromField) && trim($fromField) !== '') {
                        $derivedReferences[] = trim($fromField);
                    }
                }
            }
        }

        $references = array_values(array_unique(array_merge($references, $derivedReferences)));

        return new self($normalizedFields, $references, $meta);
    }

    public function toArray(): array
    {
        return [
            'fields' => $this->fields,
            'references' => $this->references,
            'meta' => $this->meta,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    /**
     * @return array<int, string>
     */
    public function references(): array
    {
        return $this->references;
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
        return array_values(array_map('strval', array_keys($this->fields)));
    }

    /**
     * @param mixed $rule
     * @return array<string, mixed>
     */
    private static function normalizeRule(mixed $rule, string $targetField): array
    {
        if (is_string($rule)) {
            $rule = ['from' => trim($rule)];
        }

        if (! is_array($rule)) {
            throw new InvalidArgumentException('mapping rule ไม่ถูกต้องสำหรับ ' . $targetField);
        }

        $from = self::stringOrNull($rule['from'] ?? null);
        $fromFields = self::arrayOrEmpty($rule['from_fields'] ?? [], 'from_fields');
        $transforms = $rule['transforms'] ?? ($rule['transform'] ?? []);
        if (is_string($transforms) && trim($transforms) !== '') {
            $transforms = [trim($transforms)];
        }
        $transforms = self::arrayOrEmpty($transforms, 'transforms');
        $default = $rule['default'] ?? null;

        $hasComposeOp = array_key_exists('template', $rule)
            || array_key_exists('join', $rule)
            || array_key_exists('coalesce', $rule)
            || array_key_exists('map_value', $rule)
            || array_key_exists('case_when', $rule)
            || $fromFields !== [];

        if (($from === null || $from === '') && ! $hasComposeOp) {
            throw new InvalidArgumentException('mapping rule ของ ' . $targetField . ' ต้องมี from');
        }

        return [
            'from' => $from,
            'from_fields' => $fromFields,
            'transforms' => array_values($transforms),
            'default' => $default,
            'join' => $rule['join'] ?? null,
            'template' => $rule['template'] ?? null,
            'coalesce' => is_array($rule['coalesce'] ?? null) ? $rule['coalesce'] : null,
            'map_value' => is_array($rule['map_value'] ?? null) ? $rule['map_value'] : null,
            'case_when' => is_array($rule['case_when'] ?? null) ? $rule['case_when'] : null,
            'else' => $rule['else'] ?? null,
        ];
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
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
