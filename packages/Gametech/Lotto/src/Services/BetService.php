<?php

namespace Gametech\Lotto\Services;

use Exception;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Exceptions\LottoPackageException;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoNumberBlock;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LottoTicketItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BetService - หัวใจของระบบแทง
 * ต้องใช้ transaction + lock เท่านั้น
 *
 * Validation Flow:
 * 1. load draw
 * 2. เช็ค draw open
 * 3. เช็ค member permission (blacklist: blocks only if is_allowed=false row exists)
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
    private const TICKET_ITEM_INSERT_CHUNK_SIZE = 200;
    private static ?bool $hasBetConfirmedAtColumn = null;

    public function __construct(
        private ExposureService $exposureService,
        private LottoConfigResolver $configResolver,
        private LottoPackageResolver $packageResolver,
        private WalletTransactionService $walletTransactionService,
        private MemberMarketPolicyService $policyService
    ) {}

    /**
     * @param  array<int, array{bet_type:string, number:string, amount:numeric}>  $items
     *
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
            $blockModeCache = $this->preloadBlockModes($draw, $items);
            $packagePayloadCache = [];

            foreach ($items as $item) {
                $validated = $this->validateAndPrepareItem(
                    $draw,
                    $packageId,
                    (string) ($item['bet_type'] ?? ''),
                    (string) ($item['number'] ?? ''),
                    (float) ($item['amount'] ?? 0),
                    $blockModeCache,
                    $packagePayloadCache
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

            $groupCode = 'LOTTO_BET_'.$ticket->id.'_'.now()->format('YmdHis');
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

            if ($this->supportsBetConfirmedAtColumn()) {
                $betConfirmedAt = DB::table('wallet_transactions')
                    ->where('id', $walletTxnId)
                    ->value('created_at');

                $ticket->update([
                    'bet_confirmed_at' => $betConfirmedAt ?: now(),
                ]);
            }

            $this->persistTicketItemsAndExposure($ticket, $drawId, $validatedItems);

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
        float $amount,
        array &$blockModeCache,
        array &$packagePayloadCache
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

        $blockCacheKey = $this->exposureKey($betType, $number);
        if (! array_key_exists($blockCacheKey, $blockModeCache)) {
            $blockModeCache[$blockCacheKey] = $this->resolveBlockMode($draw, $betType, $number);
        }

        $blockMode = $blockModeCache[$blockCacheKey];

        if ($blockMode === self::BLOCK_MODE_BLOCK) {
            throw new Exception("Number {$number} is blocked for this draw");
        }

        if ($blockMode === self::BLOCK_MODE_LIMIT_FUTURE) {
            throw new Exception("Number {$number} is blocked by future-limit rule");
        }

        $packageCacheKey = $packageId.'|'.$betType;
        if (! array_key_exists($packageCacheKey, $packagePayloadCache)) {
            $packagePayloadCache[$packageCacheKey] = $this->packageResolver->resolveForBet($draw, $packageId, $betType);
        }

        $packagePayload = $packagePayloadCache[$packageCacheKey];
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
        if ($this->policyService->isMemberBlockedForMarket($memberId, (int) $draw->market_id)) {
            throw new Exception('Member is blocked from betting on this market');
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
        return $this->preloadBlockModes($draw, [[
            'bet_type' => $betType,
            'number' => $number,
        ]])[$this->exposureKey($betType, $number)] ?? null;
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
     *
     * @throws Exception
     */
    private function persistTicketItemsAndExposure(LottoTicket $ticket, int $drawId, array $validatedItems): void
    {
        if ($validatedItems === []) {
            return;
        }

        $groupedExposureItems = [];
        $ticketItemRows = [];
        $timestamp = now();

        foreach ($validatedItems as $item) {
            $key = $this->exposureKey($item['bet_type'], $item['number']);

            if (! array_key_exists($key, $groupedExposureItems)) {
                $groupedExposureItems[$key] = [
                    'bet_type' => $item['bet_type'],
                    'number' => $item['number'],
                    'amount' => 0.0,
                    'max_per_number' => $item['max_per_number'],
                ];
            }

            $groupedExposureItems[$key]['amount'] += (float) $item['amount'];

            $ticketItemRows[] = [
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
                'calculated_values_at_bet_time' => json_encode(
                    $item['calculated_values_at_bet_time'],
                    JSON_THROW_ON_ERROR
                ),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        $exposures = $this->exposureService->lockExposureRows($drawId, array_values($groupedExposureItems));

        foreach ($groupedExposureItems as $key => $item) {
            $exposure = $exposures->get($key);

            if (! $exposure) {
                throw new Exception("Exposure row missing for number {$item['number']}");
            }

            if (((float) $exposure->sold_amount + $item['amount']) > $item['max_per_number']) {
                throw new Exception("Exposure limit reached for number {$item['number']}");
            }
        }

        foreach (array_chunk($ticketItemRows, self::TICKET_ITEM_INSERT_CHUNK_SIZE) as $chunk) {
            LottoTicketItem::query()->insert($chunk);
        }

        foreach ($groupedExposureItems as $key => $item) {
            $exposures->get($key)?->increment('sold_amount', $item['amount']);
        }
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

    private function supportsBetConfirmedAtColumn(): bool
    {
        if (self::$hasBetConfirmedAtColumn === null) {
            self::$hasBetConfirmedAtColumn = Schema::hasColumn('lotto_tickets', 'bet_confirmed_at');
        }

        return self::$hasBetConfirmedAtColumn;
    }

    /**
     * @param  array<int, array{bet_type:string}>  $validatedItems
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

    private function exposureKey(string $betType, string $number): string
    {
        return $betType.'|'.$number;
    }

    /**
     * @param  array<int, array{bet_type:mixed, number:mixed}>  $items
     * @return array<string, string|null>
     */
    private function preloadBlockModes(LottoDraw $draw, array $items): array
    {
        $requestedPairs = collect($items)
            ->map(function (array $item): array {
                return [
                    'bet_type' => (string) ($item['bet_type'] ?? ''),
                    'number' => (string) ($item['number'] ?? ''),
                ];
            })
            ->filter(fn (array $item): bool => $item['bet_type'] !== '' && $item['number'] !== '')
            ->unique(fn (array $item): string => $this->exposureKey($item['bet_type'], $item['number']))
            ->values();

        if ($requestedPairs->isEmpty()) {
            return [];
        }

        $resolvedModes = $requestedPairs
            ->mapWithKeys(fn (array $item): array => [$this->exposureKey($item['bet_type'], $item['number']) => null])
            ->all();

        $currentDrawDate = $draw->draw_date;
        if (! $currentDrawDate) {
            return $resolvedModes;
        }

        $rows = LottoNumberBlock::query()
            ->select(['lotto_number_blocks.bet_type', 'lotto_number_blocks.number', 'lotto_number_blocks.mode'])
            ->join('lotto_draws as block_draws', 'block_draws.id', '=', 'lotto_number_blocks.draw_id')
            ->where(function ($query) use ($draw, $currentDrawDate): void {
                $query->where(function ($subQuery) use ($draw): void {
                    $subQuery->where('lotto_number_blocks.mode', self::BLOCK_MODE_BLOCK)
                        ->where('lotto_number_blocks.draw_id', $draw->id);
                })->orWhere(function ($subQuery) use ($draw, $currentDrawDate): void {
                    $subQuery->where('lotto_number_blocks.mode', self::BLOCK_MODE_LIMIT_FUTURE)
                        ->where('block_draws.market_id', $draw->market_id)
                        ->where('block_draws.draw_date', '<', $currentDrawDate->toDateString());
                });
            })
            ->where(function ($query) use ($requestedPairs): void {
                foreach ($requestedPairs as $pair) {
                    $query->orWhere(function ($subQuery) use ($pair): void {
                        $subQuery->where('lotto_number_blocks.bet_type', $pair['bet_type'])
                            ->where('lotto_number_blocks.number', $pair['number']);
                    });
                }
            })
            ->orderByRaw("case when lotto_number_blocks.mode = 'block' then 0 else 1 end")
            ->get();

        foreach ($rows as $row) {
            $key = $this->exposureKey((string) $row->bet_type, (string) $row->number);

            if (! array_key_exists($key, $resolvedModes) || $resolvedModes[$key] !== null) {
                continue;
            }

            $resolvedModes[$key] = (string) $row->mode;
        }

        return $resolvedModes;
    }
}
