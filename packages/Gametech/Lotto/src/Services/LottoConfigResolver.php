<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoDrawBetSetting;
use Gametech\Lotto\Models\LottoMarketBetSetting;

class LottoConfigResolver
{
    public function resolveDrawSnapshot(LottoDraw $draw, string $betType): ?LottoDrawBetSetting
    {
        return $draw->betSettings
            ->where('bet_type', $betType)
            ->where('is_enabled', true)
            ->first();
    }

    public function resolveMarketSetting(int $marketId, string $betType): ?LottoMarketBetSetting
    {
        return LottoMarketBetSetting::query()
            ->where('market_id', $marketId)
            ->where('bet_type', $betType)
            ->where('is_enabled', true)
            ->first();
    }

    public function resolvePayout(LottoDraw $draw, string $betType): ?float
    {
        $snapshot = $this->resolveDrawSnapshot($draw, $betType);
        if ($snapshot) {
            return (float) $snapshot->payout;
        }

        $marketSetting = $this->resolveMarketSetting((int) $draw->market_id, $betType);
        if (! $marketSetting) {
            return null;
        }

        return (float) $marketSetting->payout;
    }

    public function resolveLimits(LottoDraw $draw, string $betType): ?array
    {
        $snapshot = $this->resolveDrawSnapshot($draw, $betType);
        if ($snapshot) {
            return [
                'min_bet' => (float) $snapshot->min_bet,
                'max_bet' => (float) $snapshot->max_bet,
                'max_per_number' => (float) $snapshot->max_per_number,
            ];
        }

        $marketSetting = $this->resolveMarketSetting((int) $draw->market_id, $betType);
        if (! $marketSetting) {
            return null;
        }

        return [
            'min_bet' => (float) $marketSetting->min_bet,
            'max_bet' => (float) $marketSetting->max_bet,
            'max_per_number' => (float) $marketSetting->max_per_number,
        ];
    }
}

