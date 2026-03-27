<?php

namespace Gametech\Lotto\Services\AutoResult;

use Gametech\Lotto\Exceptions\ResultParseException;
use voku\helper\HtmlDomParser;
use voku\helper\SimpleHtmlDomInterface;

class ResultParser
{
    public const TYPE_JSON_PATH = 'JSON_PATH';
    public const TYPE_CSS_SELECTOR = 'CSS_SELECTOR';
    public const TYPE_REGEX = 'REGEX';

    private const MODE_SINGLE_PAYLOAD = 'single_payload';
    private const MODE_RECORD_LIST = 'record_list';
    private const MODE_SCOPED_RECORD = 'scoped_record';

    /**
     * @param array<string,mixed> $parserConfig
     * @return array<string,mixed>
     */
    public function parse(string $type, array $parserConfig, string $responseBody): array
    {
        if ($this->isV2Config($parserConfig)) {
            return $this->parseV2($type, $parserConfig, $responseBody);
        }

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
    private function parseV2(string $type, array $config, string $responseBody): array
    {
        $mode = strtolower((string) ($config['mode'] ?? self::MODE_SINGLE_PAYLOAD));
        $recordType = strtoupper((string) ($config['record_parser_type'] ?? $type));
        $recordSelector = $config['record_selector'] ?? null;
        $fields = $config['fields'] ?? [];

        if (! is_array($fields) || $fields === []) {
            throw new ResultParseException('parser_config_json v2 ต้องกำหนด fields');
        }

        $candidates = match ($mode) {
            self::MODE_SINGLE_PAYLOAD => $this->extractSinglePayloadCandidates($recordType, $responseBody, $fields),
            self::MODE_RECORD_LIST => $this->extractRecordListCandidates($recordType, $recordSelector, $responseBody, $fields),
            self::MODE_SCOPED_RECORD => $this->extractScopedRecordCandidates($recordType, $recordSelector, $responseBody, $fields),
            default => throw new ResultParseException('parser mode ไม่รองรับ: ' . $mode),
        };

        return [
            'version' => 2,
            'mode' => $mode,
            'parser_type' => strtoupper($type),
            'record_parser_type' => $recordType,
            'candidate_count' => count($candidates),
            'candidates' => $candidates,
        ];
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
     * @param array<string,mixed> $fields
     * @return array<int,array<string,mixed>>
     */
    private function extractSinglePayloadCandidates(string $recordType, string $responseBody, array $fields): array
    {
        if ($recordType === self::TYPE_JSON_PATH) {
            $decoded = json_decode($responseBody, true);
            if (! is_array($decoded)) {
                throw new ResultParseException('JSON decode ไม่สำเร็จ');
            }

            return [[
                'index' => 0,
                'raw_record' => $decoded,
                'fields' => $this->extractFieldsFromRecord($recordType, $fields, $decoded, null, $responseBody),
            ]];
        }

        $dom = null;
        if ($recordType === self::TYPE_CSS_SELECTOR) {
            $dom = HtmlDomParser::str_get_html($responseBody);
            if ($dom === false) {
                throw new ResultParseException('HTML parse ไม่สำเร็จ');
            }
        }

        return [[
            'index' => 0,
            'raw_record' => $recordType === self::TYPE_REGEX ? $responseBody : null,
            'fields' => $this->extractFieldsFromRecord($recordType, $fields, null, $dom, $responseBody),
        ]];
    }

    /**
     * @param mixed $recordSelector
     * @param array<string,mixed> $fields
     * @return array<int,array<string,mixed>>
     */
    private function extractRecordListCandidates(string $recordType, $recordSelector, string $responseBody, array $fields): array
    {
        if ($recordType === self::TYPE_JSON_PATH) {
            if (! is_string($recordSelector) || trim($recordSelector) === '') {
                throw new ResultParseException('record_selector จำเป็นสำหรับ JSON record_list');
            }

            $decoded = json_decode($responseBody, true);
            if (! is_array($decoded)) {
                throw new ResultParseException('JSON decode ไม่สำเร็จ');
            }

            $records = $this->extractJsonPath($decoded, $recordSelector);
            if (! is_array($records)) {
                return [];
            }

            $candidates = [];
            $index = 0;
            foreach ($records as $record) {
                if (! is_array($record)) {
                    continue;
                }

                $candidates[] = [
                    'index' => $index++,
                    'raw_record' => $record,
                    'fields' => $this->extractFieldsFromRecord($recordType, $fields, $record, null, $responseBody),
                ];
            }

            return $candidates;
        }

        if ($recordType === self::TYPE_CSS_SELECTOR) {
            if (! is_string($recordSelector) || trim($recordSelector) === '') {
                throw new ResultParseException('record_selector จำเป็นสำหรับ CSS record_list');
            }

            $dom = HtmlDomParser::str_get_html($responseBody);
            if ($dom === false) {
                throw new ResultParseException('HTML parse ไม่สำเร็จ');
            }

            $nodes = $dom->findMulti($recordSelector);
            $candidates = [];
            $index = 0;
            foreach ($nodes as $node) {
                $candidates[] = [
                    'index' => $index++,
                    'raw_record' => trim((string) $node->outerHtml()),
                    'fields' => $this->extractFieldsFromRecord($recordType, $fields, null, $node, $responseBody),
                ];
            }

            return $candidates;
        }

        if ($recordType === self::TYPE_REGEX) {
            if (! is_string($recordSelector) || trim($recordSelector) === '') {
                throw new ResultParseException('record_selector จำเป็นสำหรับ REGEX record_list');
            }

            $matches = [];
            $ok = @preg_match_all($recordSelector, $responseBody, $matches, PREG_SET_ORDER);
            if ($ok === false || preg_last_error() !== PREG_NO_ERROR) {
                throw new ResultParseException('REGEX ผิดรูปแบบสำหรับ record_selector');
            }

            $candidates = [];
            foreach ($matches as $index => $m) {
                $recordText = (string) ($m[1] ?? ($m[0] ?? ''));
                $candidates[] = [
                    'index' => $index,
                    'raw_record' => $recordText,
                    'fields' => $this->extractFieldsFromRecord($recordType, $fields, null, null, $recordText),
                ];
            }

            return $candidates;
        }

        throw new ResultParseException('record_parser_type ไม่รองรับ: ' . $recordType);
    }

    /**
     * @param mixed $recordSelector
     * @param array<string,mixed> $fields
     * @return array<int,array<string,mixed>>
     */
    private function extractScopedRecordCandidates(string $recordType, $recordSelector, string $responseBody, array $fields): array
    {
        $candidates = $this->extractRecordListCandidates($recordType, $recordSelector, $responseBody, $fields);
        if ($candidates !== []) {
            return $candidates;
        }

        return $this->extractSinglePayloadCandidates($recordType, $responseBody, $fields);
    }

    /**
     * @param array<string,mixed> $fields
     * @param array<string,mixed>|null $recordArray
     * @return array<string,mixed>
     */
    private function extractFieldsFromRecord(string $recordType, array $fields, ?array $recordArray, ?SimpleHtmlDomInterface $recordNode, string $recordText): array
    {
        $output = [];
        foreach ($fields as $field => $spec) {
            $output[(string) $field] = $this->extractFieldValue($recordType, $spec, $recordArray, $recordNode, $recordText);
        }

        return $output;
    }

    /**
     * @param mixed $spec
     * @param array<string,mixed>|null $recordArray
     * @return mixed
     */
    private function extractFieldValue(string $defaultType, $spec, ?array $recordArray, ?SimpleHtmlDomInterface $recordNode, string $recordText)
    {
        if (is_string($spec)) {
            $spec = ['type' => $defaultType, 'selector' => $spec];
            if (strtoupper($defaultType) === self::TYPE_JSON_PATH) {
                $spec = ['type' => $defaultType, 'path' => $spec['selector']];
            }
            if (strtoupper($defaultType) === self::TYPE_REGEX) {
                $spec = ['type' => $defaultType, 'pattern' => $spec['selector']];
            }
        }

        if (! is_array($spec)) {
            return null;
        }

        $type = strtoupper((string) ($spec['type'] ?? $defaultType));
        if ($type === self::TYPE_JSON_PATH) {
            if (! is_array($recordArray)) {
                return null;
            }
            $path = (string) ($spec['path'] ?? '');
            if ($path === '') {
                return null;
            }
            return $this->extractJsonPath($recordArray, $path);
        }

        if ($type === self::TYPE_CSS_SELECTOR) {
            if (! $recordNode) {
                return null;
            }
            $selector = (string) ($spec['selector'] ?? '');
            if ($selector === '') {
                return null;
            }
            $attr = strtolower((string) ($spec['attr'] ?? 'text'));
            $node = $recordNode->findOne($selector);
            if (! $node) {
                return null;
            }
            if ($attr === 'html') {
                return trim((string) $node->innerHtml());
            }
            if ($attr === 'outer_html') {
                return trim((string) $node->outerHtml());
            }
            if ($attr === 'text' || $attr === '') {
                return trim((string) $node->text());
            }
            return trim((string) $node->getAttribute($attr));
        }

        if ($type === self::TYPE_REGEX) {
            $pattern = (string) ($spec['pattern'] ?? '');
            if ($pattern === '') {
                return null;
            }
            $matches = [];
            if (@preg_match($pattern, $recordText, $matches) === 1) {
                return $matches[1] ?? ($matches[0] ?? null);
            }
            if (preg_last_error() !== PREG_NO_ERROR) {
                throw new ResultParseException('REGEX ผิดรูปแบบสำหรับ field');
            }
            return null;
        }

        throw new ResultParseException('field type ไม่รองรับ: ' . $type);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function isV2Config(array $config): bool
    {
        if ((int) ($config['version'] ?? 1) >= 2) {
            return true;
        }

        return isset($config['mode']) || isset($config['record_parser_type']) || isset($config['record_selector']);
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
