<?php

namespace Gametech\Lotto\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YeekeeRound extends Model
{
    protected $table = 'yeekee_rounds';

    protected $fillable = [
        'market_id',
        'lotto_draw_id',
        'round_date',
        'round_no',
        'bet_open_at',
        'bet_close_at',
        'shoot_open_at',
        'shoot_close_at',
        'result_compute_at',
        'expected_settlement_deadline_at',
        'status',
        'last_shoot_position',
        'shoot_count',
        'config_snapshot_json',
        'shoot_snapshot_json',
        'shoot_snapshot_hash',
        'shoot_closed_at',
    ];

    protected $casts = [
        'last_shoot_position' => 'integer',
        'shoot_count' => 'integer',
        'shoot_closed_at' => 'datetime',
        'config_snapshot_json' => 'array',
        'shoot_snapshot_json' => 'array',
    ];

    public function market(): BelongsTo
    {
        return $this->belongsTo(LotteryMarket::class, 'market_id');
    }
}
