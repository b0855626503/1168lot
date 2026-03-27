<?php

namespace Gametech\Lotto\Services\AutoResult;

use Gametech\Lotto\Exceptions\ResultParseException;
use voku\helper\HtmlDomParser;

class ResultParser
{
    public const TYPE_JSON_PATH = 'JSON_PATH';
    public const TYPE_CSS_SELECTOR = 'CSS_SELECTOR';
    public const TYPE_REGEX = 'REGEX';

    /**
     * @param array<string,mixed> $parserConfig
     * @return array<string,mixed>
     */
    public function parse(string $type, array $parserConfig, string $responseBody): array
    {
        return match (strtoupper($type)) {
            self::TYPE_JSON_PATH => $this->parseJsonPath($parserConfig, $responseBody),
            self::TYPE_CSS_SELECTOR => $this->parseCssSelector($parserConfig, $responseBody),
            self::TYPE_REGEX => $this->parseRegex($parserConfig, $responseBody),
            default => throw new ResultParseException('parser_type ไม่รองรับ: ' . $type),
        };
    }

    /**
     * @param array<string,mixed> $config
     * @return array<string,mixed>
     */
    private function parseJsonPath(array $config, string $responseBody): array
    {
        $decoded = json_decode($responseBody, true);
        if (! is_array($decoded)) {
            throw new ResultParseException('JSON decode ไม่สำเร็จ');
        }

        $fields = $config['fields'] ?? [];
        if (! is_array($fields) || $fields === []) {
            return ['payload' => $decoded];
        }

        $output = [];
        foreach ($fields as $field => $path) {
            if (! is_string($path) || trim($path) === '') {
                continue;
            }

            $output[(string) $field] = $this->extractJsonPath($decoded, $path);
        }

        return $output;
    }

    /**
     * @param array<string,mixed> $config
     * @return array<string,mixed>
     */
    private function parseCssSelector(array $config, string $responseBody): array
    {
        $fields = $config['fields'] ?? [];
        if (! is_array($fields) || $fields === []) {
            throw new ResultParseException('CSS parser ต้องกำหนด fields');
        }

        $dom = HtmlDomParser::str_get_html($responseBody);
        if ($dom === false) {
            throw new ResultParseException('HTML parse ไม่สำเร็จ');
        }

        $output = [];
        foreach ($fields as $field => $selector) {
            if (! is_string($selector) || trim($selector) === '') {
                continue;
            }

            $node = $dom->findOne($selector);
            $output[(string) $field] = $node ? trim((string) $node->text()) : null;
        }

        return $output;
    }

    /**
     * @param array<string,mixed> $config
     * @return array<string,mixed>
     */
    private function parseRegex(array $config, string $responseBody): array
    {
        $fields = $config['fields'] ?? [];
        if (! is_array($fields) || $fields === []) {
            throw new ResultParseException('REGEX parser ต้องกำหนด fields');
        }

        $output = [];
        foreach ($fields as $field => $pattern) {
            if (! is_string($pattern) || trim($pattern) === '') {
                continue;
            }

            $matches = [];
            if (@preg_match($pattern, $responseBody, $matches) === 1) {
                $output[(string) $field] = $matches[1] ?? ($matches[0] ?? null);
                continue;
            }

            if (preg_last_error() !== PREG_NO_ERROR) {
                throw new ResultParseException('REGEX ผิดรูปแบบสำหรับ field: ' . (string) $field);
            }

            $output[(string) $field] = null;
        }

        return $output;
    }

    /**
     * @param array<string,mixed> $decoded
     * @return mixed
     */
    private function extractJsonPath(array $decoded, string $path)
    {
        $normalizedPath = trim($path);
        $normalizedPath = ltrim($normalizedPath, '$.');
        if ($normalizedPath === '') {
            return $decoded;
        }

        $segments = preg_split('/\./', $normalizedPath) ?: [];
        $cursor = $decoded;

        foreach ($segments as $segment) {
            if (! is_array($cursor)) {
                return null;
            }

            if (preg_match('/^([a-zA-Z0-9_\-]+)\[(\d+)\]$/', (string) $segment, $m) === 1) {
                $key = (string) $m[1];
                $index = (int) $m[2];
                $cursor = $cursor[$key][$index] ?? null;
                continue;
            }

            $cursor = $cursor[$segment] ?? null;
        }

        return $cursor;
    }
}
