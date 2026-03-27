<?php

namespace Gametech\Lotto\Services\AutoResultV2\Executors;

use Gametech\Lotto\Services\AutoResultV2\ConfigData\ParserConfigData;
use Gametech\Lotto\Services\AutoResultV2\Extractors\CssSelectorExtractor;
use Gametech\Lotto\Services\AutoResultV2\Extractors\JsonPathExtractor;
use Gametech\Lotto\Services\AutoResultV2\Extractors\RegexExtractor;
use Gametech\Lotto\Services\AutoResultV2\Extractors\ScriptJsonPathExtractor;

class ExtractExecutor
{
    public function __construct(
        private ?JsonPathExtractor $jsonPathExtractor = null,
        private ?CssSelectorExtractor $cssSelectorExtractor = null,
        private ?RegexExtractor $regexExtractor = null,
        private ?ScriptJsonPathExtractor $scriptJsonPathExtractor = null
    ) {
        $this->jsonPathExtractor = $this->jsonPathExtractor ?: new JsonPathExtractor();
        $this->cssSelectorExtractor = $this->cssSelectorExtractor ?: new CssSelectorExtractor();
        $this->regexExtractor = $this->regexExtractor ?: new RegexExtractor();
        $this->scriptJsonPathExtractor = $this->scriptJsonPathExtractor ?: new ScriptJsonPathExtractor();
    }

    /**
     * @return array<string,mixed>
     */
    public function execute(string $body, ParserConfigData $config): array
    {
        $fields = $config->fields();
        $recordSelector = $config->recordSelector();
        $mode = $config->mode();
        $recordType = $config->recordParserType();

        if (in_array($mode, ['record_list', 'scoped_record'], true)) {
            $records = $this->extractRecordList($body, $recordType, $recordSelector);
            $candidates = [];
            foreach ($records as $index => $record) {
                $candidates[] = [
                    'index' => $index,
                    'raw_record' => $record,
                    'fields' => $this->extractFields($record, $fields, $recordType),
                ];
            }

            return [
                'candidates' => $candidates,
                'record_selector_match_count' => count($records),
                'first_matched_block_preview' => $records[0] ?? null,
            ];
        }

        return [
            'candidates' => [[
                'index' => 0,
                'raw_record' => $body,
                'fields' => $this->extractFields($body, $fields, $recordType),
            ]],
            'record_selector_match_count' => 1,
            'first_matched_block_preview' => $body,
        ];
    }

    /**
     * @param array<string,mixed> $fields
     * @return array<string,mixed>
     */
    private function extractFields(mixed $record, array $fields, string $extractorType): array
    {
        $result = [];
        foreach ($fields as $name => $spec) {
            if (! is_string($name) || trim($name) === '') {
                continue;
            }

            $result[$name] = $this->extractOne($record, $spec, $extractorType);
        }

        return $result;
    }

    private function extractOne(mixed $record, mixed $spec, string $extractorType): mixed
    {
        if (is_string($spec)) {
            return $this->extractByType($record, $extractorType, $spec);
        }

        if (! is_array($spec)) {
            return null;
        }

        $type = strtoupper((string) ($spec['type'] ?? $extractorType));
        $selector = (string) ($spec['path'] ?? $spec['selector'] ?? $spec['pattern'] ?? '');
        $context = $spec;
        unset($context['type'], $context['path'], $context['selector'], $context['pattern']);

        return $this->extractByType($record, $type, $selector, $context);
    }

    private function extractByType(mixed $record, string $type, string $selector, array $context = []): mixed
    {
        return match (strtoupper($type)) {
            ParserConfigData::TYPE_JSON_PATH => $this->jsonPathExtractor->extract($record, $selector, $context),
            ParserConfigData::TYPE_CSS_SELECTOR => $this->cssSelectorExtractor->extract((string) $record, $selector, $context),
            ParserConfigData::TYPE_REGEX => $this->regexExtractor->extract((string) $record, $selector, $context),
            'SCRIPT_JSON_PATH' => $this->scriptJsonPathExtractor->extract((string) $record, $selector, $context),
            default => null,
        };
    }

    /**
     * @return array<int,mixed>
     */
    private function extractRecordList(string $body, string $extractorType, ?string $recordSelector): array
    {
        $selector = (string) ($recordSelector ?? '');
        if ($selector === '') {
            return [];
        }

        return match (strtoupper($extractorType)) {
            ParserConfigData::TYPE_JSON_PATH => $this->jsonPathExtractor->extractMany($body, $selector),
            ParserConfigData::TYPE_CSS_SELECTOR => $this->cssSelectorExtractor->extractMany($body, $selector),
            ParserConfigData::TYPE_REGEX => $this->regexExtractor->extractMany($body, $selector),
            'SCRIPT_JSON_PATH' => $this->scriptJsonPathExtractor->extractMany($body, $selector),
            default => [],
        };
    }
}
