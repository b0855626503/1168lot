<?php

namespace Gametech\Lotto\Services;

use Exception;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Exceptions\LottoPackageException;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoNumberBlock;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LottoTicketItem;
use Gametech\Lotto\Models\MemberLottoMarketPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BetService - หัวใจของระบบแทง
 * ต้องใช้ transaction + lock เท่านั้น
 *
 * Validation Flow:
 * 1. load draw
 * 2. เช็ค draw open
 * 3. เช็ค member permission
 * 4. เช็ค market active
 * 5. เช็ค bet_type enabled
 * 6. เช็ค block number
 * 7. เช็ค min/max
 * 8. lock exposure
 * 9. เช็ค max_per_number
 * 10. insert ticket + item
 * 11. update exposure
 */
class BetService
{
    private const BLOCK_MODE_BLOCK = 'block';
    private const BLOCK_MODE_LIMIT_FUTURE = 'limit_future';

    public function __construct(
        private ExposureService $exposureService,
        private LottoConfigResolver $configResolver,
        private LottoPackageResolver $packageResolver,
        private WalletTransactionService $walletTransactionService
    ) {}

    /**
     * @param array<int, array{bet_type:string, number:string, amount:numeric}> $items
     * @throws Exception
     */
    public function placeBet(int $memberId, int $drawId, int $packageId, array $items): LottoTicket
    {
        return DB::transaction(function () use ($memberId, $drawId, $packageId, $items) {
            if ($packageId <= 0) {
                throw LottoPackageException::packageRequired();
            }

            $draw = $this->findDraw($drawId);

            $this->validateDrawIsOpen($draw);

            $this->validateMemberPermission($memberId, $draw);
            $this->validateMarketActive($draw);

            $validatedItems = [];
            $totalBetAmount = 0.0;
            $totalDiscountAmount = 0.0;
            $totalNetAmount = 0.0;

            foreach ($items as $item) {
                $validated = $this->validateAndPrepareItem(
                    $draw,
                    $packageId,
                    (string) ($item['bet_type'] ?? ''),
                    (string) ($item['number'] ?? ''),
                    (float) ($item['amount'] ?? 0)
                );

                $validatedItems[] = $validated;
                $totalBetAmount += $validated['amount'];
                $totalDiscountAmount += $validated['discount_amount'];
                $totalNetAmount += $validated['payable_amount'];
            }

            $betTypeSummary = $this->buildBetTypeSummary($validatedItems);

            $ticket = LottoTicket::query()->create([
                'member_id' => $memberId,
                'draw_id' => $drawId,
                'total_amount' => round($totalNetAmount, 2),
                'total_bet_amount' => round($totalBetAmount, 2),
                'total_discount_amount' => round($totalDiscountAmount, 2),
                'total_net_amount' => round($totalNetAmount, 2),
                'total_win_amount' => 0,
                'status' => 'active',
                'bet_type_summary' => $betTypeSummary,
            ]);

            $groupCode = 'LOTTO_BET_' . $ticket->id . '_' . now()->format('YmdHis');
            $walletTxnId = $this->walletTransactionService->debitMemberBalance(
                memberId: $memberId,
                amount: (float) round($totalNetAmount, 2),
                refType: 'LOTTO_BET',
                refId: (int) $ticket->id,
                refCode: (string) $ticket->id,
                groupCode: $groupCode,
                meta: [
                    'draw_id' => $drawId,
                    'ticket_id' => (int) $ticket->id,
                    'item_count' => count($validatedItems),
                ],
                createdByType: 'member',
                createdById: $memberId,
                description: 'หักเงินจากการซื้อหวย'
            );

            if (Schema::hasColumn('lotto_tickets', 'bet_confirmed_at')) {
                $betConfirmedAt = DB::table('wallet_transactions')
                    ->where('id', $walletTxnId)
                    ->value('created_at');

                $ticket->update([
                    'bet_confirmed_at' => $betConfirmedAt ?: now(),
                ]);
            }

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
        int $packageId,
        string $betType,
        string $number,
        float $amount
    ): array {
        if (! in_array($betType, BetType::all(), true)) {
            throw new Exception("Invalid bet type: {$betType}");
        }

        $setting = $this->configResolver->resolveDrawSnapshot($draw, $betType);

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

        $packagePayload = $this->packageResolver->resolveForBet($draw, $packageId, $betType);
        $package = $packagePayload['package'];
        $packageSetting = $packagePayload['setting'];

        $discountPercent = $this->normalizeDiscountPercent((float) ($packageSetting->discount_percent ?? 0));
        $payout = (float) $packageSetting->payout;

        $discountAmount = $this->calculateDiscountAmount($amount, $discountPercent);
        $payableAmount = $this->calculatePayableAmount($amount, $discountAmount);
        $potentialWinAmount = $this->calculatePotentialWinAmount($amount, $payout);

        return [
            'bet_type' => $betType,
            'number' => $number,
            'amount' => $amount,
            'package_id' => (int) $package->id,
            'package_name' => (string) $package->name,
            'payout' => $payout,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'payable_amount' => $payableAmount,
            'potential_win_amount' => $potentialWinAmount,
            'calculated_values_at_bet_time' => [
                'bet_amount' => round($amount, 2),
                'discount_amount' => $discountAmount,
                'net_amount' => $payableAmount,
                'payout_amount' => $potentialWinAmount,
            ],
            'max_per_number' => (float) $setting->max_per_number,
        ];
    }

    private function validateMemberPermission(int $memberId, LottoDraw $draw): void
    {
        $hasPermission = MemberLottoMarketPolicy::query()
            ->where('member_id', $memberId)
            ->where('market_id', (int) $draw->market_id)
            ->where('is_allowed', true)
            ->exists();

        if (! $hasPermission) {
            throw new Exception('Member does not have permission to bet');
        }
    }

    private function validateMarketActive(LottoDraw $draw): void
    {
        if (! $draw->market || ! (bool) $draw->market->is_enabled) {
            throw new Exception('Market is not active');
        }

        if (! $draw->market->group || ! (bool) $draw->market->group->is_enabled) {
            throw new Exception('Group is not active');
        }
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
    private function findDraw(int $drawId): LottoDraw
    {
        $draw = LottoDraw::query()
            ->with(['market.group', 'betSettings'])
            ->where('id', $drawId)
            ->first();

        if (! $draw) {
            throw new Exception('Draw not found');
        }

        return $draw;
    }

    /**
     * @throws Exception
     */
    private function validateDrawIsOpen(LottoDraw $draw): void
    {
        if ($draw->status !== 'open') {
            throw new Exception('Draw not open or not found');
        }
    }

    private function isBetAmountInRange(float $amount, float $min, float $max): bool
    {
        return $amount >= $min && $amount <= $max;
    }

    /**
     * @param array{
     *  bet_type:string,
     *  number:string,
     *  amount:float,
     *  package_id:int,
     *  package_name:string,
     *  payout:float,
     *  discount_percent:float,
     *  discount_amount:float,
     *  payable_amount:float,
     *  potential_win_amount:float,
     *  calculated_values_at_bet_time:array{
     *    bet_amount:float,
     *    discount_amount:float,
     *    net_amount:float,
     *    payout_amount:float
     *  },
     *  max_per_number:float
     * } $item
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
            'package_id_at_time' => $item['package_id'],
            'package_name_at_time' => $item['package_name'],
            'payout_at_time' => $item['payout'],
            'discount_percent_at_time' => $item['discount_percent'],
            'discount_amount_at_time' => $item['discount_amount'],
            'payable_amount_at_time' => $item['payable_amount'],
            'potential_win_amount_at_time' => $item['potential_win_amount'],
            'calculated_values_at_bet_time' => $item['calculated_values_at_bet_time'],
        ]);

        $exposure->increment('sold_amount', $item['amount']);
    }

    private function normalizeDiscountPercent(float $discountPercent): float
    {
        if ($discountPercent < 0) {
            return 0.0;
        }

        if ($discountPercent > 100) {
            return 100.0;
        }

        return round($discountPercent, 2);
    }

    private function calculateDiscountAmount(float $amount, float $discountPercent): float
    {
        return round($amount * ($discountPercent / 100), 2);
    }

    private function calculatePayableAmount(float $amount, float $discountAmount): float
    {
        return round(max(0, $amount - $discountAmount), 2);
    }

    private function calculatePotentialWinAmount(float $amount, float $payout): float
    {
        return round($amount * $payout, 2);
    }

    /**
     * @param array<int, array{bet_type:string}> $validatedItems
     */
    private function buildBetTypeSummary(array $validatedItems): string
    {
        $labels = collect($validatedItems)
            ->pluck('bet_type')
            ->filter()
            ->map(static fn (string $type): string => BetType::label($type))
            ->unique()
            ->values();

        if ($labels->isEmpty()) {
            return '';
        }

        return mb_substr($labels->implode(', '), 0, 255);
    }
}
