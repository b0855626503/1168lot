<?php

namespace Gametech\Lotto\Services\AutoResultV2\Transform;

class TransformPipelineExecutor
{
    public function __construct(
        private ?TransformRegistry $registry = null
    ) {
        $this->registry = $this->registry ?: new TransformRegistry();
    }

    /**
     * @param mixed $value
     * @param array<int,mixed> $transforms
     * @param array<string,mixed> $context
     * @return mixed
     */
    public function execute($value, array $transforms, array $context = [])
    {
        $cursor = $value;
        foreach ($transforms as $transform) {
            $cursor = $this->applyOne($cursor, $transform, $context);
        }

        return $cursor;
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $mappingConfig
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function applyMap(array $payload, array $mappingConfig, array $context = []): array
    {
        if ($mappingConfig === []) {
            return $payload;
        }

        $normalized = [];
        foreach ($mappingConfig as $field => $rule) {
            if (! is_string($field) || trim($field) === '') {
                continue;
            }

            $normalized[$field] = $this->resolveRule($payload, $rule, $context);
        }

        return $normalized;
    }

    /**
     * @param mixed $value
     * @param mixed $transform
     * @param array<string,mixed> $context
     * @return mixed
     */
    private function applyOne($value, $transform, array $context)
    {
        if ($value === null) {
            return null;
        }

        if (is_string($transform)) {
            return $this->applyStringTransform($value, $transform, $context);
        }

        if (is_array($transform)) {
            $op = strtolower((string) ($transform['op'] ?? ''));
            $name = (string) ($transform['name'] ?? $op);
            if ($op === '' && $name === '') {
                return $value;
            }

            $config = $transform;
            unset($config['op'], $config['name']);

            if ($op !== '' && $this->registry->has($op)) {
                return $this->registry->apply($op, $value, $config);
            }

            if ($name !== '' && $this->registry->has($name)) {
                return $this->registry->apply($name, $value, $config);
            }
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @param array<string,mixed> $context
     * @return mixed
     */
    private function applyStringTransform($value, string $transform, array $context)
    {
        $normalized = strtolower(trim($transform));
        if ($normalized === '') {
            return $value;
        }

        if (str_starts_with($normalized, 'left:')) {
            return $this->registry->apply('left', $value, ['length' => (int) substr($normalized, 5)]);
        }

        if (str_starts_with($normalized, 'right:')) {
            return $this->registry->apply('right', $value, ['length' => (int) substr($normalized, 6)]);
        }

        if (str_starts_with($normalized, 'date:')) {
            $parts = array_values(array_filter(explode(':', $transform), static fn ($part) => $part !== ''));
            return $this->registry->apply('date', $value, [
                'from' => $parts[1] ?? ($context['from'] ?? ''),
                'to' => $parts[2] ?? ($context['to'] ?? 'Y-m-d'),
            ]);
        }

        return $this->registry->apply($normalized, $value, $context);
    }

    /**
     * @param array<string,mixed> $payload
     * @param mixed $rule
     * @param array<string,mixed> $context
     * @return mixed
     */
    private function resolveRule(array $payload, $rule, array $context)
    {
        if (is_string($rule)) {
            return data_get($payload, $rule);
        }

        if (! is_array($rule)) {
            return $rule;
        }

        if (array_key_exists('case_when', $rule) && is_array($rule['case_when'])) {
            $value = $this->resolveCaseWhen($payload, $rule['case_when'], $rule['else'] ?? null);
        } elseif (array_key_exists('map_value', $rule) && is_array($rule['map_value'])) {
            $sourcePath = (string) ($rule['from'] ?? $rule['path'] ?? $rule['field'] ?? '');
            $sourceValue = $sourcePath !== '' ? data_get($payload, $sourcePath) : null;
            $map = $rule['map_value'];
            $value = $map[(string) $sourceValue] ?? ($rule['default'] ?? null);
        } elseif (array_key_exists('template', $rule) && is_string($rule['template'])) {
            $value = $this->resolveTemplate($payload, (string) $rule['template']);
        } elseif (array_key_exists('coalesce', $rule) && is_array($rule['coalesce'])) {
            $value = null;
            foreach ($rule['coalesce'] as $candidatePath) {
                $candidate = data_get($payload, (string) $candidatePath);
                if ($candidate !== null && trim((string) $candidate) !== '') {
                    $value = $candidate;
                    break;
                }
            }
        } elseif (array_key_exists('from_fields', $rule) && is_array($rule['from_fields']) && $rule['from_fields'] !== []) {
            $items = [];
            foreach ($rule['from_fields'] as $path) {
                $items[] = data_get($payload, (string) $path);
            }
            if (isset($rule['join'])) {
                $separator = is_string($rule['join']) ? $rule['join'] : (string) ($rule['join']['separator'] ?? '');
                $value = implode($separator, array_map(static fn ($item) => (string) $item, $items));
            } else {
                $value = $items;
            }
        } elseif (array_key_exists('join', $rule) && is_array($rule['join'])) {
            $fields = is_array($rule['join']['fields'] ?? null) ? $rule['join']['fields'] : [];
            $separator = (string) ($rule['join']['separator'] ?? '');
            $value = implode($separator, array_map(static function ($path) use ($payload): string {
                return (string) data_get($payload, (string) $path);
            }, $fields));
        } elseif (array_key_exists('value', $rule)) {
            $value = $rule['value'];
        } else {
            $path = (string) ($rule['from'] ?? $rule['path'] ?? $rule['field'] ?? '');
            $value = $path !== '' ? data_get($payload, $path) : null;
        }

        $fallback = $rule['default'] ?? null;
        if (($value === null || $value === '') && $fallback !== null) {
            $value = $fallback;
        }

        $transforms = $rule['transforms'] ?? $rule['transform'] ?? [];
        if (is_string($transforms)) {
            $transforms = [$transforms];
        }
        if (! is_array($transforms)) {
            $transforms = [];
        }

        $transformContext = $context;
        foreach (['from', 'to', 'format', 'length', 'search', 'replace'] as $key) {
            if (array_key_exists($key, $rule)) {
                $transformContext[$key] = $rule[$key];
            }
        }

        return $this->execute($value, array_values($transforms), $transformContext);
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<int,mixed> $cases
     * @return mixed
     */
    private function resolveCaseWhen(array $payload, array $cases, mixed $elseValue = null)
    {
        foreach ($cases as $case) {
            if (! is_array($case)) {
                continue;
            }
            $path = (string) ($case['field'] ?? $case['from'] ?? '');
            $operator = strtolower((string) ($case['op'] ?? 'eq'));
            $expected = $case['value'] ?? null;
            $actual = $path !== '' ? data_get($payload, $path) : null;

            $matched = match ($operator) {
                'eq', '==' => (string) $actual === (string) $expected,
                'neq', '!=' => (string) $actual !== (string) $expected,
                'in' => is_array($expected) && in_array((string) $actual, array_map('strval', $expected), true),
                default => false,
            };

            if ($matched) {
                return $case['then'] ?? null;
            }
        }

        return $elseValue;
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function resolveTemplate(array $payload, string $template): string
    {
        return preg_replace_callback('/\\{([^}]+)\\}/', static function (array $matches) use ($payload): string {
            $path = trim((string) ($matches[1] ?? ''));

            return (string) data_get($payload, $path, '');
        }, $template) ?? $template;
    }
}
