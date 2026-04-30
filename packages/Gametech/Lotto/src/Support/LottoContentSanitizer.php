<?php

namespace Gametech\Lotto\Support;

final class LottoContentSanitizer
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function sanitizePayload(array $payload): array
    {
        $textFields = [
            'title',
            'summary',
            'rules_content',
            'schedule_content',
            'prize_content',
            'formula_content',
            'seo_title',
            'seo_description',
        ];

        foreach ($textFields as $field) {
            $payload[$field] = self::sanitizeHtmlNullable($payload[$field] ?? null);
        }

        $payload['is_enabled'] = filter_var($payload['is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);

        return $payload;
    }

    public static function sanitizeHtmlNullable(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $content = trim($value);
        if ($content === '') {
            return null;
        }

        $content = preg_replace('/<\s*script\b[^>]*>(.*?)<\s*\/\s*script>/is', '', $content) ?? '';
        $content = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $content) ?? '';
        $content = preg_replace('/\s(href|src)\s*=\s*("|\')\s*javascript:[^\2]*\2/i', '', $content) ?? '';
        $content = preg_replace('/<(iframe|object|embed|base|meta|link)[^>]*>/i', '', $content) ?? '';

        $allowedTags = '<p><br><ul><ol><li><strong><b><em><i><u><h1><h2><h3><h4><h5><h6><blockquote><a>';
        $content = strip_tags($content, $allowedTags);

        $content = preg_replace_callback('/<a\b([^>]*)>/i', static function (array $matches): string {
            $attrs = $matches[1] ?? '';

            preg_match('/href\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attrs, $hrefMatch);
            $href = trim((string) ($hrefMatch[2] ?? $hrefMatch[3] ?? $hrefMatch[4] ?? ''));

            if ($href === '' || str_starts_with(strtolower($href), 'javascript:')) {
                return '<a>';
            }

            $safeHref = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');

            return '<a href="'.$safeHref.'" rel="nofollow noopener noreferrer">';
        }, $content) ?? '';

        return trim($content) === '' ? null : trim($content);
    }
}
