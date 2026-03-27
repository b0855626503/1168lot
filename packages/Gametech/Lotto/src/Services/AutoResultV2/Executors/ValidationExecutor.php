<?php

namespace Gametech\Lotto\Services\AutoResultV2\Executors;

use Gametech\Lotto\Services\AutoResultV2\ConfigData\ValidationConfigData;

class ValidationExecutor
{
    /**
     * @param array<string,mixed> $mapped
     * @return array<string,mixed>
     */
    public function execute(array $mapped, ValidationConfigData $config): array
    {
        $missing = [];
        foreach ($config->requiredFields() as $field) {
            $value = $mapped[$field] ?? null;
            if ($value === null || trim((string) $value) === '') {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            return [
                'valid' => false,
                'error_code' => 'REQUIRED_FIELD_MISSING',
                'missing_fields' => $missing,
                'normalized' => $mapped,
            ];
        }

        return [
            'valid' => true,
            'error_code' => null,
            'missing_fields' => [],
            'normalized' => $mapped,
        ];
    }
}
