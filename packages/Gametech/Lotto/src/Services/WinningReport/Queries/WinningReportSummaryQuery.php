<?php

namespace Gametech\Lotto\Services\WinningReport\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        $latestDrawId = $this->resolveLatestDrawIdForDetails($drawIds, $filters);
        if ($latestDrawId === null) {
            return [
                'draw_ids' => [],
                'latest_draw_id' => null,
                'total_stake' => 0.0,
                'total_payout' => 0.0,
                'net_profit_loss' => 0.0,
                'winner_count' => 0,
                'winning_ticket_count' => 0,
                'settlement_status' => 'settled',
                'has_pending' => false,
            ];
        }

        $aggregatesQuery = DB::table('lotto_winnings')
            ->whereIn('draw_id', $drawIds)
            ->whereNull('voided_at');

        $this->applyUserFilter($aggregatesQuery, $filters);

        $aggregates = $aggregatesQuery
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
            'latest_draw_id' => $latestDrawId,
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
            $dateColumn = Schema::hasColumn('settlement_batches', 'draw_date') ? 'draw_date' : 'started_at';
            $query->whereDate($dateColumn, (string) $filters['date']);
        }

        if (! empty($filters['lottery_type'])) {
            $query->where('lottery_type', (string) $filters['lottery_type']);
        }

        if (! empty($filters['market'])) {
            $query->where('market', (string) $filters['market']);
        }

        if (! empty($filters['user_id']) || ! empty($filters['username'])) {
            $query->whereExists(function ($subQuery) use ($filters): void {
                $subQuery->selectRaw('1')
                    ->from('lotto_winnings as w')
                    ->whereColumn('w.draw_id', 'settlement_batches.draw_id')
                    ->whereNull('w.voided_at');

                if (! empty($filters['user_id'])) {
                    $subQuery->where('w.user_id', (int) $filters['user_id']);
                }

                if (! empty($filters['username'])) {
                    $subQuery->where('w.username', 'like', '%'.(string) $filters['username'].'%');
                }
            });
        }

        return $query->pluck('draw_id')->map(static fn ($id): int => (int) $id)->values()->all();
    }

    /**
     * @param  array<int, int>  $drawIds
     * @param  array<string, mixed>  $filters
     */
    private function resolveLatestDrawIdForDetails(array $drawIds, array $filters): ?int
    {
        $latestQuery = DB::table('lotto_winnings')
            ->whereIn('draw_id', $drawIds)
            ->whereNull('voided_at');

        $this->applyUserFilter($latestQuery, $filters);

        $latestWinningDrawId = $latestQuery
            ->orderByDesc('draw_id')
            ->value('draw_id');

        if ($latestWinningDrawId !== null) {
            return (int) $latestWinningDrawId;
        }

        return $drawIds[0] ?? null;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyUserFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['username'])) {
            $query->where('username', 'like', '%'.(string) $filters['username'].'%');
        }
    }
}
