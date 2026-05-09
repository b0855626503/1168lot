<?php

namespace Gametech\Lotto\Services;

use App\Services\Dashboard\DashboardSummarySyncService;
use App\Services\Dashboard\LottoDashboardMetricConfig;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoResultCorrection;
use Gametech\Lotto\Models\LottoResultCorrectionItem;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LottoTicketItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ResultCorrectionApplyService
{
    private const REF_CREDIT = 'LOTTO_RESULT_CORRECTION_CREDIT';
    private const REF_REVERSE = 'LOTTO_RESULT_CORRECTION_REVERSE';

    public function __construct(
        private WalletTransactionService $walletTransactionService,
        private SettlementService $settlementService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function apply(int $correctionId, ?int $actorId = null): array
    {
        $correction = LottoResultCorrection::query()->find($correctionId);
        if (! $correction instanceof LottoResultCorrection) {
            throw new InvalidArgumentException('ไม่พบ correction ที่ต้องการ apply');
        }

        if (in_array((string) $correction->status, ['completed', 'partial_failed'], true)) {
            throw new InvalidArgumentException('correction นี้ถูก apply แล้ว');
        }

        if ((string) $correction->status === 'processing') {
            throw new RuntimeException('correction นี้กำลังประมวลผล');
        }

        $drawId = (int) $correction->draw_id;
        $lockKey = 'lotto:draw-result-correction:'.$drawId;
        $lock = Cache::lock($lockKey, 20);
        if (! $lock->get()) {
            throw new RuntimeException('มี correction อื่นกำลังทำงานกับงวดนี้');
        }

        try {
            return DB::transaction(function () use ($correctionId, $drawId, $actorId): array {
                $correction = LottoResultCorrection::query()->lockForUpdate()->findOrFail($correctionId);
                $draw = LottoDraw::query()->lockForUpdate()->findOrFail($drawId);

                if ((string) $draw->status !== 'resulted') {
                    throw new InvalidArgumentException('งวดนี้ไม่อยู่ในสถานะ resulted');
                }

                if (in_array((string) $correction->status, ['completed', 'partial_failed'], true)) {
                    throw new InvalidArgumentException('correction นี้ถูก apply แล้ว');
                }

                $correction->update([
                    'status' => 'processing',
                    'started_at' => now(),
                    'error_message' => null,
                ]);

                $draw->update([
                    'result_number' => is_array($correction->new_result_number) ? $correction->new_result_number : [],
                    'result_hash' => (string) ($correction->new_result_hash ?? $draw->result_hash),
                ]);

                $items = LottoResultCorrectionItem::query()
                    ->where('correction_id', (int) $correction->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $activeTicketIdSet = LottoTicket::query()
                    ->whereIn('id', $items->pluck('ticket_id')->unique()->values()->all())
                    ->where('status', '!=', 'cancelled')
                    ->pluck('id')
                    ->flip();

                $totalCredited = 0.0;
                $totalReversed = 0.0;
                $totalRemaining = 0.0;

                foreach ($items as $item) {
                    if (! isset($activeTicketIdSet[(int) $item->ticket_id])) {
                        $item->update([
                            'status' => 'skipped_cancelled',
                            'note' => 'skip_cancelled_ticket',
                            'reverse_debited_amount' => 0,
                            'reverse_remaining_amount' => 0,
                            'new_credit_amount' => 0,
                        ]);

                        continue;
                    }

                    $delta = round((float) $item->new_win_amount - (float) $item->old_win_amount, 2);
                    $resolvedMemberCode = $this->resolveMemberCode((int) $item->member_id);

                    if ($delta > 0) {
                        $txnId = $this->walletTransactionService->creditMemberBalance(
                            memberId: $resolvedMemberCode,
                            amount: $delta,
                            refType: self::REF_CREDIT,
                            refId: (int) $item->id,
                            refCode: (string) $drawId,
                            groupCode: 'LOTTO_RESULT_CORRECTION_'.(int) $correction->id,
                            meta: [
                                'correction_id' => (int) $correction->id,
                                'correction_item_id' => (int) $item->id,
                                'draw_id' => $drawId,
                                'ticket_id' => (int) $item->ticket_id,
                            ],
                            createdByType: 'admin',
                            createdById: $actorId,
                            description: 'จ่ายส่วนต่างแก้ไขผลหวย'
                        );

                        $item->update([
                            'new_credit_amount' => $delta,
                            'reverse_debited_amount' => 0,
                            'reverse_remaining_amount' => 0,
                            'new_credit_wallet_txn_id' => $txnId,
                            'status' => 'completed',
                            'note' => 'apply_credit',
                        ]);
                        $totalCredited = round($totalCredited + $delta, 2);

                        continue;
                    }

                    if ($delta < 0) {
                        $required = round(abs($delta), 2);
                        $currentBalance = round((float) DB::table('members')->where('code', $resolvedMemberCode)->value('balance'), 2);
                        $debitAmount = min($required, max(0, $currentBalance));
                        $remaining = round($required - $debitAmount, 2);
                        $reverseTxnId = null;

                        if ($debitAmount > 0) {
                            $reverseTxnId = $this->walletTransactionService->debitMemberBalance(
                                memberId: $resolvedMemberCode,
                                amount: $debitAmount,
                                refType: self::REF_REVERSE,
                                refId: (int) $item->id,
                                refCode: (string) $drawId,
                                groupCode: 'LOTTO_RESULT_CORRECTION_'.(int) $correction->id,
                                meta: [
                                    'correction_id' => (int) $correction->id,
                                    'correction_item_id' => (int) $item->id,
                                    'draw_id' => $drawId,
                                    'ticket_id' => (int) $item->ticket_id,
                                    'retry' => false,
                                ],
                                createdByType: 'admin',
                                createdById: $actorId,
                                description: 'หักคืนส่วนต่างแก้ไขผลหวย'
                            );
                        }

                        $item->update([
                            'reverse_required_amount' => $required,
                            'reverse_debited_amount' => $debitAmount,
                            'reverse_remaining_amount' => $remaining,
                            'reverse_wallet_txn_id' => $reverseTxnId,
                            'status' => $remaining > 0 ? 'reverse_partial' : 'completed',
                            'note' => $remaining > 0 ? 'apply_reverse_partial' : 'apply_reverse',
                        ]);

                        $totalReversed = round($totalReversed + $debitAmount, 2);
                        $totalRemaining = round($totalRemaining + $remaining, 2);

                        continue;
                    }

                    $item->update([
                        'status' => 'unchanged',
                        'note' => 'apply_unchanged',
                    ]);
                }

                $this->syncAffectedTicketsResultState($correction);

                $hasRemaining = LottoResultCorrectionItem::query()
                    ->where('correction_id', (int) $correction->id)
                    ->where('reverse_remaining_amount', '>', 0)
                    ->exists();

                $correction->update([
                    'status' => $hasRemaining ? 'partial_failed' : 'completed',
                    'finished_at' => now(),
                    'total_reversed_amount' => $totalReversed,
                    'total_reverse_failed_amount' => $totalRemaining,
                    'total_new_payout_amount' => $totalCredited,
                ]);

                DB::afterCommit(function () use ($drawId): void {
                    app(DashboardSummarySyncService::class)->dispatchForModelChange('lotto', [
                        'id' => (string) $drawId,
                    ], [LottoDashboardMetricConfig::SECTION_CASH, 'net']);
                });

                return [
                    'correction_id' => (int) $correction->id,
                    'status' => (string) $correction->status,
                    'total_credit_amount' => $totalCredited,
                    'total_reversed_amount' => $totalReversed,
                    'total_reverse_remaining_amount' => $totalRemaining,
                ];
            });
        } finally {
            optional($lock)->release();
        }
    }

    private function resolveMemberCode(int $memberKey): int
    {
        $row = DB::table('members')
            ->where('code', $memberKey)
            ->first(['code']);

        return (int) ($row->code ?? $memberKey);
    }

    private function syncAffectedTicketsResultState(LottoResultCorrection $correction): void
    {
        $normalizedNewResult = is_array($correction->new_result_number) ? $correction->new_result_number : [];
        $ticketIds = LottoResultCorrectionItem::query()
            ->where('correction_id', (int) $correction->id)
            ->pluck('ticket_id')
            ->unique()
            ->values()
            ->all();

        if ($ticketIds === []) {
            return;
        }

        $tickets = LottoTicket::query()
            ->whereIn('id', $ticketIds)
            ->where('status', '!=', 'cancelled')
            ->with(['items'])
            ->lockForUpdate()
            ->get();

        foreach ($tickets as $ticket) {
            $ticketWinAmount = 0.0;

            foreach ($ticket->items as $item) {
                $isWinner = $this->settlementService->isWinningBet(
                    (string) $item->bet_type,
                    (string) $item->number,
                    $normalizedNewResult
                );

                $winAmount = 0.0;
                if ($isWinner) {
                    if (isset($item->potential_win_amount_at_time) && $item->potential_win_amount_at_time !== null) {
                        $winAmount = round((float) $item->potential_win_amount_at_time, 2);
                    } else {
                        $winAmount = round((float) $item->amount * (float) $item->payout_at_time, 2);
                    }
                    $ticketWinAmount = round($ticketWinAmount + $winAmount, 2);
                }

                LottoTicketItem::query()
                    ->where('id', (int) $item->id)
                    ->update([
                        'result_status' => $isWinner ? 'win' : 'lose',
                        'win_amount' => $winAmount,
                    ]);
            }

            $ticket->update([
                'status' => 'resulted',
                'total_win_amount' => $ticketWinAmount,
            ]);
        }
    }
}
