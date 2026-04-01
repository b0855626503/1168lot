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
        'package_id_at_time', // package id ตอนแทง (snapshot)
        'package_name_at_time', // package name ตอนแทง (snapshot)
        'payout_at_time',// อัตราจ่ายตอนแทง (snapshot)
        'discount_percent_at_time', // ส่วนลดตอนแทง (snapshot)
        'discount_amount_at_time', // ส่วนลดบาทตอนแทง (snapshot)
        'payable_amount_at_time', // จ่ายจริงหลังหักส่วนลด (snapshot)
        'potential_win_amount_at_time', // ยอดถูกรางวัลตอนแทง (snapshot)
        'calculated_values_at_bet_time', // structured snapshot for audit
        'result_status', // win | lose | null
        'win_amount',    // ยอดชนะ
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'package_id_at_time' => 'integer',
        'payout_at_time' => 'decimal:2',
        'discount_percent_at_time' => 'decimal:2',
        'discount_amount_at_time' => 'decimal:2',
        'payable_amount_at_time' => 'decimal:2',
        'potential_win_amount_at_time' => 'decimal:2',
        'calculated_values_at_bet_time' => 'array',
        'win_amount' => 'decimal:2',
    ];

    // Relationships
    public function ticket()
    {
        return $this->belongsTo(LottoTicket::class, 'ticket_id');
    }

    public function packageAtTime()
    {
        return $this->belongsTo(LottoGroupPackage::class, 'package_id_at_time');
    }
}

