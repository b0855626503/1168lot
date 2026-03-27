<?php

namespace Gametech\Lotto\Services\AutoResultV2\Extractors;

class JsonPathExtractor
{
    /**
     * @param mixed $payload
     * @param array<string,mixed> $context
     * @return mixed
     */
    public function extract($payload, string $path, array $context = [])
    {
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        if ($path === '' || trim($path) === '$') {
            return $payload;
        }

        $normalized = $this->normalizePath($path);
        if ($normalized === '') {
            return $payload;
        }

        $value = data_get($payload, $normalized, $context['default'] ?? null);

        if (array_key_exists('default', $context) && ($value === null || $value === '')) {
            return $context['default'];
        }

        return $value;
    }

    /**
     * @param mixed $payload
     * @return array<int,mixed>
     */
    public function extractMany($payload, string $path, array $context = []): array
    {
        $context['multiple'] = true;
        $value = $this->extract($payload, $path, $context);

        if (! is_array($value)) {
            return $value === null ? [] : [$value];
        }

        return array_values($value);
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        $path = ltrim($path, '$');
        $path = ltrim($path, '.');
        $path = preg_replace('/\[(\d+|\*)\]/', '.$1', $path) ?? $path;
        $path = preg_replace('/\.\.+/', '.', $path) ?? $path;
        $path = trim($path, '.');

        return $path;
    }
}
