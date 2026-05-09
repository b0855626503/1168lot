<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LottoResultCorrection;
use Gametech\Lotto\Models\LottoResultCorrectionItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ResultCorrectionRetryDebitService
{
    private const REF_REVERSE = 'LOTTO_RESULT_CORRECTION_REVERSE';

    public function __construct(
        private WalletTransactionService $walletTransactionService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function retryRemaining(int $correctionId, ?int $itemId = null, ?int $actorId = null, ?int $memberId = null): array
    {
        $correction = LottoResultCorrection::query()->find($correctionId);
        if (! $correction instanceof LottoResultCorrection) {
            throw new InvalidArgumentException('ไม่พบ correction ที่ต้องการ retry');
        }

        return DB::transaction(function () use ($correction, $itemId, $actorId, $memberId): array {
            $query = LottoResultCorrectionItem::query()
                ->where('correction_id', (int) $correction->id)
                ->where('reverse_remaining_amount', '>', 0)
                ->lockForUpdate()
                ->orderBy('id');

            if ($itemId !== null) {
                $query->where('id', $itemId);
            }
            if ($memberId !== null) {
                $query->where('member_id', $memberId);
            }

            $items = $query->get();

            $retried = 0;
            foreach ($items as $item) {
                $remaining = round((float) $item->reverse_remaining_amount, 2);
                if ($remaining <= 0) {
                    continue;
                }

                $resolvedMemberCode = $this->resolveMemberCode((int) $item->member_id);
                $debitResult = $this->walletTransactionService->debitAvailableMemberBalance(
                    memberId: $resolvedMemberCode,
                    requestedAmount: $remaining,
                    refType: self::REF_REVERSE,
                    refId: $this->buildRetryRefId((int) $item->id),
                    refCode: (string) $item->draw_id,
                    groupCode: 'LOTTO_RESULT_CORRECTION_'.(int) $correction->id,
                    meta: [
                        'retry' => true,
                        'correction_id' => (int) $correction->id,
                        'correction_item_id' => (int) $item->id,
                        'draw_id' => (int) $item->draw_id,
                        'ticket_id' => (int) $item->ticket_id,
                    ],
                    createdByType: 'admin',
                    createdById: $actorId,
                    description: 'หักคืนยอดค้างแก้ไขผลหวย'
                );
                $debitAmount = round((float) ($debitResult['debited_amount'] ?? 0), 2);

                if ($debitAmount <= 0) {
                    $item->update([
                        'status' => 'reverse_failed',
                        'note' => 'retry_no_balance',
                    ]);

                    continue;
                }
                $txnId = isset($debitResult['transaction_id']) ? (int) $debitResult['transaction_id'] : null;

                $newDebited = round((float) $item->reverse_debited_amount + $debitAmount, 2);
                $newRemaining = round((float) $item->reverse_required_amount - $newDebited, 2);

                $item->update([
                    'reverse_debited_amount' => $newDebited,
                    'reverse_remaining_amount' => max(0, $newRemaining),
                    'reverse_wallet_txn_id' => $txnId,
                    'status' => $newRemaining > 0 ? 'reverse_partial' : 'completed',
                    'note' => $newRemaining > 0 ? 'retry_partial' : 'retry_completed',
                ]);
                $retried++;
            }

            $remainingTotal = round((float) LottoResultCorrectionItem::query()
                ->where('correction_id', (int) $correction->id)
                ->sum('reverse_remaining_amount'), 2);

            $correction->update([
                'status' => $remainingTotal > 0 ? 'partial_failed' : 'completed',
                'total_reverse_failed_amount' => $remainingTotal,
            ]);

            return [
                'correction_id' => (int) $correction->id,
                'retried_items' => $retried,
                'remaining_amount' => $remainingTotal,
                'status' => (string) $correction->status,
            ];
        });
    }

    private function resolveMemberCode(int $memberKey): int
    {
        $row = DB::table('members')
            ->where('code', $memberKey)
            ->first(['code']);

        return (int) ($row->code ?? $memberKey);
    }

    private function buildRetryRefId(int $correctionItemId): int
    {
        $timePart = (int) floor(microtime(true) * 1000) % 1000000;

        return ($correctionItemId * 1000000) + $timePart;
    }
}
