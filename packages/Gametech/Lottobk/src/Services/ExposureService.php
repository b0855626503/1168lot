<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoNumberExposure;
use Illuminate\Database\QueryException;

/**
 * ExposureService - ยอดสะสมต่อเลข
 * ต้องใช้ atomic transaction เท่านั้น
 */
class ExposureService
{
    /**
     * Ensure a row exists, then lock it for update.
     */
    public function lockExposureRow(int $drawId, string $betType, string $number): LottoNumberExposure
    {
        try {
            LottoNumberExposure::query()->firstOrCreate([
                'draw_id' => $drawId,
                'bet_type' => $betType,
                'number' => $number,
            ], [
                'sold_amount' => 0,
            ]);
        } catch (QueryException $exception) {
            // Concurrent insert hit the unique key; lock the winner row below.
        }

        return $this->exposureQuery($drawId, $betType, $number)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Check if number can accept more bets.
     */
    public function checkLimit(int $drawId, string $betType, string $number, float $amount): bool
    {
        $exposure = $this->lockExposureRow($drawId, $betType, $number);

        $setting = $this->findBetSetting($drawId, $betType);

        if (! $setting) {
            return false;
        }

        $newTotal = (float) $exposure->sold_amount + $amount;

        return $newTotal <= (float) $setting->max_per_number;
    }

    public function getExposure(int $drawId, string $betType, string $number): ?float
    {
        $value = $this->exposureQuery($drawId, $betType, $number)->value('sold_amount');

        return is_null($value) ? null : (float) $value;
    }

    public function getDrawExposures(int $drawId, ?string $betType = null): array
    {
        $query = LottoNumberExposure::query()->where('draw_id', $drawId);

        if ($betType) {
            $query->where('bet_type', $betType);
        }

        return $query->get()->keyBy('number')->toArray();
    }

    public function getExposureReport(int $drawId): array
    {
        return LottoNumberExposure::query()
            ->where('draw_id', $drawId)
            ->get()
            ->groupBy('bet_type')
            ->map(static fn ($items) => $items->pluck('sold_amount', 'number'))
            ->toArray();
    }

    private function exposureQuery(int $drawId, string $betType, string $number)
    {
        return LottoNumberExposure::query()
            ->where('draw_id', $drawId)
            ->where('bet_type', $betType)
            ->where('number', $number);
    }

    private function findBetSetting(int $drawId, string $betType)
    {
        return LottoDraw::query()
            ->findOrFail($drawId)
            ->betSettings()
            ->where('bet_type', $betType)
            ->first();
    }
}
