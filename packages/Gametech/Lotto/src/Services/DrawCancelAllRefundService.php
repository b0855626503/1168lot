<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class DrawCancelAllRefundService
{
    public function __construct(
        private WalletTransactionService $walletTransactionService
    ) {
    }

    /**
     * @return array{cancelled_tickets:int,refunded_amount:float,group_code:string}
     */
    public function cancelAllActiveTickets(
        LottoDraw $lockedDraw,
        string $reason = 'งดออกผล',
        string $createdByType = 'admin',
        ?int $createdById = null,
        ?string $groupCode = null
    ): array {
        if (! Schema::hasTable('wallet_transactions')) {
            throw new InvalidArgumentException('ไม่พบตาราง wallet_transactions สำหรับคืนเงิน');
        }

        $resolvedGroupCode = trim((string) $groupCode);
        if ($resolvedGroupCode === '') {
            $resolvedGroupCode = 'LOTTO_DRAW_CANCEL_' . (int) $lockedDraw->id . '_' . now()->format('YmdHis');
        }

        $tickets = LottoTicket::query()
            ->with(['items:id,ticket_id,bet_type,number,amount'])
            ->where('draw_id', (int) $lockedDraw->id)
            ->where('status', 'active')
            ->lockForUpdate()
            ->get();

        $cancelledTickets = 0;
        $totalRefund = 0.0;

        foreach ($tickets as $ticket) {
            $refundAmount = round((float) ($ticket->total_net_amount ?? $ticket->total_amount ?? 0), 2);
            if ($refundAmount > 0) {
                $debitTxn = DB::table('wallet_transactions')
                    ->where('member_id', (int) $ticket->member_id)
                    ->where('ref_type', 'LOTTO_BET')
                    ->where('ref_id', (int) $ticket->id)
                    ->orderByDesc('id')
                    ->first(['id']);

                $this->walletTransactionService->creditMemberBalance(
                    memberId: (int) $ticket->member_id,
                    amount: $refundAmount,
                    refType: 'LOTTO_CANCEL',
                    refId: (int) $ticket->id,
                    refCode: (string) $ticket->id,
                    groupCode: $resolvedGroupCode,
                    relatedTxnId: isset($debitTxn->id) ? (int) $debitTxn->id : null,
                    meta: [
                        'draw_id' => (int) $ticket->draw_id,
                        'ticket_id' => (int) $ticket->id,
                        'cancel_scope' => 'draw',
                    ],
                    createdByType: $createdByType,
                    createdById: $createdById,
                    description: 'คืนเงินจากการยกเลิกโพยทั้งงวด'
                );
            }

            foreach ($ticket->items as $item) {
                $exposure = DB::table('lotto_number_exposures')
                    ->where('draw_id', (int) $ticket->draw_id)
                    ->where('bet_type', (string) $item->bet_type)
                    ->where('number', (string) $item->number)
                    ->lockForUpdate()
                    ->first(['id', 'sold_amount']);

                if (! $exposure) {
                    continue;
                }

                $nextAmount = max(0, round((float) ($exposure->sold_amount ?? 0) - (float) ($item->amount ?? 0), 2));
                DB::table('lotto_number_exposures')
                    ->where('id', (int) $exposure->id)
                    ->update([
                        'sold_amount' => $nextAmount,
                        'updated_at' => now(),
                    ]);
            }

            $updatePayload = [
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $createdById,
                'refund_amount' => $refundAmount,
                'total_win_amount' => 0,
            ];

            if (Schema::hasColumn('lotto_tickets', 'reason')) {
                $updatePayload['reason'] = $reason;
            }

            $ticket->update($updatePayload);

            $cancelledTickets++;
            $totalRefund += $refundAmount;
        }

        return [
            'cancelled_tickets' => $cancelledTickets,
            'refunded_amount' => round($totalRefund, 2),
            'group_code' => $resolvedGroupCode,
        ];
    }
}
