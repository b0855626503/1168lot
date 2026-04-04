<?php

namespace Gametech\Lotto\Services;

use Exception;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LottoTicketItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class SettlementService
{
    private const SETTLE_WIN_REF_TYPE = 'LOTTO_SETTLE_WIN';

    public function __construct(
        private ?WalletTransactionService $walletTransactionService = null
    ) {}

    /**
     * @param array<string, mixed> $resultNumber
     * @return array<string, int|float|array<string, string>>
     * @throws Exception
     */
    public function settleDraw(LottoDraw $draw, array $resultNumber): array
    {
        $normalizedResult = $this->normalizeResultNumber($resultNumber);

        return DB::transaction(function () use ($draw, $normalizedResult) {
            $draw = LottoDraw::query()->lockForUpdate()->findOrFail($draw->id);

            if ((string) $draw->status === 'resulted') {
                throw new InvalidArgumentException('งวดนี้ประกาศผลไปแล้ว');
            }

            $draw->update([
                'result_number' => $normalizedResult,
                'result_at' => now(),
                'status' => 'resulted',
            ]);

            $tickets = LottoTicket::query()
                ->with('items')
                ->where('draw_id', $draw->id)
                ->where('status', '!=', 'cancelled')
                ->lockForUpdate()
                ->get();

            $winningTickets = 0;
            $winningItems = 0;
            $totalWinAmount = 0.0;
            $canWriteWalletTransactions = Schema::hasTable('wallet_transactions');

            foreach ($tickets as $ticket) {
                $ticketWinAmount = 0.0;
                $hasWinningItem = false;

                foreach ($ticket->items as $item) {
                    $isWinner = $this->isWinningItem($item, $normalizedResult);
                    $winAmount = $this->resolveWinAmount($item, $isWinner);
                    $this->applyItemSettlement($item, $isWinner, $winAmount);

                    if ($isWinner) {
                        $hasWinningItem = true;
                        $winningItems++;
                        $ticketWinAmount += $winAmount;
                    }
                }

                $ticket->update([
                    'status' => 'resulted',
                    'total_win_amount' => round($ticketWinAmount, 2),
                ]);

                if ($hasWinningItem) {
                    $winningTickets++;
                    $totalWinAmount += $ticketWinAmount;

                    $this->creditWinnerIfNeeded($draw, $ticket, $ticketWinAmount, $canWriteWalletTransactions);
                }
            }

            return [
                'draw_id' => (int) $draw->id,
                'result_number' => $normalizedResult,
                'ticket_count' => (int) $tickets->count(),
                'winning_ticket_count' => $winningTickets,
                'winning_item_count' => $winningItems,
                'total_win_amount' => round($totalWinAmount, 2),
            ];
        });
    }

    /**
     * @param array<string, mixed> $resultNumber
     * @return array<string, string>
     */
    public function normalizeResultNumber(array $resultNumber): array
    {
        $firstPrize = preg_replace('/\D+/', '', (string) ($resultNumber['first_prize'] ?? ''));
        $last2Digits = preg_replace('/\D+/', '', (string) ($resultNumber['last_2_digits'] ?? ''));

        if ($firstPrize !== '' || $last2Digits !== '') {
            $firstPrizeLength = strlen($firstPrize);
            if (! in_array($firstPrizeLength, [3, 4, 5, 6], true)) {
                throw new InvalidArgumentException('รางวัลที่ 1 ต้องมี 3, 4, 5 หรือ 6 หลัก');
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
     * @param array<string, string> $resultNumber
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
     * @param array<string, string> $resultNumber
     */
    public function describeResultNumber(array $resultNumber): string
    {
        $normalized = $this->normalizeResultNumber($resultNumber);

        if (! empty($normalized['first_prize']) && ! empty($normalized['last_2_digits'])) {
            return 'รางวัลที่ 1 ' . $normalized['first_prize']
                . ' / เลขท้าย 2 ตัว ' . $normalized['last_2_digits']
                . ' / 3 ตัวบน ' . $normalized['top_3']
                . ' / 2 ตัวบน ' . $normalized['top_2'];
        }

        return '3 ตัวบน ' . $normalized['top_3']
            . ' / 2 ตัวบน ' . $normalized['top_2']
            . ' / 2 ตัวล่าง ' . $normalized['bottom_2'];
    }

    /**
     * @param array<string, string> $resultNumber
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
    ): void
    {
        if ($ticketWinAmount <= 0) {
            return;
        }

        if (! $canWriteWalletTransactions) {
            return;
        }

        $alreadyCredited = DB::table('wallet_transactions')
            ->where('member_id', (int) $ticket->member_id)
            ->where('direction', 'CREDIT')
            ->where('ref_type', self::SETTLE_WIN_REF_TYPE)
            ->where('ref_id', (int) $ticket->id)
            ->exists();

        if ($alreadyCredited) {
            return;
        }

        $this->walletTransactionService()->creditMemberBalance(
            memberId: (int) $ticket->member_id,
            amount: $ticketWinAmount,
            refType: self::SETTLE_WIN_REF_TYPE,
            refId: (int) $ticket->id,
            refCode: (string) $draw->id,
            groupCode: 'LOTTO_SETTLE_DRAW_' . (int) $draw->id,
            meta: [
                'draw_id' => (int) $draw->id,
                'ticket_id' => (int) $ticket->id,
            ],
            createdByType: 'system',
            createdById: null,
            description: 'จ่ายรางวัลหวย'
        );
    }

    private function walletTransactionService(): WalletTransactionService
    {
        if ($this->walletTransactionService instanceof WalletTransactionService) {
            return $this->walletTransactionService;
        }

        $this->walletTransactionService = app(WalletTransactionService::class);

        return $this->walletTransactionService;
    }
}
