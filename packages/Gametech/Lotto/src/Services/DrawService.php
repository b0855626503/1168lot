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
    /**
     * Auto-sync draw statuses by schedule:
     * - draft + open_at <= now => open
     * - open + close_at <= now => closed
     *
     * @return array{opened:int,closed:int}
     */
    public function syncScheduledStatuses(): array
    {
        $opened = 0;
        $closed = 0;

        $dueOpenDraws = LottoDraw::query()
            ->where('status', 'draft')
            ->whereNotNull('open_at')
            ->where('open_at', '<=', now())
            ->orderBy('id')
            ->get();

        foreach ($dueOpenDraws as $draw) {
            try {
                $this->openDraw($draw);
                $opened++;
            } catch (\Throwable $exception) {
                // ignore one-off transition failure and continue with others
            }
        }

        $dueCloseDraws = LottoDraw::query()
            ->where('status', 'open')
            ->whereNotNull('close_at')
            ->where('close_at', '<=', now())
            ->orderBy('id')
            ->get();

        foreach ($dueCloseDraws as $draw) {
            try {
                $this->closeDraw($draw);
                $closed++;
            } catch (\Throwable $exception) {
                // ignore one-off transition failure and continue with others
            }
        }

        return [
            'opened' => $opened,
            'closed' => $closed,
        ];
    }

    public function createDraft(array $data): LottoDraw
    {
        return LottoDraw::query()->create($this->buildDraftPayload($data));
    }

    public function openDraw(LottoDraw $draw): LottoDraw
    {
        return DB::transaction(function () use ($draw) {
            $this->assertCanOpen($draw);

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
                'payout' => $setting->payout,
                'discount_percent' => $setting->discount_percent ?? 0,
                'is_enabled' => $setting->is_enabled,
                'min_bet' => $setting->min_bet,
                'max_bet' => $setting->max_bet,
                'max_per_number' => $setting->max_per_number,
            ]);
        }
    }

    private function assertCanOpen(LottoDraw $draw): void
    {
        if (! in_array($draw->status, ['draft', 'closed'], true)) {
            throw new InvalidArgumentException('Only draft or closed draws can be opened');
        }
    }

    private function buildDraftPayload(array $data): array
    {
        return [
            'market_id' => $data['market_id'],
            'draw_date' => $data['draw_date'],
            'open_at' => $data['open_at'],
            'close_at' => $data['close_at'],
            'result_at' => $data['result_at'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'created_by' => $data['created_by'] ?? null,
        ];
    }
}
