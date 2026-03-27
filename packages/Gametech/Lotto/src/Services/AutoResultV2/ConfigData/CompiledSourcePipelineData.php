<?php

namespace Gametech\Lotto\Services\AutoResultV2\ConfigData;

use InvalidArgumentException;

final class CompiledSourcePipelineData
{
    public const VERSION_LEGACY = 'LEGACY';
    public const VERSION_V2_SHADOW = 'V2_SHADOW';
    public const VERSION_V2_CUTOVER = 'V2_CUTOVER';

    private string $pipelineVersion;
    private FetchConfigData $fetch;
    private ParserConfigData $parser;
    private MappingConfigData $mapping;
    private SelectionConfigData $selection;
    private ValidationConfigData $validation;
    private ReadinessConfigData $readiness;
    private array $meta;

    private function __construct(
        string $pipelineVersion,
        FetchConfigData $fetch,
        ParserConfigData $parser,
        MappingConfigData $mapping,
        SelectionConfigData $selection,
        ValidationConfigData $validation,
        ReadinessConfigData $readiness,
        array $meta
    ) {
        $this->pipelineVersion = $pipelineVersion;
        $this->fetch = $fetch;
        $this->parser = $parser;
        $this->mapping = $mapping;
        $this->selection = $selection;
        $this->validation = $validation;
        $this->readiness = $readiness;
        $this->meta = $meta;
    }

    public static function fromArray(array $data): self
    {
        $pipelineVersion = self::normalizePipelineVersion((string) self::read($data, ['pipeline_version', 'version'], self::VERSION_LEGACY));
        $fetch = FetchConfigData::fromArray(self::section($data, ['fetch', 'fetch_config', 'fetch_config_json'], [
            'fetch_strategy' => self::read($data, ['fetch_strategy'], null),
            'endpoint_url' => self::read($data, ['endpoint_url'], null),
            'http_method' => self::read($data, ['http_method'], null),
            'headers' => self::read($data, ['headers'], []),
            'query' => self::read($data, ['query'], []),
            'body' => self::read($data, ['body'], null),
            'timeout_seconds' => self::read($data, ['timeout_seconds'], 10),
            'manual_input' => self::read($data, ['manual_input'], null),
        ]));
        $parser = ParserConfigData::fromArray(self::section($data, ['parser', 'parser_config', 'parser_config_json'], []));
        $mapping = MappingConfigData::fromArray(self::section($data, ['mapping', 'mapping_config', 'mapping_config_json'], []));
        $selection = SelectionConfigData::fromArray(self::section($data, ['selection', 'selection_config', 'selection_config_json'], $parser->selectionStrategy()));
        $validation = ValidationConfigData::fromArray(self::section($data, ['validation', 'validation_config', 'validation_config_json'], []));
        $readiness = ReadinessConfigData::fromArray(self::section($data, ['readiness', 'readiness_config', 'readiness_config_json'], []));
        $meta = self::arrayOrEmpty(self::read($data, ['meta'], []), 'meta');

        return new self($pipelineVersion, $fetch, $parser, $mapping, $selection, $validation, $readiness, $meta);
    }

    public function toArray(): array
    {
        return [
            'pipeline_version' => $this->pipelineVersion,
            'fetch' => $this->fetch->toArray(),
            'parser' => $this->parser->toArray(),
            'mapping' => $this->mapping->toArray(),
            'selection' => $this->selection->toArray(),
            'validation' => $this->validation->toArray(),
            'readiness' => $this->readiness->toArray(),
            'meta' => $this->meta,
        ];
    }

    public function pipelineVersion(): string
    {
        return $this->pipelineVersion;
    }

    public function fetch(): FetchConfigData
    {
        return $this->fetch;
    }

    public function parser(): ParserConfigData
    {
        return $this->parser;
    }

    public function mapping(): MappingConfigData
    {
        return $this->mapping;
    }

    public function selection(): SelectionConfigData
    {
        return $this->selection;
    }

    public function validation(): ValidationConfigData
    {
        return $this->validation;
    }

    public function readiness(): ReadinessConfigData
    {
        return $this->readiness;
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
    public static function allowedPipelineVersions(): array
    {
        return [
            self::VERSION_LEGACY,
            self::VERSION_V2_SHADOW,
            self::VERSION_V2_CUTOVER,
        ];
    }

    private static function normalizePipelineVersion(string $value): string
    {
        $normalized = strtoupper(trim($value));

        if (! in_array($normalized, self::allowedPipelineVersions(), true)) {
            throw new InvalidArgumentException('pipeline_version ไม่ถูกต้อง: ' . $value);
        }

        return $normalized;
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

    /**
     * @param array<string, mixed> $default
     * @return array<string, mixed>
     */
    private static function section(array $data, array $keys, array $default): array
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $value = $data[$key];
                if (! is_array($value)) {
                    throw new InvalidArgumentException($key . ' ต้องเป็น array');
                }

                return $value;
            }
        }

        return $default;
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
}
