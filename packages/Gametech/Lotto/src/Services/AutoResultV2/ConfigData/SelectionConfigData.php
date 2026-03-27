<?php

namespace Gametech\Lotto\Services\AutoResultV2\ConfigData;

use Gametech\Lotto\Services\AutoResultV2\Config\SelectionStageGuard;
use InvalidArgumentException;

final class SelectionConfigData
{
    public const STAGE_PRE_MAPPING = 'PRE_MAPPING';
    public const STAGE_POST_MAPPING = 'POST_MAPPING';

    private string $selectionStage;
    private string $strategy;
    private ?string $dateField;
    private array $requiredFields;
    private array $references;
    private array $meta;

    private function __construct(
        string $selectionStage,
        string $strategy,
        ?string $dateField,
        array $requiredFields,
        array $references,
        array $meta
    ) {
        $this->selectionStage = $selectionStage;
        $this->strategy = $strategy;
        $this->dateField = $dateField;
        $this->requiredFields = $requiredFields;
        $this->references = $references;
        $this->meta = $meta;
    }

    public static function fromArray(array $data): self
    {
        $selectionStage = SelectionStageGuard::normalize((string) self::read($data, ['selection_stage', 'stage'], self::STAGE_PRE_MAPPING));
        $strategy = strtolower((string) self::read($data, ['strategy', 'type'], 'strict_single_match'));
        $dateField = self::stringOrNull(self::read($data, ['date_field'], null));
        $requiredFields = self::arrayOfStrings(self::read($data, ['required_fields'], []), 'required_fields');
        $references = self::arrayOfStrings(self::read($data, ['references'], []), 'references');
        $meta = self::arrayOrEmpty(self::read($data, ['meta'], []), 'meta');

        return new self(
            $selectionStage,
            $strategy,
            $dateField,
            $requiredFields,
            $references,
            $meta
        );
    }

    public function toArray(): array
    {
        return [
            'selection_stage' => $this->selectionStage,
            'strategy' => $this->strategy,
            'date_field' => $this->dateField,
            'required_fields' => $this->requiredFields,
            'references' => $this->references,
            'meta' => $this->meta,
        ];
    }

    public function selectionStage(): string
    {
        return $this->selectionStage;
    }

    public function strategy(): string
    {
        return $this->strategy;
    }

    public function dateField(): ?string
    {
        return $this->dateField;
    }

    /**
     * @return array<int, string>
     */
    public function requiredFields(): array
    {
        return $this->requiredFields;
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
        return array_values(array_unique(array_merge($this->requiredFields, $this->dateField ? [$this->dateField] : [])));
    }

    /**
     * @return array<int, string>
     */
    public static function allowedStages(): array
    {
        return [
            self::STAGE_PRE_MAPPING,
            self::STAGE_POST_MAPPING,
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

