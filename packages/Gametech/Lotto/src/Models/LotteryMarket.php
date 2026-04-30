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
    public const RESULT_MODE_NORMAL = 'normal';
    public const RESULT_MODE_YEEKEE = 'yeekee';

    public const DRAW_SCHEDULE_TYPE_MANUAL = 'manual';
    public const DRAW_SCHEDULE_TYPE_WEEKLY = 'weekly';
    public const DRAW_SCHEDULE_TYPE_MONTHLY = 'monthly';

    public const DRAW_MODE_MANUAL = 'manual';
    public const DRAW_MODE_DAILY = 'daily';
    public const DRAW_MODE_WEEKDAYS = 'weekdays';
    public const DRAW_MODE_WED_SAT_SUN = 'wed_sat_sun';

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
        'result_mode',
        'draw_mode',
        'draw_schedule_type',
        'draw_days',
        'draw_dates',
        'auto_open_time',
        'auto_close_time',
        'auto_result_time',
        'result_url',
        'auto_settle_on_result',
        'auto_refund_on_no_result',
        'notify_result_telegram',
        'is_enabled',
        'affect_existing_members',
        'policy_version',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'auto_settle_on_result' => 'boolean',
        'auto_refund_on_no_result' => 'boolean',
        'notify_result_telegram' => 'boolean',
        'affect_existing_members' => 'boolean',
        'policy_version' => 'integer',
        'draw_days' => 'array',
        'draw_dates' => 'array',
    ];

    public static function resultModes(): array
    {
        return [
            self::RESULT_MODE_NORMAL,
            self::RESULT_MODE_YEEKEE,
        ];
    }

    public static function drawScheduleTypes(): array
    {
        return [
            self::DRAW_SCHEDULE_TYPE_MANUAL,
            self::DRAW_SCHEDULE_TYPE_WEEKLY,
            self::DRAW_SCHEDULE_TYPE_MONTHLY,
        ];
    }

    public static function drawModes(): array
    {
        return [
            self::DRAW_MODE_MANUAL,
            self::DRAW_MODE_DAILY,
            self::DRAW_MODE_WEEKDAYS,
            self::DRAW_MODE_WED_SAT_SUN,
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

    public function yeekeeSetting()
    {
        return $this->hasOne(YeekeeMarketSetting::class, 'market_id');
    }

    public function contents()
    {
        return $this->hasMany(LottoMarketContent::class, 'market_id');
    }

    public function enabledContents()
    {
        return $this->hasMany(LottoMarketContent::class, 'market_id')
            ->where('is_enabled', true);
    }
}
