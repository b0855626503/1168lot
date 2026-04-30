<?php

namespace Gametech\Lotto\Models;

use Illuminate\Database\Eloquent\Model;

class LottoMarketContent extends Model
{
    protected $table = 'lotto_market_contents';

    protected $fillable = [
        'market_id',
        'locale',
        'title',
        'summary',
        'rules_content',
        'schedule_content',
        'prize_content',
        'formula_content',
        'seo_title',
        'seo_description',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function market()
    {
        return $this->belongsTo(LotteryMarket::class, 'market_id');
    }
}
