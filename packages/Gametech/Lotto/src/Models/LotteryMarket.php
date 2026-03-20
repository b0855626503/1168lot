<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LotteryMarket as LotteryMarketContract;
use Illuminate\Database\Eloquent\Model;

/**
 * LotteryMarket - รายการหวย
 * เช่น ออมสิน / ธกส / ดาวโจนส์
 */
class LotteryMarket extends Model implements LotteryMarketContract
{
    public $timestamps = false;
    protected $table = 'lotto_markets';

    protected $fillable = [
        'group_id',
        'name',      // ออมสิน / ธกส
        'code',      // unique: gsb, kbank
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    // Relationships
    public function group()
    {
        return $this->belongsTo(LotteryGroup::class, 'group_id');
    }

    public function draws()
    {
        return $this->hasMany(LottoDraw::class, 'market_id');
    }

    public function defaultBetSettings()
    {
        return $this->hasMany(LottoMarketBetSetting::class, 'market_id');
    }
}

