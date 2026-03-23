<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoMarketBetSetting as LottoMarketBetSettingContract;
use Illuminate\Database\Eloquent\Model;

/**
 * LottoMarketBetSetting - ค่าพื้นฐานของแต่ละประเภทเดิมพันต่อหวย
 * ใช้เป็น default ตอนสร้างงวดใหม่
 */
class LottoMarketBetSetting extends Model implements LottoMarketBetSettingContract
{
    public $timestamps = false;
    protected $table = 'lotto_market_bet_settings';

    protected $fillable = [
        'market_id',
        'bet_type',      // top_3, tod_3, etc.
        'payout',        // อัตราจ่าย
        'discount_percent', // ส่วนลด (%)
        'is_enabled',
        'min_bet',       // ขั้นต่ำต่อโพย
        'max_bet',       // สูงสุดต่อโพย
        'max_per_number', // สูงสุดสะสมต่อเลข (ทั้งหวย)
    ];

    protected $casts = [
        'payout' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'is_enabled' => 'boolean',
        'min_bet' => 'decimal:2',
        'max_bet' => 'decimal:2',
        'max_per_number' => 'decimal:2',
    ];

    // Relationships
    public function market()
    {
        return $this->belongsTo(LotteryMarket::class, 'market_id');
    }
}

