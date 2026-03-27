<?php

namespace Gametech\Lotto\Services\AutoResultV2\Extractors;

class RegexExtractor
{
    /**
     * @param array<string,mixed> $context
     * @return mixed
     */
    public function extract(string $subject, string $pattern, array $context = [])
    {
        $pattern = trim($pattern);
        if ($pattern === '') {
            return null;
        }

        $multiple = (bool) ($context['multiple'] ?? false);

        if ($multiple) {
            $matches = [];
            $ok = @preg_match_all($pattern, $subject, $matches, PREG_SET_ORDER);
            if ($ok === false || preg_last_error() !== PREG_NO_ERROR) {
                return [];
            }

            return array_map(static function (array $match): mixed {
                if (count($match) > 1) {
                    return $match[1];
                }

                return $match[0] ?? null;
            }, $matches);
        }

        $matches = [];
        if (@preg_match($pattern, $subject, $matches) !== 1) {
            return null;
        }

        return $matches[1] ?? ($matches[0] ?? null);
    }

    /**
     * @return array<int,mixed>
     */
    public function extractMany(string $subject, string $pattern, array $context = []): array
    {
        $context['multiple'] = true;
        $value = $this->extract($subject, $pattern, $context);
        if (! is_array($value)) {
            return $value === null ? [] : [$value];
        }

        return array_values($value);
    }
}
