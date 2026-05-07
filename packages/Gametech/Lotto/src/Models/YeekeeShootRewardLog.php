<?php

namespace Gametech\Lotto\Models;

use Illuminate\Database\Eloquent\Model;

class YeekeeShootRewardLog extends Model
{
    protected $table = 'yeekee_shoot_reward_logs';

    protected $fillable = [
        'yeekee_round_id',
        'member_id',
        'position',
        'credit_amount',
        'reward_ref_type',
        'idempotency_key',
    ];
}
