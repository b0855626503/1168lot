<?php

namespace Gametech\Lotto\Models;

use Illuminate\Database\Eloquent\Model;

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
        'shoots_snapshot_json',
        'shoots_snapshot_hash',
        'shoots_frozen_at',
    ];

    protected $casts = [
        'config_snapshot_json' => 'array',
        'shoots_snapshot_json' => 'array',
    ];
}
