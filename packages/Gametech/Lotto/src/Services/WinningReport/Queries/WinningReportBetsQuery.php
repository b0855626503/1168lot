<?php

namespace Gametech\Lotto\Services\WinningReport\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WinningReportBetsQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function run(int $drawId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = DB::table('lotto_winnings')
            ->where('draw_id', $drawId)
            ->select([
                'id',
                'draw_id',
                'bet_id',
                'bet_item_id',
                'ticket_no',
                'user_id',
                'username',
                'lottery_type',
                'market',
                'bet_type',
                'number',
                'stake',
                'odds',
                'payout',
                'net_profit',
                'result_number',
                'matched_rule',
                'status',
                'settlement_batch_id',
                'settled_at',
                'credited_at',
                'created_at',
            ])
            ->orderByDesc('id');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['bet_type'])) {
            $query->where('bet_type', (string) $filters['bet_type']);
        }

        if (! empty($filters['number'])) {
            $query->where('number', (string) $filters['number']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        return $query->paginate($perPage);
    }
}
