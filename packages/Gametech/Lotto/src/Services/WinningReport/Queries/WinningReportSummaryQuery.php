<?php

namespace Gametech\Lotto\Services\WinningReport\Queries;

use Illuminate\Support\Facades\DB;

class WinningReportSummaryQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function run(array $filters): array
    {
        $drawIds = $this->resolveDrawIds($filters);
        if ($drawIds === []) {
            return [
                'draw_ids' => [],
                'total_stake' => 0.0,
                'total_payout' => 0.0,
                'net_profit_loss' => 0.0,
                'winner_count' => 0,
                'winning_ticket_count' => 0,
                'settlement_status' => 'settled',
                'has_pending' => false,
            ];
        }

        $aggregates = DB::table('lotto_winnings')
            ->whereIn('draw_id', $drawIds)
            ->selectRaw('COALESCE(SUM(stake), 0) as total_stake')
            ->selectRaw('COALESCE(SUM(COALESCE(payout, 0)), 0) as total_payout_raw')
            ->selectRaw('COUNT(DISTINCT user_id) as winner_count')
            ->selectRaw('COUNT(*) as winning_ticket_count')
            ->selectRaw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count')
            ->first();

        $hasPending = (int) ($aggregates->pending_count ?? 0) > 0;
        $totalStake = round((float) ($aggregates->total_stake ?? 0), 2);
        $totalPayout = $hasPending ? null : round((float) ($aggregates->total_payout_raw ?? 0), 2);

        $latestBatchStatus = (string) DB::table('settlement_batches')
            ->whereIn('draw_id', $drawIds)
            ->orderByDesc('id')
            ->value('status');

        return [
            'draw_ids' => $drawIds,
            'total_stake' => $totalStake,
            'total_payout' => $totalPayout,
            'net_profit_loss' => $totalPayout === null ? null : round($totalStake - $totalPayout, 2),
            'winner_count' => (int) ($aggregates->winner_count ?? 0),
            'winning_ticket_count' => (int) ($aggregates->winning_ticket_count ?? 0),
            'settlement_status' => $latestBatchStatus !== '' ? $latestBatchStatus : 'settled',
            'has_pending' => $hasPending,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, int>
     */
    private function resolveDrawIds(array $filters): array
    {
        $query = DB::table('settlement_batches')
            ->select('draw_id')
            ->distinct()
            ->orderByDesc('draw_id');

        if (! empty($filters['draw_id'])) {
            $query->where('draw_id', (int) $filters['draw_id']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('started_at', (string) $filters['date']);
        }

        if (! empty($filters['lottery_type'])) {
            $query->where('lottery_type', (string) $filters['lottery_type']);
        }

        if (! empty($filters['market'])) {
            $query->where('market', (string) $filters['market']);
        }

        return $query->pluck('draw_id')->map(static fn ($id): int => (int) $id)->values()->all();
    }
}
