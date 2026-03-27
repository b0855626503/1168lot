<?php

namespace Gametech\Lotto\Services\AutoResultV2\ConfigData;

use InvalidArgumentException;

final class ValidationConfigData
{
    private array $requiredFields;
    private array $fields;
    private array $expectedDrawDate;
    private array $references;
    private array $meta;

    private function __construct(array $requiredFields, array $fields, array $expectedDrawDate, array $references, array $meta)
    {
        $this->requiredFields = $requiredFields;
        $this->fields = $fields;
        $this->expectedDrawDate = $expectedDrawDate;
        $this->references = $references;
        $this->meta = $meta;
    }

    public static function fromArray(array $data): self
    {
        $requiredFields = self::arrayOfStrings(self::read($data, ['required_fields', 'required'], []), 'required_fields');
        $fields = self::arrayOrEmpty(self::read($data, ['fields'], []), 'fields');
        $expectedDrawDate = self::arrayOrEmpty(self::read($data, ['expected_draw_date'], []), 'expected_draw_date');
        $references = self::arrayOfStrings(self::read($data, ['references'], []), 'references');
        $meta = self::arrayOrEmpty(self::read($data, ['meta'], []), 'meta');

        return new self($requiredFields, $fields, $expectedDrawDate, $references, $meta);
    }

    public function toArray(): array
    {
        return [
            'required_fields' => $this->requiredFields,
            'fields' => $this->fields,
            'expected_draw_date' => $this->expectedDrawDate,
            'references' => $this->references,
            'meta' => $this->meta,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function requiredFields(): array
    {
        return $this->requiredFields;
    }

    /**
     * @return array<string, mixed>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    /**
     * @return array<string, mixed>
     */
    public function expectedDrawDate(): array
    {
        return $this->expectedDrawDate;
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
        $fieldNames = $this->requiredFields;

        foreach (array_keys($this->fields) as $field) {
            if (is_string($field) && trim($field) !== '') {
                $fieldNames[] = $field;
            }
        }

        $expectedField = (string) ($this->expectedDrawDate['field'] ?? '');
        if (trim($expectedField) !== '') {
            $fieldNames[] = trim($expectedField);
        }

        return array_values(array_unique($fieldNames));
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
