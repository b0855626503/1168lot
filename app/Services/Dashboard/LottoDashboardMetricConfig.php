<?php

namespace App\Services\Dashboard;

class LottoDashboardMetricConfig
{
    public const SECTION_CASH = 'lotto_cash';
    public const SECTION_PRODUCT = 'lotto_product';
    public const SECTION_RISK = 'lotto_risk';
    public const SECTION_OPERATIONS = 'lotto_operations';
    public const SECTION_BET_TYPE_INSIGHTS = 'lotto_bet_type_insights';

    /**
     * Canonical status exclusions for lotto insights projector.
     */
    public const LOTTO_INSIGHT_EXCLUDED_TICKET_STATUSES = [
        'cancelled',
        'voided',
        'deleted',
    ];

    /**
     * Keep item-level exclusion explicit even if schema currently has no item status.
     */
    public const LOTTO_INSIGHT_EXCLUDED_ITEM_STATUSES = [
        'cancelled',
        'voided',
        'deleted',
    ];

    public const WALLET_SUCCESS_STATUS = 'SUCCESS';

    /**
     * @return string[]
     */
    public static function salesRefTypes(): array
    {
        return ['LOTTO_BET'];
    }

    /**
     * @return string[]
     */
    public static function payoutRefTypes(): array
    {
        return ['LOTTO_SETTLE_WIN', 'LOTTO_PAYOUT'];
    }

    /**
     * @return string[]
     */
    public static function refundRefTypes(): array
    {
        return ['LOTTO_CANCEL'];
    }

    public static function riskSnapshotRetentionDays(): int
    {
        $days = (int) config('dashboard.lotto.risk_snapshot_retention_days', 7);

        if ($days < 1 || $days > 90) {
            return 7;
        }

        return $days;
    }
}
