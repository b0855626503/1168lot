<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoWinning as LottoWinningContract;
use Illuminate\Database\Eloquent\Model;

class LottoWinning extends Model implements LottoWinningContract
{
    protected $table = 'lotto_winnings';

    protected $fillable = [
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
    ];

    protected $casts = [
        'draw_id' => 'integer',
        'bet_id' => 'integer',
        'bet_item_id' => 'integer',
        'user_id' => 'integer',
        'stake' => 'decimal:2',
        'odds' => 'decimal:4',
        'payout' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'settlement_batch_id' => 'integer',
        'settled_at' => 'datetime',
        'credited_at' => 'datetime',
    ];

    public function draw()
    {
        return $this->belongsTo(LottoDraw::class, 'draw_id');
    }

    public function settlementBatch()
    {
        return $this->belongsTo(SettlementBatch::class, 'settlement_batch_id');
    }
}
