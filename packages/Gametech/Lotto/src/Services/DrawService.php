<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoDrawBetSetting;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Draw service.
 *
 * Important rule: draw settings are snapshotted from market settings.
 * Runtime betting must read from draw snapshots, never from market defaults.
 */
class DrawService
{
    public function createDraft(array $data): LottoDraw
    {
        return LottoDraw::query()->create([
            'market_id' => $data['market_id'],
            'draw_date' => $data['draw_date'],
            'open_at' => $data['open_at'],
            'close_at' => $data['close_at'],
            'result_at' => $data['result_at'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'created_by' => $data['created_by'] ?? null,
        ]);
    }

    public function openDraw(LottoDraw $draw): LottoDraw
    {
        return DB::transaction(function () use ($draw) {
            if (! in_array($draw->status, ['draft', 'closed'], true)) {
                throw new InvalidArgumentException('Only draft or closed draws can be opened');
            }

            if (! $draw->betSettings()->exists()) {
                $this->snapshotBetSettings($draw);
            }

            $draw->forceFill(['status' => 'open'])->save();

            return $draw->fresh(['betSettings']);
        });
    }

    public function closeDraw(LottoDraw $draw): LottoDraw
    {
        if ($draw->status !== 'open') {
            throw new InvalidArgumentException('Only open draws can be closed');
        }

        $draw->forceFill(['status' => 'closed'])->save();

        return $draw->fresh();
    }

    public function snapshotBetSettings(LottoDraw $draw): void
    {
        $market = LotteryMarket::query()->with('defaultBetSettings')->findOrFail($draw->market_id);

        foreach ($market->defaultBetSettings as $setting) {
            LottoDrawBetSetting::query()->updateOrCreate([
                'draw_id' => $draw->id,
                'bet_type' => $setting->bet_type,
            ], [
                'is_enabled' => $setting->is_enabled,
                'min_bet' => $setting->min_bet,
                'max_bet' => $setting->max_bet,
                'max_per_number' => $setting->max_per_number,
            ]);
        }
    }
}
