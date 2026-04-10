<?php

namespace Gametech\Lotto\Services;

use App\Events\RealtimeMemberActivityUpdated;
use App\Services\Dashboard\DashboardSummarySyncService;
use App\Services\Dashboard\LottoDashboardMetricConfig;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletTransactionService
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function debitMemberBalance(
        int $memberId,
        float $amount,
        string $refType,
        ?int $refId = null,
        ?string $refCode = null,
        ?string $groupCode = null,
        ?int $relatedTxnId = null,
        array $meta = [],
        string $createdByType = 'system',
        ?int $createdById = null,
        ?string $description = null
    ): int {
        return $this->applyMemberBalance(
            memberId: $memberId,
            direction: 'DEBIT',
            amount: $amount,
            refType: $refType,
            refId: $refId,
            refCode: $refCode,
            groupCode: $groupCode,
            relatedTxnId: $relatedTxnId,
            meta: $meta,
            createdByType: $createdByType,
            createdById: $createdById,
            description: $description
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function creditMemberBalance(
        int $memberId,
        float $amount,
        string $refType,
        ?int $refId = null,
        ?string $refCode = null,
        ?string $groupCode = null,
        ?int $relatedTxnId = null,
        array $meta = [],
        string $createdByType = 'system',
        ?int $createdById = null,
        ?string $description = null
    ): int {
        return $this->applyMemberBalance(
            memberId: $memberId,
            direction: 'CREDIT',
            amount: $amount,
            refType: $refType,
            refId: $refId,
            refCode: $refCode,
            groupCode: $groupCode,
            relatedTxnId: $relatedTxnId,
            meta: $meta,
            createdByType: $createdByType,
            createdById: $createdById,
            description: $description
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function applyMemberBalance(
        int $memberId,
        string $direction,
        float $amount,
        string $refType,
        ?int $refId,
        ?string $refCode,
        ?string $groupCode,
        ?int $relatedTxnId,
        array $meta,
        string $createdByType,
        ?int $createdById,
        ?string $description
    ): int {
        $normalizedAmount = round($amount, 2);
        if ($normalizedAmount <= 0) {
            throw new RuntimeException('Transaction amount must be greater than zero');
        }

        $member = DB::table('members')
            ->where('code', $memberId)
            ->lockForUpdate()
            ->first(['code', 'balance']);

        if (! $member) {
            throw new RuntimeException('Member not found');
        }

        $balanceBefore = round((float) ($member->balance ?? 0), 2);
        $balanceAfter = $direction === 'DEBIT'
            ? round($balanceBefore - $normalizedAmount, 2)
            : round($balanceBefore + $normalizedAmount, 2);

        if ($direction === 'DEBIT' && $balanceAfter < 0) {
            throw new RuntimeException('ยอดเงินไม่เพียงพอ');
        }

        DB::table('members')
            ->where('code', $memberId)
            ->update([
                'balance' => $balanceAfter,
                'date_update' => now(),
            ]);

        $createdAt = now();

        $txId = (int) DB::table('wallet_transactions')->insertGetId([
            'member_id' => $memberId,
            'scope' => 'MEMBER',
            'game_user_id' => null,
            'direction' => $direction,
            'amount' => $normalizedAmount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'ref_type' => $refType,
            'ref_id' => $refId,
            'ref_code' => $refCode,
            'group_code' => $groupCode,
            'related_txn_id' => $relatedTxnId,
            'status' => 'SUCCESS',
            'description' => $description,
            'meta' => empty($meta) ? null : json_encode($meta, JSON_UNESCAPED_UNICODE),
            'created_by_type' => $createdByType,
            'created_by_id' => $createdById,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        if (in_array($refType, array_merge(
            LottoDashboardMetricConfig::salesRefTypes(),
            LottoDashboardMetricConfig::payoutRefTypes(),
            LottoDashboardMetricConfig::refundRefTypes()
        ), true)) {
            DB::afterCommit(function () use ($txId, $createdAt): void {
                app(DashboardSummarySyncService::class)->dispatchForModelChange('lotto', [
                    'id' => (string) $txId,
                    'cash_dates' => [$createdAt],
                ], [LottoDashboardMetricConfig::SECTION_CASH, 'net']);
            });
        }

        $this->broadcastMemberRealtimeActivity(
            memberId: $memberId,
            direction: $direction,
            amount: $normalizedAmount,
            balanceAfter: $balanceAfter,
            refType: $refType,
            refId: $refId,
            meta: $meta
        );

        return $txId;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function broadcastMemberRealtimeActivity(
        int $memberId,
        string $direction,
        float $amount,
        float $balanceAfter,
        string $refType,
        ?int $refId,
        array $meta
    ): void {
        $payload = $this->resolveRealtimePayload($direction, $amount, $balanceAfter, $refType, $refId, $meta);
        if ($payload === null) {
            return;
        }

        broadcast(new RealtimeMemberActivityUpdated(
            $memberId,
            $payload['method'],
            $payload['event'],
            $payload['data'],
            $payload['message']
        ));
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array{method:string,event:string,message:string,data:array<string,mixed>}|null
     */
    private function resolveRealtimePayload(
        string $direction,
        float $amount,
        float $balanceAfter,
        string $refType,
        ?int $refId,
        array $meta
    ): ?array {
        if ($refType === 'LOTTO_SETTLE_WIN') {
            $lottoContext = $this->resolveLottoRealtimeContext($meta);
            $message = 'โพยหวยของคุณถูกรางวัล '.$this->formatMoney($amount).' บาท';
            if ($lottoContext['market_name'] !== null && $lottoContext['draw_date'] !== null) {
                $message .= ' ('.$lottoContext['market_name'].' งวดวันที่ '.$lottoContext['draw_date'].')';
            }

            return [
                'method' => 'lotto',
                'event' => 'lotto.ticket_won',
                'message' => $message,
                'data' => [
                    'amount' => $amount,
                    'balance' => $balanceAfter,
                    'reference_code' => $refId ?? 0,
                    'reason' => 'lotto_ticket_won',
                    'direction' => strtolower($direction),
                    'draw_id' => $lottoContext['draw_id'],
                    'ticket_id' => $lottoContext['ticket_id'],
                    'market_name' => $lottoContext['market_name'],
                    'draw_date' => $lottoContext['draw_date'],
                ],
            ];
        }

        if ($refType === 'LOTTO_CANCEL') {
            $lottoContext = $this->resolveLottoRealtimeContext($meta);
            $message = 'ระบบคืนเงินโพยหวย '.$this->formatMoney($amount).' บาท';
            if ($lottoContext['market_name'] !== null && $lottoContext['draw_date'] !== null) {
                $message .= ' ('.$lottoContext['market_name'].' งวดวันที่ '.$lottoContext['draw_date'].')';
            }

            return [
                'method' => 'lotto',
                'event' => 'lotto.ticket_refunded',
                'message' => $message,
                'data' => [
                    'amount' => $amount,
                    'balance' => $balanceAfter,
                    'reference_code' => $refId ?? 0,
                    'reason' => 'lotto_ticket_refunded',
                    'direction' => strtolower($direction),
                    'draw_id' => $lottoContext['draw_id'],
                    'ticket_id' => $lottoContext['ticket_id'],
                    'market_name' => $lottoContext['market_name'],
                    'draw_date' => $lottoContext['draw_date'],
                    'cancel_scope' => $meta['cancel_scope'] ?? null,
                ],
            ];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array{draw_id:?int,ticket_id:?int,market_name:?string,draw_date:?string}
     */
    private function resolveLottoRealtimeContext(array $meta): array
    {
        $drawId = isset($meta['draw_id']) ? (int) $meta['draw_id'] : null;
        $ticketId = isset($meta['ticket_id']) ? (int) $meta['ticket_id'] : null;

        if ($drawId === null || $drawId <= 0) {
            return [
                'draw_id' => null,
                'ticket_id' => $ticketId,
                'market_name' => null,
                'draw_date' => null,
            ];
        }

        $row = DB::table('lotto_draws as draws')
            ->leftJoin('lotto_markets as markets', 'markets.id', '=', 'draws.market_id')
            ->where('draws.id', $drawId)
            ->first([
                'draws.id as draw_id',
                'draws.draw_date as draw_date',
                'markets.name as market_name',
            ]);

        return [
            'draw_id' => $row ? (int) ($row->draw_id ?? 0) : $drawId,
            'ticket_id' => $ticketId,
            'market_name' => $row ? $this->normalizeNullableText($row->market_name ?? null) : null,
            'draw_date' => $row ? $this->normalizeNullableText($row->draw_date ?? null) : null,
        ];
    }

    private function formatMoney(float $amount): string
    {
        return number_format(round($amount, 2), 2, '.', ',');
    }

    private function normalizeNullableText(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
