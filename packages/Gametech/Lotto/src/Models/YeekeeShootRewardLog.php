<?php

namespace Gametech\Lotto\Models;

use Illuminate\Database\Eloquent\Model;

class YeekeeShootRewardLog extends Model
{
    protected $table = 'yeekee_shoot_reward_logs';

    protected $fillable = [
        'yeekee_round_id',
        'lotto_draw_id',
        'market_id',
        'yeekee_shoot_id',
        'member_id',
        'position',
        'credit_amount',
        'currency',
        'status',
        'reason',
        'policy_source',
        'policy_hash',
        'reward_ref_type',
        'idempotency_key',
        'wallet_transaction_id',
        'paid_at',
        'metadata_json',
    ];

    protected $casts = [
        'credit_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata_json' => 'array',
    ];
}
