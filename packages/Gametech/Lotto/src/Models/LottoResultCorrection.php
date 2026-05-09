<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoResultCorrection as LottoResultCorrectionContract;
use Illuminate\Database\Eloquent\Model;

class LottoResultCorrection extends Model implements LottoResultCorrectionContract
{
    protected $table = 'lotto_result_corrections';

    protected $fillable = [
        'draw_id',
        'old_result_number',
        'new_result_number',
        'old_result_hash',
        'new_result_hash',
        'source',
        'reason',
        'status',
        'ticket_count',
        'affected_ticket_count',
        'old_winning_ticket_count',
        'new_winning_ticket_count',
        'total_reversed_amount',
        'total_reverse_failed_amount',
        'total_new_payout_amount',
        'created_by',
        'started_at',
        'finished_at',
        'error_message',
    ];

    protected $casts = [
        'draw_id' => 'integer',
        'old_result_number' => 'array',
        'new_result_number' => 'array',
        'ticket_count' => 'integer',
        'affected_ticket_count' => 'integer',
        'old_winning_ticket_count' => 'integer',
        'new_winning_ticket_count' => 'integer',
        'total_reversed_amount' => 'decimal:2',
        'total_reverse_failed_amount' => 'decimal:2',
        'total_new_payout_amount' => 'decimal:2',
        'created_by' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function draw()
    {
        return $this->belongsTo(LottoDraw::class, 'draw_id');
    }

    public function items()
    {
        return $this->hasMany(LottoResultCorrectionItem::class, 'correction_id');
    }
}
