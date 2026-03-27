<?php

namespace Gametech\Lotto\Services\AutoResultV2;

class ShadowCompareService
{
    /**
     * Deterministic compare for canonical outcome only.
     *
     * @param array<string,mixed> $legacy
     * @param array<string,mixed> $v2
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function compare(array $legacy, array $v2, array $context = []): array
    {
        $legacyCanonical = $this->extractCanonicalOutcome($legacy);
        $v2Canonical = $this->extractCanonicalOutcome($v2);

        $legacyNormalized = $this->normalize($legacyCanonical);
        $v2Normalized = $this->normalize($v2Canonical);

        $mismatches = $this->diff($legacyNormalized, $v2Normalized);
        $status = $mismatches === []
            ? PipelineRunTrace::SHADOW_COMPARE_MATCH
            : PipelineRunTrace::SHADOW_COMPARE_MISMATCH;

        return [
            'policy' => 'canonical_outcome_only',
            'shadow_compare_status' => $status,
            'matched' => $mismatches === [],
            'legacy_hash' => $this->hash($legacyNormalized),
            'v2_hash' => $this->hash($v2Normalized),
            'legacy_canonical' => $legacyNormalized,
            'v2_canonical' => $v2Normalized,
            'mismatches' => $mismatches,
            'reason_codes' => $this->reasonCodes($mismatches),
            'context' => $context,
        ];
    }

    /**
     * @param array<string,mixed> $legacy
     * @param array<string,mixed> $v2
     * @return array<string,mixed>
     */
    public function diff(array $legacy, array $v2): array
    {
        $diffs = [];
        $paths = array_unique(array_merge(array_keys($legacy), array_keys($v2)));
        sort($paths, SORT_STRING);

        foreach ($paths as $key) {
            $path = (string) $key;
            $left = $legacy[$key] ?? null;
            $right = $v2[$key] ?? null;
            $this->collectDiffs($diffs, $path, $left, $right);
        }

        return $diffs;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function extractCanonicalOutcome(array $payload): array
    {
        foreach (['canonical_outcome', 'canonical', 'outcome', 'validated'] as $key) {
            $value = $payload[$key] ?? null;
            if (is_array($value)) {
                return $value;
            }
        }

        $exclude = ['status', 'error_message', 'draw_id', 'market_id', 'source_id', 'request', 'fetch', 'extract', 'selection', 'compose', 'validation', 'readiness', 'shadow_compare', 'context', 'debug'];
        $out = [];
        foreach ($payload as $key => $value) {
            if (in_array((string) $key, $exclude, true) || str_starts_with((string) $key, '_')) {
                continue;
            }
            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function normalize($value)
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalize($item);
            }

            if ($this->isAssoc($normalized)) {
                ksort($normalized);
            }

            return $normalized;
        }

        if (is_string($value)) {
            return trim($value);
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value) || $value === null) {
            return $value;
        }

        return (string) $value;
    }

    /**
     * @param array<int,array<string,mixed>> $diffs
     * @param mixed $left
     * @param mixed $right
     */
    private function collectDiffs(array &$diffs, string $path, $left, $right): void
    {
        if (is_array($left) || is_array($right)) {
            $leftArray = is_array($left) ? $left : [];
            $rightArray = is_array($right) ? $right : [];
            $keys = array_unique(array_merge(array_keys($leftArray), array_keys($rightArray)));
            sort($keys, SORT_STRING);

            foreach ($keys as $key) {
                $nextPath = $path === '' ? (string) $key : $path . '.' . $key;
                $this->collectDiffs($diffs, $nextPath, $leftArray[$key] ?? null, $rightArray[$key] ?? null);
            }

            return;
        }

        if ($this->scalarEquals($left, $right)) {
            return;
        }

        $diffs[] = [
            'path' => $path,
            'legacy' => $left,
            'v2' => $right,
        ];
    }

    private function scalarEquals($left, $right): bool
    {
        if (is_string($left)) {
            $left = trim($left);
        }
        if (is_string($right)) {
            $right = trim($right);
        }

        return $left === $right || (string) $left === (string) $right;
    }

    /**
     * @param array<int,array<string,mixed>> $mismatches
     * @return array<int,string>
     */
    private function reasonCodes(array $mismatches): array
    {
        if ($mismatches === []) {
            return ['NO_DIFF'];
        }

        $codes = [];
        foreach ($mismatches as $item) {
            $path = (string) ($item['path'] ?? '');
            if ($path === 'final_decision') {
                $codes[] = 'FINAL_DECISION_DIFF';
                continue;
            }
            if ($path === 'error_code') {
                $codes[] = 'ERROR_CODE_DIFF';
                continue;
            }
            if (str_starts_with($path, 'readiness_result.state')) {
                $codes[] = 'READINESS_STATE_DIFF';
                continue;
            }

            $codes[] = 'CANONICAL_FIELD_DIFF';
        }

        return array_values(array_unique($codes));
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function hash($value): string
    {
        return hash('sha256', json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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
