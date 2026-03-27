<?php

namespace Gametech\Lotto\Services\AutoResultV2\Compose;

use Gametech\Lotto\Services\AutoResultV2\Transform\TransformPipelineExecutor;
use Illuminate\Support\Carbon;

class ResultComposer
{
    public function __construct(
        private ?TransformPipelineExecutor $transformPipelineExecutor = null
    ) {
        $this->transformPipelineExecutor = $this->transformPipelineExecutor ?: new TransformPipelineExecutor();
    }

    /**
     * @param array<string,mixed> $selectedCandidate
     * @param array<string,mixed> $sourceConfig
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function compose(array $selectedCandidate, array $sourceConfig = [], array $context = []): array
    {
        $fields = $this->resolveFields($selectedCandidate);
        $mappingConfig = $this->resolveMappingConfig($sourceConfig, $context);

        $mapped = $this->transformPipelineExecutor->applyMap($fields, $mappingConfig, $context);
        $canonical = array_merge($fields, $mapped);

        foreach (['draw_id', 'market_id', 'source_id'] as $metaKey) {
            if (array_key_exists($metaKey, $context) && ! array_key_exists($metaKey, $canonical)) {
                $canonical[$metaKey] = $context[$metaKey];
            }
        }

        $canonical = $this->normalizeCanonicalOutcome($canonical, $context);
        $canonicalHash = hash('sha256', json_encode($this->sortRecursive($canonical), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return [
            'canonical_outcome' => $canonical,
            'canonical_hash' => $canonicalHash,
            'mapped_fields' => $mapped,
            'selected_fields' => $fields,
            'compose_debug' => [
                'mapping_config_keys' => array_keys($mappingConfig),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $selectedCandidate
     * @return array<string,mixed>
     */
    private function resolveFields(array $selectedCandidate): array
    {
        $fields = data_get($selectedCandidate, 'fields');
        if (is_array($fields)) {
            return $fields;
        }

        unset($selectedCandidate['candidate_debug'], $selectedCandidate['selection_debug']);

        return $selectedCandidate;
    }

    /**
     * @param array<string,mixed> $sourceConfig
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    private function resolveMappingConfig(array $sourceConfig, array $context): array
    {
        foreach (['mapping_config', 'mapping_config_json'] as $key) {
            $value = data_get($context, $key);
            if (is_array($value) && $value !== []) {
                return $this->unwrapMappingFields($value);
            }
        }

        foreach (['mapping_config_json', 'mapping_config', 'fields'] as $key) {
            $value = data_get($sourceConfig, $key);
            if (is_array($value) && $value !== []) {
                return $this->unwrapMappingFields($value);
            }
        }

        return [];
    }

    /**
     * @param array<string,mixed> $mappingConfig
     * @return array<string,mixed>
     */
    private function unwrapMappingFields(array $mappingConfig): array
    {
        $fields = data_get($mappingConfig, 'fields');
        if (is_array($fields) && $fields !== []) {
            return $fields;
        }

        return $mappingConfig;
    }

    /**
     * @param array<string,mixed> $canonical
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    private function normalizeCanonicalOutcome(array $canonical, array $context): array
    {
        foreach (['first_prize', 'last_2_digits'] as $field) {
            if (array_key_exists($field, $canonical) && $canonical[$field] !== null) {
                $scalar = $this->toScalarStringOrNull($canonical[$field]);
                if ($scalar === null) {
                    continue;
                }

                $canonical[$field] = preg_replace('/\D+/', '', $scalar);
            }
        }

        $dateField = (string) (data_get($context, 'expected_draw_date_field') ?: 'draw_date');
        if (array_key_exists($dateField, $canonical) && $canonical[$dateField] !== null) {
            $normalized = $this->normalizeDateMixed($canonical[$dateField]);
            if ($normalized !== null) {
                $canonical[$dateField] = $normalized;
            }
        }

        if (array_key_exists('draw_date', $canonical) && $canonical['draw_date'] !== null) {
            $normalized = $this->normalizeDateMixed($canonical['draw_date']);
            if ($normalized !== null) {
                $canonical['draw_date'] = $normalized;
            }
        }

        return $canonical;
    }

    private function normalizeDate(string $value): string
    {
        $raw = trim($value);
        if ($raw === '') {
            return $raw;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'Ymd'] as $format) {
            try {
                return Carbon::createFromFormat($format, $raw)->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $raw;
        }
    }

    private function normalizeDateMixed(mixed $value): ?string
    {
        $scalar = $this->toScalarStringOrNull($value);
        if ($scalar === null) {
            return null;
        }

        return $this->normalizeDate($scalar);
    }

    private function toScalarStringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return null;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function sortRecursive(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->sortRecursive($value);
            }
        }

        if ($this->isAssoc($payload)) {
            ksort($payload);
        }

        return $payload;
    }

    /**
     * @param array<mixed> $value
     */
    private function isAssoc(array $value): bool
    {
        if ($value === []) {
            return false;
        }

        return array_keys($value) !== range(0, count($value) - 1);
    }
}
