<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoNumberExposure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
     * @param  array<int, array{bet_type:string, number:string}>  $items
     * @return Collection<string, LottoNumberExposure>
     */
    public function lockExposureRows(int $drawId, array $items): Collection
    {
        $normalizedItems = collect($items)
            ->map(function (array $item): array {
                return [
                    'bet_type' => (string) $item['bet_type'],
                    'number' => (string) $item['number'],
                ];
            })
            ->unique(static fn (array $item): string => $item['bet_type'].'|'.$item['number'])
            ->values();

        if ($normalizedItems->isEmpty()) {
            return collect();
        }

        DB::table('lotto_number_exposures')->insertOrIgnore(
            $normalizedItems
                ->map(function (array $item) use ($drawId): array {
                    return [
                        'draw_id' => $drawId,
                        'bet_type' => $item['bet_type'],
                        'number' => $item['number'],
                        'sold_amount' => 0,
                    ];
                })
                ->all()
        );

        return LottoNumberExposure::query()
            ->where('draw_id', $drawId)
            ->where(function ($query) use ($normalizedItems): void {
                foreach ($normalizedItems as $item) {
                    $query->orWhere(function ($subQuery) use ($item): void {
                        $subQuery->where('bet_type', $item['bet_type'])
                            ->where('number', $item['number']);
                    });
                }
            })
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (LottoNumberExposure $exposure): string => $this->exposureKey(
                (string) $exposure->bet_type,
                (string) $exposure->number
            ));
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

    private function exposureKey(string $betType, string $number): string
    {
        return $betType.'|'.$number;
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
