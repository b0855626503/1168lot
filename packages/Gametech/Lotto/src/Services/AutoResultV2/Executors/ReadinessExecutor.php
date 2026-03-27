<?php

namespace Gametech\Lotto\Services\AutoResultV2\Executors;

use Gametech\Lotto\Services\AutoResultV2\ConfigData\ReadinessConfigData;

class ReadinessExecutor
{
    /**
     * @param array<string,mixed> $normalized
     * @return array<string,mixed>
     */
    public function execute(array $normalized, ReadinessConfigData $config, bool $supportsPartial = false): array
    {
        $missing = [];
        foreach ($config->minimumRequiredKeys() as $field) {
            $value = $normalized[$field] ?? null;
            if ($this->isBlank($value)) {
                $missing[] = $field;
            }
        }

        if ($missing === []) {
            return [
                'ready' => true,
                'state' => 'READY',
                'supports_partial' => $supportsPartial,
                'missing_fields' => [],
            ];
        }

        if ($supportsPartial) {
            return [
                'ready' => false,
                'state' => 'PARTIAL',
                'supports_partial' => true,
                'missing_fields' => $missing,
                'error_code' => 'NOT_READY_PARTIAL_RESULT',
            ];
        }

        return [
            'ready' => false,
            'state' => 'NOT_READY',
            'supports_partial' => false,
            'missing_fields' => $missing,
            'error_code' => 'NOT_READY_BUSINESS_RULE',
        ];
    }

    private function isBlank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return $value === [];
        }

        if (is_object($value)) {
            return false;
        }

        return trim((string) $value) === '';
    }
}
