<?php

namespace Gametech\Lotto\Services;

use App\Services\Dashboard\DashboardSummarySyncService;
use App\Services\Dashboard\LottoDashboardMetricConfig;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletTransactionService
{
    /**
     * @param array<string, mixed> $meta
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
     * @param array<string, mixed> $meta
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
     * @param array<string, mixed> $meta
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

        return $txId;
    }
}
