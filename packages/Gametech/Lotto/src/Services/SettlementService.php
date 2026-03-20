<?php

namespace Gametech\Lotto\Services;

use Exception;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LottoTicketItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SettlementService
{
    /**
     * @param array<string, mixed> $resultNumber
     * @return array<string, int|float|array<string, string>>
     * @throws Exception
     */
    public function settleDraw(LottoDraw $draw, array $resultNumber, ?string $resultAt = null): array
    {
        $normalizedResult = $this->normalizeResultNumber($resultNumber);

        return DB::transaction(function () use ($draw, $normalizedResult, $resultAt) {
            $draw = LottoDraw::query()->lockForUpdate()->findOrFail($draw->id);

            $draw->update([
                'result_number' => $normalizedResult,
                'result_at' => $resultAt ?? now(),
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

            foreach ($tickets as $ticket) {
                $ticketWinAmount = 0.0;
                $hasWinningItem = false;

                foreach ($ticket->items as $item) {
                    $isWinner = $this->isWinningItem($item, $normalizedResult);
                    $winAmount = $isWinner ? round((float) $item->amount * (float) $item->payout_at_time, 2) : 0.0;

                    $item->update([
                        'result_status' => $isWinner ? 'win' : 'lose',
                        'win_amount' => $winAmount,
                    ]);

                    if ($isWinner) {
                        $hasWinningItem = true;
                        $winningItems++;
                        $ticketWinAmount += $winAmount;
                    }
                }

                $ticket->update([
                    'status' => 'resulted',
                ]);

                if ($hasWinningItem) {
                    $winningTickets++;
                    $totalWinAmount += $ticketWinAmount;
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
        $top3 = preg_replace('/\D+/', '', (string) ($resultNumber['top_3'] ?? ''));
        $bottom2 = preg_replace('/\D+/', '', (string) ($resultNumber['bottom_2'] ?? ''));

        if (strlen($top3) !== 3) {
            throw new InvalidArgumentException('ผล 3 ตัวบนต้องมี 3 หลัก');
        }

        if (strlen($bottom2) !== 2) {
            throw new InvalidArgumentException('ผล 2 ตัวล่างต้องมี 2 หลัก');
        }

        return [
            'top_3' => $top3,
            'bottom_2' => $bottom2,
        ];
    }

    /**
     * @param array<string, string> $resultNumber
     */
    public function isWinningBet(string $betType, string $number, array $resultNumber): bool
    {
        $top3 = $resultNumber['top_3'];
        $bottom2 = $resultNumber['bottom_2'];
        $normalizedNumber = preg_replace('/\D+/', '', trim($number));

        return match ($betType) {
            BetType::TOP_3 => $normalizedNumber === $top3,
            BetType::TOD_3 => strlen($normalizedNumber) === 3 && $this->sameDigits($normalizedNumber, $top3),
            BetType::TOP_2 => $normalizedNumber === substr($top3, 0, 2),
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

        return '3 ตัวบน ' . $normalized['top_3'] . ' / 2 ตัวล่าง ' . $normalized['bottom_2'];
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
}

