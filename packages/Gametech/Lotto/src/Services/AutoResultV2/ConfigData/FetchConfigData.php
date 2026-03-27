<?php

namespace Gametech\Lotto\Services\AutoResultV2\ConfigData;

use InvalidArgumentException;

final class FetchConfigData
{
    public const STRATEGY_JSON_HTTP = 'JSON_HTTP';
    public const STRATEGY_HTML_HTTP = 'HTML_HTTP';
    public const STRATEGY_RENDERED_BROWSER = 'RENDERED_BROWSER';
    public const STRATEGY_EMBEDDED_JSON = 'EMBEDDED_JSON';
    public const STRATEGY_MANUAL_INPUT = 'MANUAL_INPUT';

    private string $strategy;
    private ?string $endpointUrl;
    private string $httpMethod;
    private array $headers;
    private array $query;
    private mixed $body;
    private int $timeoutSeconds;
    private ?string $manualInput;
    private array $meta;

    private function __construct(
        string $strategy,
        ?string $endpointUrl,
        string $httpMethod,
        array $headers,
        array $query,
        mixed $body,
        int $timeoutSeconds,
        ?string $manualInput,
        array $meta
    ) {
        $this->strategy = $strategy;
        $this->endpointUrl = $endpointUrl;
        $this->httpMethod = $httpMethod;
        $this->headers = $headers;
        $this->query = $query;
        $this->body = $body;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->manualInput = $manualInput;
        $this->meta = $meta;
    }

    public static function fromArray(array $data): self
    {
        $strategy = self::normalizeStrategy((string) self::read($data, ['fetch_strategy', 'strategy'], self::STRATEGY_JSON_HTTP));
        $endpointUrl = self::stringOrNull(self::read($data, ['endpoint_url', 'url'], null));
        $httpMethod = strtoupper((string) self::read($data, ['http_method', 'method'], 'GET'));
        $headers = self::arrayOrEmpty(self::read($data, ['headers', 'request_headers_json'], []), 'headers');
        $query = self::arrayOrEmpty(self::read($data, ['query', 'request_query_template_json'], []), 'query');
        $body = self::read($data, ['body', 'request_body_template_json'], null);
        $timeoutSeconds = max(1, (int) self::read($data, ['timeout_seconds', 'timeoutSeconds'], 10));
        $manualInput = self::stringOrNull(self::read($data, ['manual_input', 'manualInput'], null));
        $meta = self::arrayOrEmpty(self::read($data, ['meta'], []), 'meta');

        if ($strategy === self::STRATEGY_MANUAL_INPUT && trim((string) $manualInput) === '') {
            throw new InvalidArgumentException('manual_input จำเป็นสำหรับ strategy MANUAL_INPUT');
        }

        if ($strategy !== self::STRATEGY_MANUAL_INPUT && trim((string) $endpointUrl) === '') {
            throw new InvalidArgumentException('endpoint_url จำเป็นสำหรับ fetch strategy ' . $strategy);
        }

        if ($strategy !== self::STRATEGY_MANUAL_INPUT && trim($httpMethod) === '') {
            throw new InvalidArgumentException('http_method จำเป็นสำหรับ fetch strategy ' . $strategy);
        }

        return new self(
            $strategy,
            $endpointUrl,
            $httpMethod,
            $headers,
            $query,
            $body,
            $timeoutSeconds,
            $manualInput,
            $meta
        );
    }

    public function toArray(): array
    {
        return [
            'fetch_strategy' => $this->strategy,
            'endpoint_url' => $this->endpointUrl,
            'http_method' => $this->httpMethod,
            'headers' => $this->headers,
            'query' => $this->query,
            'body' => $this->body,
            'timeout_seconds' => $this->timeoutSeconds,
            'manual_input' => $this->manualInput,
            'meta' => $this->meta,
        ];
    }

    public function strategy(): string
    {
        return $this->strategy;
    }

    public function endpointUrl(): ?string
    {
        return $this->endpointUrl;
    }

    public function httpMethod(): string
    {
        return $this->httpMethod;
    }

    /**
     * @return array<string, mixed>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return $this->query;
    }

    public function body(): mixed
    {
        return $this->body;
    }

    public function timeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    public function manualInput(): ?string
    {
        return $this->manualInput;
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
    public static function allowedStrategies(): array
    {
        return [
            self::STRATEGY_JSON_HTTP,
            self::STRATEGY_HTML_HTTP,
            self::STRATEGY_RENDERED_BROWSER,
            self::STRATEGY_EMBEDDED_JSON,
            self::STRATEGY_MANUAL_INPUT,
        ];
    }

    private static function normalizeStrategy(string $value): string
    {
        $normalized = strtoupper(trim($value));
        if (! in_array($normalized, self::allowedStrategies(), true)) {
            throw new InvalidArgumentException('fetch_strategy ไม่ถูกต้อง: ' . $value);
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
