<?php

namespace Gametech\Lotto\Services\WinningReport\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WinningReportUsersQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function run(int $drawId, array $filters, int $perPage): LengthAwarePaginator
    {
        $driver = DB::connection()->getDriverName();
        $winningNumbersExpression = $driver === 'sqlite'
            ? 'GROUP_CONCAT(DISTINCT number) as winning_numbers'
            : 'GROUP_CONCAT(DISTINCT number ORDER BY number ASC SEPARATOR ",") as winning_numbers';

        $query = DB::table('lotto_winnings')
            ->where('draw_id', $drawId)
            ->groupBy('user_id', 'username')
            ->selectRaw('user_id')
            ->selectRaw('MAX(COALESCE(username, "")) as username')
            ->selectRaw('COALESCE(SUM(stake), 0) as total_stake')
            ->selectRaw('CASE WHEN SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) > 0 THEN NULL ELSE COALESCE(SUM(COALESCE(payout, 0)), 0) END as total_payout')
            ->selectRaw('CASE WHEN SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) > 0 THEN NULL ELSE (COALESCE(SUM(stake), 0) - COALESCE(SUM(COALESCE(payout, 0)), 0)) END as net_by_user')
            ->selectRaw('COUNT(*) as winning_bet_count')
            ->selectRaw($winningNumbersExpression)
            ->selectRaw('CASE WHEN SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) > 0 THEN "pending" WHEN SUM(CASE WHEN status = "credited" THEN 1 ELSE 0 END) = COUNT(*) THEN "credited" ELSE "settled" END as credited_status')
            ->orderBy('user_id');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        return $query->paginate($perPage);
    }
}
