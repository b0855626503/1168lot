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
    public const DRAW_MODE_MANUAL = 'manual';
    public const DRAW_MODE_DAILY = 'daily';
    public const DRAW_MODE_WEEKDAYS = 'weekdays';

    public $timestamps = false;
    protected $table = 'lotto_markets';

    protected $fillable = [
        'group_id',
        'name',      // ออมสิน / ธกส
        'name_en',
        'name_kh',
        'name_laos',
        'logo',
        'icon',
        'code',      // unique: gsb, kbank
        'draw_mode',
        'auto_open_time',
        'auto_close_time',
        'auto_result_time',
        'result_url',
        'is_enabled',
        'affect_existing_members',
        'policy_version',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'affect_existing_members' => 'boolean',
        'policy_version' => 'integer',
    ];

    public static function drawModes(): array
    {
        return [
            self::DRAW_MODE_MANUAL,
            self::DRAW_MODE_DAILY,
            self::DRAW_MODE_WEEKDAYS,
        ];
    }

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

    public function memberPolicies()
    {
        return $this->hasMany(MemberLottoMarketPolicy::class, 'market_id');
    }

    public function resultSources()
    {
        return $this->hasMany(LottoResultSource::class, 'market_id');
    }
}

