<?php

namespace Gametech\Lotto\Services;

use App\Services\Dashboard\DashboardSummarySyncService;
use App\Services\Dashboard\LottoDashboardMetricConfig;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoResultCorrection;
use Gametech\Lotto\Models\LottoResultCorrectionItem;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LottoTicketItem;
use Gametech\Lotto\Models\LottoWinning;
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
        $lock = Cache::lock($lockKey, 600);
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
                            'status' => 'completed',
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
                        $debitResult = $this->walletTransactionService->debitAvailableMemberBalance(
                            memberId: $resolvedMemberCode,
                            requestedAmount: $required,
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
                        $debitAmount = round((float) ($debitResult['debited_amount'] ?? 0), 2);
                        $remaining = round((float) ($debitResult['remaining_amount'] ?? $required), 2);
                        $reverseTxnId = isset($debitResult['transaction_id']) ? (int) $debitResult['transaction_id'] : null;

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
                $this->syncWinningReportMaterializedData($correction, $draw);

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

    private function syncWinningReportMaterializedData(LottoResultCorrection $correction, LottoDraw $draw): void
    {
        $drawId = (int) $correction->draw_id;
        $normalizedNewResult = is_array($correction->new_result_number) ? $correction->new_result_number : [];
        $context = $this->resolveLottoContext($drawId);
        $settlementBatchId = $this->resolveCorrectionSettlementBatchId($correction, $draw, $context);

        LottoWinning::query()
            ->where('draw_id', $drawId)
            ->whereNull('voided_at')
            ->update([
                'voided_by_correction_id' => (int) $correction->id,
                'voided_at' => now(),
            ]);

        $winningItems = LottoTicketItem::query()
            ->join('lotto_tickets as t', 't.id', '=', 'lotto_ticket_items.ticket_id')
            ->leftJoin('members as m', 'm.code', '=', 't.member_id')
            ->where('t.draw_id', $drawId)
            ->where('t.status', '!=', 'cancelled')
            ->where('lotto_ticket_items.result_status', 'win')
            ->orderBy('lotto_ticket_items.id')
            ->get([
                'lotto_ticket_items.id as item_id',
                'lotto_ticket_items.ticket_id',
                'lotto_ticket_items.bet_type',
                'lotto_ticket_items.number',
                'lotto_ticket_items.amount',
                'lotto_ticket_items.payout_at_time',
                'lotto_ticket_items.win_amount',
                't.member_id',
                'm.user_name as member_username',
                'm.code as member_code',
            ]);

        foreach ($winningItems as $winningItem) {
            $matchedContext = $this->resolveMatchedContext(
                (string) $winningItem->bet_type,
                $normalizedNewResult
            );
            $payout = round((float) ($winningItem->win_amount ?? 0), 2);
            $stake = round((float) ($winningItem->amount ?? 0), 2);

            $memberUsername = trim((string) ($winningItem->member_username ?? ''));
            $displayUsername = $memberUsername !== ''
                ? $memberUsername
                : (string) ($winningItem->member_code ?? '');

            LottoWinning::query()->create([
                'draw_id' => $drawId,
                'bet_id' => (int) $winningItem->ticket_id,
                'bet_item_id' => (int) $winningItem->item_id,
                'ticket_no' => (string) $winningItem->ticket_id,
                'user_id' => (int) $winningItem->member_id,
                'username' => $displayUsername,
                'lottery_type' => (string) ($context['lottery_type'] ?? ''),
                'market' => (string) ($context['market'] ?? ''),
                'bet_type' => (string) $winningItem->bet_type,
                'number' => (string) $winningItem->number,
                'stake' => $stake,
                'odds' => round((float) ($winningItem->payout_at_time ?? 0), 4),
                'payout' => $payout,
                'net_profit' => round($stake - $payout, 2),
                'result_number' => $matchedContext['result_number'],
                'matched_rule' => $matchedContext['matched_rule'],
                'status' => 'settled',
                'settlement_batch_id' => $settlementBatchId,
                'settled_at' => now(),
                'credited_at' => null,
                'voided_by_correction_id' => null,
                'voided_at' => null,
            ]);
        }
    }

    /**
     * @param  array{lottery_type:string, market:?string}  $context
     */
    private function resolveCorrectionSettlementBatchId(
        LottoResultCorrection $correction,
        LottoDraw $draw,
        array $context
    ): int {
        $idempotencyKey = sprintf('result-correction:%d', (int) $correction->id);

        $settlementBatchId = DB::table('settlement_batches')
            ->where('idempotency_key', $idempotencyKey)
            ->value('id');

        if ($settlementBatchId !== null) {
            return (int) $settlementBatchId;
        }

        $batchId = DB::table('settlement_batches')->insertGetId([
            'draw_id' => (int) $correction->draw_id,
            'draw_date' => $draw->draw_date,
            'lottery_type' => (string) ($context['lottery_type'] ?? ''),
            'market' => (string) ($context['market'] ?? ''),
            'mode' => 'result_correction',
            'status' => 'settled',
            'started_at' => $correction->started_at ?? now(),
            'finished_at' => now(),
            'idempotency_key' => $idempotencyKey,
            'total_bets_processed' => 0,
            'total_winning_records' => 0,
            'total_stake' => 0,
            'total_payout' => 0,
            'error_message' => null,
            'triggered_by' => $correction->created_by !== null ? (string) $correction->created_by : 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) $batchId;
    }

    /**
     * @param  array<string, string>  $normalizedResult
     * @return array{result_number:?string, matched_rule:?string}
     */
    private function resolveMatchedContext(string $betType, array $normalizedResult): array
    {
        return match ($betType) {
            'top_3', 'tod_3', 'run_top' => [
                'result_number' => $normalizedResult['top_3'] ?? null,
                'matched_rule' => $betType,
            ],
            'top_2' => [
                'result_number' => $normalizedResult['top_2'] ?? null,
                'matched_rule' => $betType,
            ],
            'bottom_2', 'run_bottom' => [
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
