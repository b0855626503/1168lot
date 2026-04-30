<?php

namespace Gametech\Lotto\Services;

use Illuminate\Support\Facades\DB;

class YeekeeVoidRefundPolicyService
{
    /**
     * @return array{should_void:bool,count:int,mode:string}
     */
    public function evaluateNumberThreshold(
        int $drawId,
        string $betType,
        string $number,
        int $minimumEntries,
        string $countMode = 'all_ticket_items_by_number'
    ): array {
        if ($minimumEntries <= 0) {
            return [
                'should_void' => false,
                'count' => 0,
                'mode' => $countMode,
            ];
        }

        $query = DB::table('lotto_ticket_items')
            ->join('lotto_tickets', 'lotto_tickets.id', '=', 'lotto_ticket_items.ticket_id')
            ->where('lotto_tickets.draw_id', $drawId)
            ->where('lotto_tickets.status', '!=', 'cancelled')
            ->where('lotto_ticket_items.bet_type', $betType)
            ->where('lotto_ticket_items.number', $number);

        if ($countMode === 'unique_members_by_number') {
            $count = (int) $query->distinct('lotto_tickets.member_id')->count('lotto_tickets.member_id');
        } else {
            $count = (int) $query->count();
            $countMode = 'all_ticket_items_by_number';
        }

        return [
            'should_void' => $count < $minimumEntries,
            'count' => $count,
            'mode' => $countMode,
        ];
    }
}
