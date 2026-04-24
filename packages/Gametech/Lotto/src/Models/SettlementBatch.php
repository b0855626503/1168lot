<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\SettlementBatch as SettlementBatchContract;
use Illuminate\Database\Eloquent\Model;

class SettlementBatch extends Model implements SettlementBatchContract
{
    protected $table = 'settlement_batches';

    protected $fillable = [
        'draw_id',
        'lottery_type',
        'market',
        'mode',
        'status',
        'started_at',
        'finished_at',
        'total_bets_processed',
        'total_winning_records',
        'total_stake',
        'total_payout',
        'error_message',
        'triggered_by',
        'idempotency_key',
    ];

    protected $casts = [
        'draw_id' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'total_bets_processed' => 'integer',
        'total_winning_records' => 'integer',
        'total_stake' => 'decimal:2',
        'total_payout' => 'decimal:2',
    ];

    public function draw()
    {
        return $this->belongsTo(LottoDraw::class, 'draw_id');
    }

    public function winnings()
    {
        return $this->hasMany(LottoWinning::class, 'settlement_batch_id');
    }
}
