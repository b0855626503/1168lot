<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoTicketItem as LottoTicketItemContract;
use Illuminate\Database\Eloquent\Model;

/**
 * LottoTicketItem - บรรทัดการแทง
 */
class LottoTicketItem extends Model implements LottoTicketItemContract
{
    protected $table = 'lotto_ticket_items';

    protected $fillable = [
        'ticket_id',
        'bet_type',      // top_3, tod_3, etc.
        'number',        // เลข
        'amount',        // ยอดแทงบรรทัดนี้
        'payout_at_time',// อัตราจ่ายตอนแทง (snapshot)
        'result_status', // win | lose | null
        'win_amount',    // ยอดชนะ
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payout_at_time' => 'decimal:2',
        'win_amount' => 'decimal:2',
    ];

    // Relationships
    public function ticket()
    {
        return $this->belongsTo(LottoTicket::class, 'ticket_id');
    }
}

