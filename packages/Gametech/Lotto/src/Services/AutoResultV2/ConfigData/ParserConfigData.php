<?php

namespace Gametech\Lotto\Services\AutoResultV2\ConfigData;

use InvalidArgumentException;

final class ParserConfigData
{
    public const TYPE_JSON_PATH = 'JSON_PATH';
    public const TYPE_CSS_SELECTOR = 'CSS_SELECTOR';
    public const TYPE_REGEX = 'REGEX';
    public const TYPE_SCRIPT_JSON_PATH = 'SCRIPT_JSON_PATH';

    private string $type;
    private string $mode;
    private ?string $recordSelector;
    private string $recordParserType;
    private array $fields;
    private array $selectionStrategy;
    private array $meta;

    private function __construct(
        string $type,
        string $mode,
        ?string $recordSelector,
        string $recordParserType,
        array $fields,
        array $selectionStrategy,
        array $meta
    ) {
        $this->type = $type;
        $this->mode = $mode;
        $this->recordSelector = $recordSelector;
        $this->recordParserType = $recordParserType;
        $this->fields = $fields;
        $this->selectionStrategy = $selectionStrategy;
        $this->meta = $meta;
    }

    public static function fromArray(array $data): self
    {
        $type = self::normalizeType((string) self::read($data, ['parser_type', 'type'], self::TYPE_JSON_PATH));
        $mode = strtolower((string) self::read($data, ['mode'], 'single_payload'));
        $recordSelector = self::stringOrNull(self::read($data, ['record_selector', 'selector'], null));
        $recordParserType = self::normalizeType((string) self::read($data, ['record_parser_type'], $type));
        $fields = self::arrayOrEmpty(self::read($data, ['fields'], []), 'fields');
        $selectionStrategy = self::arrayOrEmpty(self::read($data, ['selection_strategy'], []), 'selection_strategy');
        $meta = self::arrayOrEmpty(self::read($data, ['meta'], []), 'meta');

        if ($fields === []) {
            throw new InvalidArgumentException('fields จำเป็นสำหรับ parser config');
        }

        return new self(
            $type,
            $mode,
            $recordSelector,
            $recordParserType,
            $fields,
            $selectionStrategy,
            $meta
        );
    }

    public function toArray(): array
    {
        return [
            'parser_type' => $this->type,
            'mode' => $this->mode,
            'record_selector' => $this->recordSelector,
            'record_parser_type' => $this->recordParserType,
            'fields' => $this->fields,
            'selection_strategy' => $this->selectionStrategy,
            'meta' => $this->meta,
        ];
    }

    public function type(): string
    {
        return $this->type;
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function recordSelector(): ?string
    {
        return $this->recordSelector;
    }

    public function recordParserType(): string
    {
        return $this->recordParserType;
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
    public function selectionStrategy(): array
    {
        return $this->selectionStrategy;
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
        return array_values(array_filter(array_map('strval', array_keys($this->fields))));
    }

    /**
     * @return array<int, string>
     */
    public static function allowedTypes(): array
    {
        return [
            self::TYPE_JSON_PATH,
            self::TYPE_CSS_SELECTOR,
            self::TYPE_REGEX,
            self::TYPE_SCRIPT_JSON_PATH,
        ];
    }

    private static function normalizeType(string $value): string
    {
        $normalized = strtoupper(trim($value));

        if (! in_array($normalized, self::allowedTypes(), true)) {
            throw new InvalidArgumentException('parser_type ไม่ถูกต้อง: ' . $value);
        }

        return $normalized;
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
