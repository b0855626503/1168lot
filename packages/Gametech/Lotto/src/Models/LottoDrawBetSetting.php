<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoDrawBetSetting as LottoDrawBetSettingContract;
use Illuminate\Database\Eloquent\Model;

/**
 * LottoDrawBetSetting - ค่าพื้นฐานเดิมพันของแต่ละงวด
 * Snapshot จาก LottoMarketBetSetting ตอนเปิดงวด
 * ห้ามแก้หลังเปิดแล้ว
 */
class LottoDrawBetSetting extends Model implements LottoDrawBetSettingContract
{
    public $timestamps = false;
    protected $table = 'lotto_draw_bet_settings';

    protected $fillable = [
        'draw_id',
        'bet_type',
        'is_enabled',
        'min_bet',
        'max_bet',
        'max_per_number',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'min_bet' => 'decimal:2',
        'max_bet' => 'decimal:2',
        'max_per_number' => 'decimal:2',
    ];

    // Relationships
    public function draw()
    {
        return $this->belongsTo(LottoDraw::class, 'draw_id');
    }
}

