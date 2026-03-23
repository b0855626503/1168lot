<?php

namespace App\Services\Dashboard;

class LottoDashboardMetricConfig
{
    public const SECTION_CASH = 'lotto_cash';
    public const SECTION_PRODUCT = 'lotto_product';
    public const SECTION_RISK = 'lotto_risk';
    public const SECTION_OPERATIONS = 'lotto_operations';

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
        return 90;
    }
}

