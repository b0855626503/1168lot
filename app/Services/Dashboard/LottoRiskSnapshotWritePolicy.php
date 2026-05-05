<?php

namespace App\Services\Dashboard;

class LottoRiskSnapshotWritePolicy
{
    /**
     * @param  array<int, array<string, mixed>>  $riskRows
     * @param  array<string, mixed>  $context
     * @return array{allowed:bool, source:string, has_meaningful_risk:bool, reason:string}
     */
    public function evaluate(array $riskRows, array $context = []): array
    {
        $source = $this->normalizeSource((string) ($context['source'] ?? 'scheduled'));
        $manualAuditReason = $this->normalizeReason($context['reason'] ?? null);
        $hasMeaningfulRisk = $this->hasMeaningfulRisk($riskRows);
        $isAuditEvent = in_array($source, ['draw_closed', 'draw_resulted', 'manual_audit'], true);

        if ($source === 'manual_audit' && $manualAuditReason === null) {
            return [
                'allowed' => false,
                'source' => $source,
                'has_meaningful_risk' => $hasMeaningfulRisk,
                'reason' => 'manual_audit_missing_reason',
            ];
        }

        if (! $hasMeaningfulRisk && ! $isAuditEvent) {
            return [
                'allowed' => false,
                'source' => $source,
                'has_meaningful_risk' => false,
                'reason' => 'zero_risk_non_audit',
            ];
        }

        return [
            'allowed' => true,
            'source' => $source,
            'has_meaningful_risk' => $hasMeaningfulRisk,
            'reason' => $hasMeaningfulRisk ? 'meaningful_risk' : 'audit_event',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $riskRows
     * @param  array<string, mixed>  $context
     */
    public function shouldWriteRiskSnapshot(array $riskRows, array $context = []): bool
    {
        return $this->evaluate($riskRows, $context)['allowed'];
    }

    /**
     * @param  array<int, array<string, mixed>>  $riskRows
     */
    private function hasMeaningfulRisk(array $riskRows): bool
    {
        foreach ($riskRows as $row) {
            $stakeTotal = (float) ($row['stake_total'] ?? 0);
            $payoutIfHit = (float) ($row['payout_if_hit'] ?? 0);
            $liability = (float) ($row['liability'] ?? 0);

            if ($stakeTotal > 0 || $payoutIfHit > 0 || $liability > 0) {
                return true;
            }
        }

        return false;
    }

    private function normalizeSource(string $source): string
    {
        $normalized = strtolower(trim($source));

        return $normalized === '' ? 'scheduled' : $normalized;
    }

    private function normalizeReason(mixed $value): ?string
    {
        if (! is_scalar($value) || $value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
