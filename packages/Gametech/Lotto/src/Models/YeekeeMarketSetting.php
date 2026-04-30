<?php

namespace Gametech\Lotto\Models;

use Illuminate\Database\Eloquent\Model;

class YeekeeMarketSetting extends Model
{
    protected $table = 'yeekee_market_settings';

    protected $fillable = [
        'market_id',
        'round_config',
        'formula_config',
        'reward_config',
        'refund_config',
        'ui_config',
        'reward_enabled',
        'refund_if_bet_entries_below_min',
    ];

    protected $casts = [
        'round_config' => 'array',
        'formula_config' => 'array',
        'reward_config' => 'array',
        'refund_config' => 'array',
        'ui_config' => 'array',
        'reward_enabled' => 'boolean',
        'refund_if_bet_entries_below_min' => 'boolean',
    ];

    public function market()
    {
        return $this->belongsTo(LotteryMarket::class, 'market_id');
    }
}
