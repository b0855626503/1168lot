<?php

namespace Gametech\Lotto\Services;

use Exception;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoNumberBlock;
use Gametech\Lotto\Models\LottoRatePlan;
use Gametech\Lotto\Models\LottoRatePlanItem;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LottoTicketItem;
use Gametech\Lotto\Models\MemberLottoMarketPolicy;
use Gametech\Lotto\Models\MemberLottoPermission;
use Gametech\Lotto\Models\MemberLottoSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BetService - หัวใจของระบบแทง
 * ต้องใช้ transaction + lock เท่านั้น
 *
 * Validation Flow:
 * 1. เช็ค draw open
 * 2. เช็ค member permission
 * 3. เช็ค bet_type enabled
 * 4. เช็ค block number
 * 5. เช็ค min/max
 * 6. lock exposure
 * 7. เช็ค max_per_number
 * 8. insert ticket + item
 * 9. update exposure
 */
class BetService
{
    private const BLOCK_MODE_BLOCK = 'block';
    private const BLOCK_MODE_LIMIT_FUTURE = 'limit_future';

    public function __construct(private ExposureService $exposureService)
    {
    }

    /**
     * @param array<int, array{bet_type:string, number:string, amount:numeric}> $items
     * @throws Exception
     */
    public function placeBet(int $memberId, int $drawId, array $items): LottoTicket
    {
        return DB::transaction(function () use ($memberId, $drawId, $items) {
            $draw = $this->findOpenDraw($drawId);

            $this->validateMemberPermission($memberId, $draw);

            $validatedItems = [];
            $totalAmount = 0;

            foreach ($items as $item) {
                $validated = $this->validateAndPrepareItem(
                    $draw,
                    (string) ($item['bet_type'] ?? ''),
                    (string) ($item['number'] ?? ''),
                    (float) ($item['amount'] ?? 0),
                    $memberId
                );

                $validatedItems[] = $validated;
                $totalAmount += $validated['amount'];
            }

            $ticket = LottoTicket::query()->create([
                'member_id' => $memberId,
                'draw_id' => $drawId,
                'total_amount' => $totalAmount,
                'status' => 'active',
            ]);

            foreach ($validatedItems as $item) {
                $this->persistTicketItemAndExposure($ticket, $drawId, $item);
            }

            return $ticket->fresh('items');
        });
    }

    /**
     * @throws Exception
     */
    private function validateAndPrepareItem(
        LottoDraw $draw,
        string $betType,
        string $number,
        float $amount,
        int $memberId
    ): array {
        if (! in_array($betType, BetType::all(), true)) {
            throw new Exception("Invalid bet type: {$betType}");
        }

        $setting = $draw->betSettings
            ->where('bet_type', $betType)
            ->where('is_enabled', true)
            ->first();

        if (! $setting) {
            throw new Exception("Bet type {$betType} not enabled for this draw");
        }

        if (! $this->isBetAmountInRange($amount, (float) $setting->min_bet, (float) $setting->max_bet)) {
            throw new Exception(
                "Bet amount out of range. Min: {$setting->min_bet}, Max: {$setting->max_bet}"
            );
        }

        $blockMode = $this->resolveBlockMode($draw, $betType, $number);

        if ($blockMode === self::BLOCK_MODE_BLOCK) {
            throw new Exception("Number {$number} is blocked for this draw");
        }

        if ($blockMode === self::BLOCK_MODE_LIMIT_FUTURE) {
            throw new Exception("Number {$number} is blocked by future-limit rule");
        }

        return [
            'bet_type' => $betType,
            'number' => $number,
            'amount' => $amount,
            'payout' => $this->getPayout($draw, $betType, $memberId),
            'max_per_number' => (float) $setting->max_per_number,
        ];
    }

    /**
     * Policy-managed members use market snapshots; legacy members keep existing permission behavior.
     */
    private function validateMemberPermission(int $memberId, LottoDraw $draw): void
    {
        if ($this->isManagedByMarketPolicy($memberId)) {
            $hasMarketAccess = MemberLottoMarketPolicy::query()
                ->where('member_id', $memberId)
                ->where('market_id', (int) $draw->market_id)
                ->where('is_allowed', true)
                ->exists();

            if (! $hasMarketAccess) {
                throw new Exception('Member does not have permission to bet');
            }

            return;
        }

        $hasCustomRules = MemberLottoPermission::query()
            ->where('member_id', $memberId)
            ->exists();

        if (! $hasCustomRules) {
            return;
        }

        $hasPermission = MemberLottoPermission::query()
            ->where('member_id', $memberId)
            ->where('is_allowed', true)
            ->where(function ($query) use ($draw) {
                $query->whereNull('group_id')
                    ->orWhere('group_id', $draw->market->group_id);
            })
            ->exists();

        if (! $hasPermission) {
            throw new Exception('Member does not have permission to bet');
        }
    }

    private function isManagedByMarketPolicy(int $memberId): bool
    {
        if (! Schema::hasTable('member_lotto_market_policies')) {
            return false;
        }

        return MemberLottoMarketPolicy::query()
            ->where('member_id', $memberId)
            ->exists();
    }

    /**
     * Resolve member-specific or group-default payout.
     *
     * @throws Exception
     */
    private function getPayout(LottoDraw $draw, string $betType, int $memberId): float
    {
        $ratePlanId = MemberLottoSetting::query()
            ->where('member_id', $memberId)
            ->value('rate_plan_id');

        if (! $ratePlanId) {
            $ratePlanId = LottoRatePlan::query()
                ->where('group_id', $draw->market->group_id)
                ->where('is_enabled', true)
                ->orderBy('id')
                ->value('id');
        }

        if (! $ratePlanId) {
            throw new Exception('No rate plan found');
        }

        $item = LottoRatePlanItem::query()
            ->where('rate_plan_id', $ratePlanId)
            ->where('bet_type', $betType)
            ->first();

        if (! $item) {
            throw new Exception("No payout rate found for {$betType}");
        }

        return (float) $item->payout;
    }

    /**
     * block: ใช้เฉพาะงวดที่ตั้งค่าไว้
     * limit_future: ใช้กับงวดถัดไปและงวดต่อๆ ไป (ตลาดเดียวกัน)
     */
    private function resolveBlockMode(LottoDraw $draw, string $betType, string $number): ?string
    {
        $currentDrawDate = $draw->draw_date;

        if (! $currentDrawDate) {
            return null;
        }

        $record = LottoNumberBlock::query()
            ->where('bet_type', $betType)
            ->where('number', $number)
            ->where(function ($query) use ($draw, $currentDrawDate) {
                $query->where(function ($subQuery) use ($draw) {
                    $subQuery->where('mode', 'block')
                        ->where('draw_id', $draw->id);
                })->orWhere(function ($subQuery) use ($draw, $currentDrawDate) {
                    $subQuery->where('mode', 'limit_future')
                        ->whereHas('draw', function ($drawQuery) use ($draw, $currentDrawDate) {
                            $drawQuery->where('market_id', $draw->market_id)
                                ->whereDate('draw_date', '<', $currentDrawDate);
                        });
                });
            })
            ->orderByRaw("case when mode = 'block' then 0 else 1 end")
            ->first(['mode']);

        return $record?->mode;
    }

    /**
     * @throws Exception
     */
    private function findOpenDraw(int $drawId): LottoDraw
    {
        $draw = LottoDraw::query()
            ->with(['market', 'betSettings'])
            ->where('id', $drawId)
            ->where('status', 'open')
            ->first();

        if (! $draw) {
            throw new Exception('Draw not open or not found');
        }

        return $draw;
    }

    private function isBetAmountInRange(float $amount, float $min, float $max): bool
    {
        return $amount >= $min && $amount <= $max;
    }

    /**
     * @param array{bet_type:string,number:string,amount:float,payout:float,max_per_number:float} $item
     * @throws Exception
     */
    private function persistTicketItemAndExposure(LottoTicket $ticket, int $drawId, array $item): void
    {
        $exposure = $this->exposureService->lockExposureRow(
            $drawId,
            $item['bet_type'],
            $item['number']
        );

        if (((float) $exposure->sold_amount + $item['amount']) > $item['max_per_number']) {
            throw new Exception("Exposure limit reached for number {$item['number']}");
        }

        LottoTicketItem::query()->create([
            'ticket_id' => $ticket->id,
            'bet_type' => $item['bet_type'],
            'number' => $item['number'],
            'amount' => $item['amount'],
            'payout_at_time' => $item['payout'],
        ]);

        $exposure->increment('sold_amount', $item['amount']);
    }
}
