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
        $noResultReason = $this->detectNoResultReason($mapped);
        if ($noResultReason !== null) {
            $normalized = $mapped;
            $normalized['first_prize'] = '';
            $normalized['last_2_digits'] = '';
            $normalized['no_result'] = true;
            $normalized['no_result_reason'] = $noResultReason;

            return [
                'valid' => true,
                'error_code' => null,
                'missing_fields' => [],
                'normalized' => $normalized,
            ];
        }

        $missing = [];
        foreach ($config->requiredFields() as $field) {
            $value = $mapped[$field] ?? null;
            if ($this->isBlank($value)) {
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

    /**
     * @param array<string,mixed> $payload
     */
    private function detectNoResultReason(array $payload): ?string
    {
        foreach ($payload as $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $text = trim((string) $value);
            if ($text === '') {
                continue;
            }

            $normalized = mb_strtolower(preg_replace('/\s+/', '', $text));
            foreach ([
                'งดออกผล',
                'งดออก',
                'ไม่ออกผล',
                'noresult',
                'cancelled',
                'canceled',
                'cancel',
                'void',
            ] as $marker) {
                if (str_contains($normalized, $marker)) {
                    return 'งดออกผล';
                }
            }
        }

        return null;
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
