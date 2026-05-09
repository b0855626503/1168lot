<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoResultCorrectionItem as LottoResultCorrectionItemContract;
use Illuminate\Database\Eloquent\Model;

class LottoResultCorrectionItem extends Model implements LottoResultCorrectionItemContract
{
    protected $table = 'lotto_result_correction_items';

    protected $fillable = [
        'correction_id',
        'draw_id',
        'ticket_id',
        'member_id',
        'old_win_amount',
        'new_win_amount',
        'reverse_required_amount',
        'reverse_debited_amount',
        'reverse_remaining_amount',
        'new_credit_amount',
        'status',
        'reverse_wallet_txn_id',
        'new_credit_wallet_txn_id',
        'note',
    ];

    protected $casts = [
        'correction_id' => 'integer',
        'draw_id' => 'integer',
        'ticket_id' => 'integer',
        'member_id' => 'integer',
        'old_win_amount' => 'decimal:2',
        'new_win_amount' => 'decimal:2',
        'reverse_required_amount' => 'decimal:2',
        'reverse_debited_amount' => 'decimal:2',
        'reverse_remaining_amount' => 'decimal:2',
        'new_credit_amount' => 'decimal:2',
        'reverse_wallet_txn_id' => 'integer',
        'new_credit_wallet_txn_id' => 'integer',
    ];

    public function correction()
    {
        return $this->belongsTo(LottoResultCorrection::class, 'correction_id');
    }

    public function draw()
    {
        return $this->belongsTo(LottoDraw::class, 'draw_id');
    }

    public function ticket()
    {
        return $this->belongsTo(LottoTicket::class, 'ticket_id');
    }
}
