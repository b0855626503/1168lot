<?php

namespace Gametech\Lotto\Services;

use Exception;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LottoTicketItem;
use Gametech\Lotto\Models\LottoWinning;
use Gametech\Lotto\Models\SettlementBatch;
use Gametech\Lotto\Support\ResultHash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

class SettlementService
{
    private const SETTLE_WIN_REF_TYPE = 'LOTTO_SETTLE_WIN';

    public function __construct(
        private ?WalletTransactionService $walletTransactionService = null
    ) {}

    /**
     * @param  array<string, mixed>  $resultNumber
     * @return array<string, int|float|array<string, string>>
     *
     * @throws Exception
     */
    public function settleDraw(LottoDraw $draw, array $resultNumber, string $mode = 'settlement'): array
    {
        $normalizedResult = $this->normalizeResultNumber($resultNumber);
        $resultHash = ResultHash::fromPayload($normalizedResult);
        $batch = $this->findOrCreateSettlementBatch($draw, $resultHash, $mode);

        $startedAt = microtime(true);

        try {
            $summary = DB::transaction(function () use ($draw, $normalizedResult, $resultHash, $batch, $mode) {
                $draw = LottoDraw::query()->lockForUpdate()->findOrFail($draw->id);

                if (
                    (string) $draw->status === 'resulted'
                    && (string) ($draw->result_hash ?? '') === $resultHash
                    && $mode === 'settlement'
                ) {
                    $this->syncWinningCreditStatusFromWallet((int) $draw->id);

                    return $this->buildSummaryFromMaterializedData($draw, $normalizedResult);
                }

                if ((string) $draw->status === 'resulted' && $mode === 'settlement') {
                    throw new InvalidArgumentException('งวดนี้ประกาศผลไปแล้ว');
                }

                if ((string) $draw->status !== 'resulted') {
                    $draw->update([
                        'result_number' => $normalizedResult,
                        'result_at' => now(),
                        'status' => 'resulted',
                        'result_fetch_status' => 'APPLIED',
                        'result_fetch_error' => null,
                        'result_hash' => ResultHash::fromPayload($normalizedResult),
                        'result_applied_at' => now(),
                        'result_fetched_at' => now(),
                    ]);
                }

                $context = $this->resolveLottoContext((int) $draw->id);
                $lotteryType = $context['lottery_type'];
                $marketCode = $context['market'];

                $tickets = LottoTicket::query()
                    ->with(['items', 'member:code,user_name'])
                    ->where('draw_id', $draw->id)
                    ->where('status', '!=', 'cancelled')
                    ->lockForUpdate()
                    ->get();

                $winningTickets = 0;
                $winningItems = 0;
                $totalWinAmount = 0.0;
                $totalStake = 0.0;
                $canWriteWalletTransactions = $mode === 'settlement' && Schema::hasTable('wallet_transactions');

                foreach ($tickets as $ticket) {
                    $ticketWinAmount = 0.0;
                    $hasWinningItem = false;
                    $winningRecordIds = [];

                    foreach ($ticket->items as $item) {
                        $isWinner = $this->isWinningItem($item, $normalizedResult);
                        $winAmount = $this->resolveWinAmount($item, $isWinner);
                        $this->applyItemSettlement($item, $isWinner, $winAmount);

                        if (! $isWinner) {
                            continue;
                        }

                        $matchedContext = $this->resolveMatchedContext((string) $item->bet_type, $normalizedResult);
                        $winningRecord = LottoWinning::query()->updateOrCreate(
                            [
                                'draw_id' => (int) $draw->id,
                                'bet_item_id' => (int) $item->id,
                            ],
                            [
                                'bet_id' => (int) $ticket->id,
                                'ticket_no' => (string) $ticket->id,
                                'user_id' => (int) $ticket->member_id,
                                'username' => (string) ($ticket->member->user_name ?? ''),
                                'lottery_type' => $lotteryType,
                                'market' => $marketCode !== '' ? $marketCode : null,
                                'bet_type' => (string) $item->bet_type,
                                'number' => (string) $item->number,
                                'stake' => round((float) $item->amount, 2),
                                'odds' => round((float) $item->payout_at_time, 4),
                                'payout' => round($winAmount, 2),
                                'net_profit' => round((float) $item->amount - $winAmount, 2),
                                'result_number' => $matchedContext['result_number'],
                                'matched_rule' => $matchedContext['matched_rule'],
                                'status' => 'settled',
                                'settlement_batch_id' => (int) $batch->id,
                                'settled_at' => now(),
                                'credited_at' => null,
                            ]
                        );

                        $winningRecordIds[] = (int) $winningRecord->id;

                        $hasWinningItem = true;
                        $winningItems++;
                        $ticketWinAmount += $winAmount;
                        $totalStake += (float) $item->amount;
                    }

                    $ticket->update([
                        'status' => 'resulted',
                        'total_win_amount' => round($ticketWinAmount, 2),
                    ]);

                    if (! $hasWinningItem) {
                        continue;
                    }

                    $winningTickets++;
                    $totalWinAmount += $ticketWinAmount;

                    $creditSuccess = $this->creditWinnerIfNeeded($draw, $ticket, $ticketWinAmount, $canWriteWalletTransactions);

                    $walletCreditedAt = $this->resolveWalletCreditAt(
                        (int) $ticket->id,
                        (int) $ticket->member_id,
                        (int) $draw->id
                    );

                    if (($creditSuccess || $walletCreditedAt !== null) && $winningRecordIds !== []) {
                        LottoWinning::query()
                            ->whereIn('id', $winningRecordIds)
                            ->update([
                                'status' => 'credited',
                                'credited_at' => $walletCreditedAt ?? now(),
                            ]);
                    }
                }

                $batch->update([
                    'status' => 'settled',
                    'finished_at' => now(),
                    'total_bets_processed' => (int) $tickets->count(),
                    'total_winning_records' => (int) $winningItems,
                    'total_stake' => round($totalStake, 2),
                    'total_payout' => round($totalWinAmount, 2),
                    'error_message' => null,
                ]);

                return [
                    'draw_id' => (int) $draw->id,
                    'result_number' => $normalizedResult,
                    'ticket_count' => (int) $tickets->count(),
                    'winning_ticket_count' => $winningTickets,
                    'winning_item_count' => $winningItems,
                    'total_win_amount' => round($totalWinAmount, 2),
                ];
            });

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $batchPayload = SettlementBatch::query()->find((int) $batch->id);

            Log::info('lotto.settlement.batch.settled', [
                'settlement_batch_id' => (int) $batch->id,
                'draw_id' => (int) $draw->id,
                'lottery_type' => (string) ($batchPayload->lottery_type ?? ''),
                'processed_count' => (int) ($summary['ticket_count'] ?? 0),
                'winning_count' => (int) ($summary['winning_item_count'] ?? 0),
                'total_payout' => (float) ($summary['total_win_amount'] ?? 0),
                'duration_ms' => $durationMs,
                'error_message' => null,
            ]);

            return $summary;
        } catch (Throwable $exception) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            SettlementBatch::query()
                ->where('id', (int) $batch->id)
                ->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'error_message' => mb_substr($exception->getMessage(), 0, 65535),
                ]);

            Log::error('lotto.settlement.batch.failed', [
                'settlement_batch_id' => (int) $batch->id,
                'draw_id' => (int) $draw->id,
                'lottery_type' => (string) ($batch->lottery_type ?? ''),
                'processed_count' => 0,
                'winning_count' => 0,
                'total_payout' => 0,
                'duration_ms' => $durationMs,
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $resultNumber
     * @return array<string, string>
     */
    public function normalizeResultNumber(array $resultNumber): array
    {
        $firstPrize = preg_replace('/\D+/', '', (string) ($resultNumber['first_prize'] ?? ''));
        $last2Digits = preg_replace('/\D+/', '', (string) ($resultNumber['last_2_digits'] ?? ''));

        if ($firstPrize !== '' || $last2Digits !== '') {
            $firstPrizeLength = strlen($firstPrize);
            if (! in_array($firstPrizeLength, [3, 4, 5, 6, 7], true)) {
                throw new InvalidArgumentException('รางวัลที่ 1 ต้องมี 3, 4, 5, 6 หรือ 7 หลัก');
            }

            if (strlen($last2Digits) !== 2) {
                throw new InvalidArgumentException('เลขท้าย 2 ตัวต้องมี 2 หลัก');
            }

            $top3 = $firstPrizeLength === 3
                ? $firstPrize
                : substr($firstPrize, -3);
            $top2 = substr($firstPrize, -2);

            return [
                'first_prize' => $firstPrize,
                'last_2_digits' => $last2Digits,
                'top_3' => $top3,
                'top_2' => $top2,
                // Keep compatibility with existing BOTTOM_2 bet type settlement logic.
                'bottom_2' => $last2Digits,
            ];
        }

        // Legacy fallback for older payloads/tests that still send top_3/bottom_2.
        $top3 = preg_replace('/\D+/', '', (string) ($resultNumber['top_3'] ?? ''));
        $top2Input = preg_replace('/\D+/', '', (string) ($resultNumber['top_2'] ?? ''));
        $bottom2 = preg_replace('/\D+/', '', (string) ($resultNumber['bottom_2'] ?? ''));

        if (strlen($top3) !== 3) {
            throw new InvalidArgumentException('ผล 3 ตัวบนต้องมี 3 หลัก');
        }

        if (strlen($bottom2) !== 2) {
            throw new InvalidArgumentException('ผล 2 ตัวล่างต้องมี 2 หลัก');
        }

        $top2 = $top2Input !== '' ? $top2Input : substr($top3, -2);

        if (strlen($top2) !== 2) {
            throw new InvalidArgumentException('ผล 2 ตัวบนต้องมี 2 หลัก');
        }

        return [
            'top_3' => $top3,
            'top_2' => $top2,
            'bottom_2' => $bottom2,
        ];
    }

    /**
     * @param  array<string, string>  $resultNumber
     */
    public function isWinningBet(string $betType, string $number, array $resultNumber): bool
    {
        $top3 = $resultNumber['top_3'];
        $top2 = $resultNumber['top_2'] ?? substr($top3, -2);
        $bottom2 = $resultNumber['bottom_2'];
        $normalizedNumber = preg_replace('/\D+/', '', trim($number));

        return match ($betType) {
            BetType::TOP_3 => $normalizedNumber === $top3,
            BetType::TOD_3 => strlen($normalizedNumber) === 3 && $this->sameDigits($normalizedNumber, $top3),
            BetType::TOP_2 => $normalizedNumber === $top2,
            BetType::BOTTOM_2 => $normalizedNumber === $bottom2,
            BetType::RUN_TOP => strlen($normalizedNumber) === 1 && str_contains($top3, $normalizedNumber),
            BetType::RUN_BOTTOM => strlen($normalizedNumber) === 1 && str_contains($bottom2, $normalizedNumber),
            default => false,
        };
    }

    /**
     * @param  array<string, string>  $resultNumber
     */
    public function describeResultNumber(array $resultNumber): string
    {
        $normalized = $this->normalizeResultNumber($resultNumber);

        if (! empty($normalized['first_prize']) && ! empty($normalized['last_2_digits'])) {
            return 'รางวัลที่ 1 '.$normalized['first_prize']
                .' / เลขท้าย 2 ตัว '.$normalized['last_2_digits']
                .' / 3 ตัวบน '.$normalized['top_3']
                .' / 2 ตัวบน '.$normalized['top_2'];
        }

        return '3 ตัวบน '.$normalized['top_3']
            .' / 2 ตัวบน '.$normalized['top_2']
            .' / 2 ตัวล่าง '.$normalized['bottom_2'];
    }

    /**
     * @param  array<string, string>  $resultNumber
     */
    private function isWinningItem(LottoTicketItem $item, array $resultNumber): bool
    {
        return $this->isWinningBet((string) $item->bet_type, (string) $item->number, $resultNumber);
    }

    private function sameDigits(string $left, string $right): bool
    {
        $leftDigits = str_split($left);
        $rightDigits = str_split($right);

        sort($leftDigits);
        sort($rightDigits);

        return $leftDigits === $rightDigits;
    }

    private function resolveWinAmount(LottoTicketItem $item, bool $isWinner): float
    {
        if (! $isWinner) {
            return 0.0;
        }

        if (isset($item->potential_win_amount_at_time) && $item->potential_win_amount_at_time !== null) {
            return round((float) $item->potential_win_amount_at_time, 2);
        }

        return round((float) $item->amount * (float) $item->payout_at_time, 2);
    }

    private function applyItemSettlement(LottoTicketItem $item, bool $isWinner, float $winAmount): void
    {
        $item->update([
            'result_status' => $isWinner ? 'win' : 'lose',
            'win_amount' => $winAmount,
        ]);
    }

    private function creditWinnerIfNeeded(
        LottoDraw $draw,
        LottoTicket $ticket,
        float $ticketWinAmount,
        bool $canWriteWalletTransactions
    ): bool {
        if ($ticketWinAmount <= 0) {
            return false;
        }

        if (! $canWriteWalletTransactions) {
            return false;
        }

        $alreadyCredited = DB::table('wallet_transactions')
            ->where('member_id', (int) $ticket->member_id)
            ->where('direction', 'CREDIT')
            ->where('ref_type', self::SETTLE_WIN_REF_TYPE)
            ->where('ref_id', (int) $ticket->id)
            ->exists();

        if ($alreadyCredited) {
            return true;
        }

        $this->walletTransactionService()->creditMemberBalance(
            memberId: (int) $ticket->member_id,
            amount: $ticketWinAmount,
            refType: self::SETTLE_WIN_REF_TYPE,
            refId: (int) $ticket->id,
            refCode: (string) $draw->id,
            groupCode: 'LOTTO_SETTLE_DRAW_'.(int) $draw->id,
            meta: [
                'draw_id' => (int) $draw->id,
                'ticket_id' => (int) $ticket->id,
            ],
            createdByType: 'system',
            createdById: null,
            description: 'จ่ายรางวัลหวย'
        );

        return true;
    }

    private function walletTransactionService(): WalletTransactionService
    {
        if ($this->walletTransactionService instanceof WalletTransactionService) {
            return $this->walletTransactionService;
        }

        $this->walletTransactionService = app(WalletTransactionService::class);

        return $this->walletTransactionService;
    }

    private function resolveWalletCreditAt(int $ticketId, int $memberId, int $drawId): ?string
    {
        $creditedAt = DB::table('wallet_transactions')
            ->where('member_id', $memberId)
            ->where('direction', 'CREDIT')
            ->where('ref_type', self::SETTLE_WIN_REF_TYPE)
            ->where('ref_id', $ticketId)
            ->where('ref_code', (string) $drawId)
            ->orderBy('id')
            ->value('created_at');

        return $creditedAt === null ? null : (string) $creditedAt;
    }

    private function syncWinningCreditStatusFromWallet(int $drawId): void
    {
        LottoWinning::query()
            ->where('draw_id', $drawId)
            ->where(function ($query): void {
                $query->whereNull('credited_at')
                    ->orWhere('status', '!=', 'credited');
            })
            ->orderBy('id')
            ->chunkById(200, function ($winnings) use ($drawId): void {
                foreach ($winnings as $winning) {
                    $walletCreditedAt = $this->resolveWalletCreditAt(
                        (int) $winning->bet_id,
                        (int) $winning->user_id,
                        $drawId
                    );

                    if ($walletCreditedAt === null) {
                        continue;
                    }

                    LottoWinning::query()
                        ->where('id', (int) $winning->id)
                        ->update([
                            'status' => 'credited',
                            'credited_at' => $walletCreditedAt,
                        ]);
                }
            });
    }

    private function findOrCreateSettlementBatch(LottoDraw $draw, string $resultHash, string $mode): SettlementBatch
    {
        $context = $this->resolveLottoContext((int) $draw->id);
        $lotteryType = $context['lottery_type'];
        $marketCode = $context['market'];

        $idempotencyKey = sprintf('settlement:%d:%s', (int) $draw->id, $resultHash);

        return SettlementBatch::query()->firstOrCreate(
            [
                'idempotency_key' => $idempotencyKey,
            ],
            [
                'draw_id' => (int) $draw->id,
                'draw_date' => $draw->draw_date,
                'lottery_type' => $lotteryType,
                'market' => $marketCode !== '' ? $marketCode : null,
                'mode' => $mode,
                'status' => 'pending',
                'started_at' => now(),
                'triggered_by' => auth()->check() ? (string) auth()->id() : 'system',
            ]
        );
    }

    /**
     * @param  array<string, string>  $normalizedResult
     * @return array<string, int|float|array<string, string>>
     */
    private function buildSummaryFromMaterializedData(LottoDraw $draw, array $normalizedResult): array
    {
        $winningQuery = LottoWinning::query()->where('draw_id', (int) $draw->id);

        $ticketCount = LottoTicket::query()
            ->where('draw_id', (int) $draw->id)
            ->where('status', '!=', 'cancelled')
            ->count();

        return [
            'draw_id' => (int) $draw->id,
            'result_number' => $normalizedResult,
            'ticket_count' => (int) $ticketCount,
            'winning_ticket_count' => (int) (clone $winningQuery)->distinct('bet_id')->count('bet_id'),
            'winning_item_count' => (int) (clone $winningQuery)->count(),
            'total_win_amount' => round((float) (clone $winningQuery)->sum('payout'), 2),
        ];
    }

    /**
     * @param  array<string, string>  $normalizedResult
     * @return array{result_number:?string, matched_rule:?string}
     */
    private function resolveMatchedContext(string $betType, array $normalizedResult): array
    {
        return match ($betType) {
            BetType::TOP_3, BetType::TOD_3, BetType::RUN_TOP => [
                'result_number' => $normalizedResult['top_3'] ?? null,
                'matched_rule' => $betType,
            ],
            BetType::TOP_2 => [
                'result_number' => $normalizedResult['top_2'] ?? null,
                'matched_rule' => $betType,
            ],
            BetType::BOTTOM_2, BetType::RUN_BOTTOM => [
                'result_number' => $normalizedResult['bottom_2'] ?? null,
                'matched_rule' => $betType,
            ],
            default => [
                'result_number' => null,
                'matched_rule' => $betType,
            ],
        };
    }

    /**
     * @return array{lottery_type:string, market:?string}
     */
    private function resolveLottoContext(int $drawId): array
    {
        $row = DB::table('lotto_draws as d')
            ->leftJoin('lotto_markets as m', 'm.id', '=', 'd.market_id')
            ->leftJoin('lotto_groups as g', 'g.id', '=', 'm.group_id')
            ->where('d.id', $drawId)
            ->select([
                'g.code as lottery_type',
                'm.code as market_code',
            ])
            ->first();

        $lotteryType = (string) ($row->lottery_type ?? '');
        if ($lotteryType === '') {
            throw new InvalidArgumentException('ไม่พบ lottery_type จาก market.group.code');
        }

        $marketCode = (string) ($row->market_code ?? '');

        return [
            'lottery_type' => $lotteryType,
            'market' => $marketCode !== '' ? $marketCode : null,
        ];
    }
}
