<?php

namespace Gametech\Lotto\Services\AutoResultV2\Extractors;

class ScriptJsonPathExtractor
{
    public function __construct(
        private ?JsonPathExtractor $jsonPathExtractor = null
    ) {
        $this->jsonPathExtractor = $this->jsonPathExtractor ?: new JsonPathExtractor();
    }

    /**
     * @param array<string,mixed> $context
     * @return mixed
     */
    public function extract(string $input, string $path = '$', array $context = [])
    {
        $documents = $this->collectDocuments($input, $context);
        if ($documents === []) {
            return $context['multiple'] ?? false ? [] : null;
        }

        if (($context['multiple'] ?? false) === true) {
            $results = [];
            foreach ($documents as $document) {
                $value = $this->jsonPathExtractor->extract($document, $path, $context);
                if ($value !== null) {
                    $results[] = $value;
                }
            }

            return $results;
        }

        return $this->jsonPathExtractor->extract($documents[0], $path, $context);
    }

    /**
     * @return array<int,mixed>
     */
    public function extractMany(string $input, string $path = '$', array $context = []): array
    {
        $context['multiple'] = true;
        $value = $this->extract($input, $path, $context);
        if (! is_array($value)) {
            return $value === null ? [] : [$value];
        }

        return array_values($value);
    }

    /**
     * @param array<string,mixed> $context
     * @return array<int,mixed>
     */
    private function collectDocuments(string $input, array $context): array
    {
        $input = trim($input);
        if ($input === '') {
            return [];
        }

        $decoded = json_decode($input, true);
        if (is_array($decoded)) {
            return [$decoded];
        }

        $documents = [];
        $scripts = $this->extractScriptBlocks($input);
        foreach ($scripts as $script) {
            $document = $this->decodeScriptJson($script, $context);
            if ($document !== null) {
                $documents[] = $document;
            }
        }

        return $documents;
    }

    /** @return array<int,string> */
    private function extractScriptBlocks(string $input): array
    {
        if (! preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $input, $matches)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', $matches[1] ?? []), static fn ($value) => $value !== ''));
    }

    /**
     * @param array<string,mixed> $context
     * @return array<string,mixed>|null
     */
    private function decodeScriptJson(string $script, array $context): ?array
    {
        $script = trim(html_entity_decode($script, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $script = preg_replace('/^<!--|-->$/', '', $script) ?? $script;
        $script = trim($script);

        $decoded = json_decode($script, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = null;
        $end = null;
        foreach (['{', '['] as $open) {
            $pos = strpos($script, $open);
            if ($pos === false) {
                continue;
            }
            $close = $open === '{' ? '}' : ']';
            $last = strrpos($script, $close);
            if ($last === false || $last <= $pos) {
                continue;
            }
            $start = $pos;
            $end = $last;
            break;
        }

        if ($start === null || $end === null) {
            return null;
        }

        $fragment = substr($script, $start, $end - $start + 1);
        $decoded = json_decode($fragment, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (($context['allow_js_assignment'] ?? true) === true) {
            $fragment = preg_replace('/^[A-Za-z0-9_$.]+\s*=\s*/', '', $fragment) ?? $fragment;
            $decoded = json_decode($fragment, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
