<?php

namespace Gametech\Lotto\Services\AutoResultV2\Extractors;

use voku\helper\HtmlDomParser;

class CssSelectorExtractor
{
    /**
     * @param array<string,mixed> $context
     * @return mixed
     */
    public function extract(string $html, string $selector, array $context = [])
    {
        $selector = trim($selector);
        if ($selector === '') {
            return null;
        }

        $dom = HtmlDomParser::str_get_html($html);
        if ($dom === false) {
            return null;
        }

        $multiple = (bool) ($context['multiple'] ?? false);
        $returnHtml = (bool) ($context['return_html'] ?? false);

        if ($multiple) {
            $nodes = $dom->findMulti($selector);
            $output = [];
            foreach ($nodes ?? [] as $node) {
                $output[] = $returnHtml ? trim((string) $node->html()) : trim((string) $node->text());
            }

            return $output;
        }

        $node = $dom->findOne($selector);
        if (! $node) {
            return null;
        }

        return $returnHtml ? trim((string) $node->html()) : trim((string) $node->text());
    }

    /**
     * @return array<int,mixed>
     */
    public function extractMany(string $html, string $selector, array $context = []): array
    {
        $context['multiple'] = true;
        $value = $this->extract($html, $selector, $context);
        if (! is_array($value)) {
            return $value === null ? [] : [$value];
        }

        return array_values($value);
    }
}
