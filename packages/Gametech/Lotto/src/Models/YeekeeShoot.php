<?php

namespace Gametech\Lotto\Models;

use Illuminate\Database\Eloquent\Model;

class YeekeeShoot extends Model
{
    protected $table = 'yeekee_shoots';

    protected $fillable = [
        'yeekee_round_id',
        'lotto_draw_id',
        'market_id',
        'member_id',
        'position',
        'number_text',
        'number_value',
        'submitted_at',
        'ip_address',
        'user_agent',
        'metadata_json',
    ];

    protected $casts = [
        'metadata_json' => 'array',
        'submitted_at' => 'datetime',
    ];
}
