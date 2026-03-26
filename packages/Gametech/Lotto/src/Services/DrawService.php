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
    public const SOURCE_SCHEDULED = 'scheduled';
    public const SOURCE_MANUAL = 'manual';

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
                $this->openDraw($draw, [
                    'source' => self::SOURCE_SCHEDULED,
                    'actor_type' => 'system',
                ]);
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
                $this->closeDraw($draw, [
                    'source' => self::SOURCE_SCHEDULED,
                    'actor_type' => 'system',
                ]);
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

    /**
     * @param array{source:string,actor_id?:int,actor_type?:string,reason?:string} $context
     */
    public function openDraw(LottoDraw $draw, array $context): LottoDraw
    {
        return DB::transaction(function () use ($draw, $context) {
            $source = $this->resolveSource($context);
            $draw = LottoDraw::query()->lockForUpdate()->findOrFail($draw->id);

            if ((string) $draw->status === 'open') {
                return $draw->fresh(['betSettings']);
            }

            $this->assertCanOpen($draw);
            $now = now();

            if ($draw->open_at && $now->lt($draw->open_at)) {
                if ($source === self::SOURCE_SCHEDULED) {
                    throw new InvalidArgumentException('ยังไม่ถึงเวลาเปิดรับตามกำหนด');
                }
                $this->assertCanForceOpen();
            }

            if (! $draw->betSettings()->exists()) {
                $this->snapshotBetSettings($draw);
            }

            $draw->forceFill([
                'status' => 'open',
                'opened_at' => $now,
                'open_mode' => $source,
            ])->save();

            return $draw->fresh(['betSettings']);
        });
    }

    /**
     * @param array{source:string,actor_id?:int,actor_type?:string,reason?:string} $context
     */
    public function closeDraw(LottoDraw $draw, array $context): LottoDraw
    {
        return DB::transaction(function () use ($draw, $context) {
            $source = $this->resolveSource($context);
            $draw = LottoDraw::query()->lockForUpdate()->findOrFail($draw->id);

            if ((string) $draw->status === 'closed') {
                return $draw->fresh();
            }

            if ((string) $draw->status !== 'open') {
                throw new InvalidArgumentException('เฉพาะงวดที่เปิดรับอยู่เท่านั้นที่ปิดรับได้');
            }

            $now = now();
            if (! $draw->close_at) {
                if (! $this->allowManualCloseWithoutSchedule($source)) {
                    throw new InvalidArgumentException('ไม่พบเวลาปิดรับตามกำหนด จึงไม่สามารถปิดรับได้');
                }
            } elseif ($source === self::SOURCE_SCHEDULED && $now->lt($draw->close_at)) {
                throw new InvalidArgumentException('ยังไม่ถึงเวลาปิดรับตามกำหนด');
            }

            $draw->forceFill([
                'status' => 'closed',
                'closed_at' => $now,
                'close_mode' => $source,
            ])->save();

            return $draw->fresh();
        });
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
        if (! $draw->open_at) {
            throw new InvalidArgumentException('ไม่พบเวลาเปิดรับตามกำหนด');
        }

        if ((string) $draw->status === 'resulted') {
            throw new InvalidArgumentException('งวดที่ประกาศผลแล้วไม่สามารถเปิดรับได้');
        }

        if (! in_array((string) $draw->status, ['draft', 'closed'], true)) {
            throw new InvalidArgumentException('เฉพาะงวดร่างหรือปิดรับแล้วเท่านั้นที่เปิดรับได้');
        }
    }

    /**
     * @param array{source?:string} $context
     */
    private function resolveSource(array $context): string
    {
        $source = (string) ($context['source'] ?? '');
        if (! in_array($source, [self::SOURCE_SCHEDULED, self::SOURCE_MANUAL], true)) {
            throw new InvalidArgumentException('source ไม่ถูกต้อง');
        }

        return $source;
    }

    private function assertCanForceOpen(): void
    {
        if (! function_exists('bouncer')) {
            throw new InvalidArgumentException('ไม่สามารถตรวจสอบสิทธิ์ force open ได้');
        }

        if (! bouncer()->hasPermission('lotto_draws.force_open')) {
            throw new InvalidArgumentException('ไม่มีสิทธิ์เปิดรับก่อนเวลาที่กำหนด');
        }
    }

    private function allowManualCloseWithoutSchedule(string $source): bool
    {
        if ($source !== self::SOURCE_MANUAL) {
            return false;
        }

        return (bool) config('lotto.allow_manual_close_without_close_at', false);
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
