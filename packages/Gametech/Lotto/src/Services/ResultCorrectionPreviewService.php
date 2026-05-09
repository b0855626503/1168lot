<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoResultCorrection;
use Gametech\Lotto\Models\LottoResultCorrectionItem;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LottoTicketItem;
use Gametech\Lotto\Support\ResultHash;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ResultCorrectionPreviewService
{
    public function __construct(
        private SettlementService $settlementService
    ) {}

    /**
     * @param  array<string, mixed>  $resultNumber
     * @return array<string, mixed>
     */
    public function preview(LottoDraw $draw, array $resultNumber, string $reason, int $createdBy, bool $persist = true): array
    {
        if ((string) $draw->status !== 'resulted') {
            throw new InvalidArgumentException('ออกผลใหม่ได้เฉพาะงวดที่ประกาศผลแล้ว');
        }

        $oldResult = is_array($draw->result_number) ? $draw->result_number : [];
        $normalizedOld = $this->settlementService->normalizeResultNumber($oldResult);
        $normalizedNew = $this->settlementService->normalizeResultNumber($resultNumber);

        $oldHash = ResultHash::fromPayload($normalizedOld);
        $newHash = ResultHash::fromPayload($normalizedNew);

        $tickets = LottoTicket::query()
            ->with(['items'])
            ->where('draw_id', (int) $draw->id)
            ->where('status', '!=', 'cancelled')
            ->orderBy('id')
            ->get();

        $memberKeys = $tickets->pluck('member_id')->unique()->values()->all();
        $memberRecords = DB::table('members')
            ->whereIn('code', $memberKeys)
            ->get(['code', 'balance']);
        $membersByCode = $memberRecords->keyBy('code');

        $rows = [];
        $summary = [
            'ticket_count' => (int) $tickets->count(),
            'affected_ticket_count' => 0,
            'old_winning_ticket_count' => 0,
            'new_winning_ticket_count' => 0,
            'total_credit_amount' => 0.0,
            'total_reverse_amount' => 0.0,
            'estimated_reverse_uncollectable_amount' => 0.0,
        ];

        foreach ($tickets as $ticket) {
            $oldWin = $this->calculateTicketWin($ticket->items, $normalizedOld);
            $newWin = $this->calculateTicketWin($ticket->items, $normalizedNew);
            $delta = round($newWin - $oldWin, 2);
            $memberRecord = $membersByCode[(int) $ticket->member_id] ?? null;
            $memberCode = (int) ($memberRecord->code ?? $ticket->member_id);
            $memberBalance = round((float) ($memberRecord->balance ?? 0), 2);

            if ($oldWin > 0) {
                $summary['old_winning_ticket_count']++;
            }
            if ($newWin > 0) {
                $summary['new_winning_ticket_count']++;
            }

            $reverseRequired = $delta < 0 ? round(abs($delta), 2) : 0.0;
            $newCredit = $delta > 0 ? round($delta, 2) : 0.0;
            $estimatedUncollectable = $reverseRequired > 0 ? max(0, round($reverseRequired - $memberBalance, 2)) : 0.0;

            if ($delta !== 0.0) {
                $summary['affected_ticket_count']++;
            }
            $summary['total_credit_amount'] = round($summary['total_credit_amount'] + $newCredit, 2);
            $summary['total_reverse_amount'] = round($summary['total_reverse_amount'] + $reverseRequired, 2);
            $summary['estimated_reverse_uncollectable_amount'] = round($summary['estimated_reverse_uncollectable_amount'] + $estimatedUncollectable, 2);

            $rows[] = [
                'draw_id' => (int) $draw->id,
                'ticket_id' => (int) $ticket->id,
                'member_id' => $memberCode,
                'old_win_amount' => $oldWin,
                'new_win_amount' => $newWin,
                'delta' => $delta,
                'reverse_required_amount' => $reverseRequired,
                'new_credit_amount' => $newCredit,
                'current_balance' => $memberBalance,
                'expected_status' => $this->expectedStatus($delta, $estimatedUncollectable),
                'estimated_reverse_uncollectable_amount' => $estimatedUncollectable,
            ];
        }

        $correctionId = null;
        if ($persist) {
            $correction = DB::transaction(function () use ($draw, $normalizedOld, $normalizedNew, $oldHash, $newHash, $reason, $createdBy, $summary, $rows): LottoResultCorrection {
                $correction = LottoResultCorrection::query()->create([
                    'draw_id' => (int) $draw->id,
                    'old_result_number' => $normalizedOld,
                    'new_result_number' => $normalizedNew,
                    'old_result_hash' => $oldHash,
                    'new_result_hash' => $newHash,
                    'source' => 'manual',
                    'reason' => $reason,
                    'status' => 'previewed',
                    'ticket_count' => $summary['ticket_count'],
                    'affected_ticket_count' => $summary['affected_ticket_count'],
                    'old_winning_ticket_count' => $summary['old_winning_ticket_count'],
                    'new_winning_ticket_count' => $summary['new_winning_ticket_count'],
                    'total_reversed_amount' => $summary['total_reverse_amount'],
                    'total_reverse_failed_amount' => $summary['estimated_reverse_uncollectable_amount'],
                    'total_new_payout_amount' => $summary['total_credit_amount'],
                    'created_by' => $createdBy > 0 ? $createdBy : null,
                ]);

                foreach ($rows as $row) {
                    LottoResultCorrectionItem::query()->create([
                        'correction_id' => (int) $correction->id,
                        'draw_id' => (int) $draw->id,
                        'ticket_id' => (int) $row['ticket_id'],
                        'member_id' => (int) $row['member_id'],
                        'old_win_amount' => (float) $row['old_win_amount'],
                        'new_win_amount' => (float) $row['new_win_amount'],
                        'initial_member_balance' => (float) ($row['current_balance'] ?? 0),
                        'reverse_required_amount' => (float) $row['reverse_required_amount'],
                        'reverse_debited_amount' => 0,
                        'reverse_remaining_amount' => (float) $row['reverse_required_amount'],
                        'new_credit_amount' => (float) $row['new_credit_amount'],
                        'status' => (string) $row['expected_status'],
                        'note' => 'preview',
                    ]);
                }

                return $correction;
            });
            $correctionId = (int) $correction->id;
        }

        return [
            'correction_id' => $correctionId,
            'draw_id' => (int) $draw->id,
            'reason' => $reason,
            'source' => 'manual',
            'old_result_number' => $normalizedOld,
            'new_result_number' => $normalizedNew,
            'summary' => $summary,
            'items' => $rows,
        ];
    }

    /**
     * @param  iterable<int, LottoTicketItem>  $items
     * @param  array<string, string>  $resultNumber
     */
    private function calculateTicketWin(iterable $items, array $resultNumber): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $isWinner = $this->settlementService->isWinningBet((string) $item->bet_type, (string) $item->number, $resultNumber);
            if (! $isWinner) {
                continue;
            }

            if (isset($item->potential_win_amount_at_time) && $item->potential_win_amount_at_time !== null) {
                $total += round((float) $item->potential_win_amount_at_time, 2);

                continue;
            }

            $total += round((float) $item->amount * (float) $item->payout_at_time, 2);
        }

        return round($total, 2);
    }

    private function expectedStatus(float $delta, float $estimatedUncollectable): string
    {
        if ($delta === 0.0) {
            return 'unchanged';
        }
        if ($delta > 0) {
            return 'credited';
        }

        return $estimatedUncollectable > 0 ? 'reverse_partial' : 'reversed';
    }
}
