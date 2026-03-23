<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoRatePlan as LottoRatePlanContract;
use Illuminate\Database\Eloquent\Model;

/**
 * LottoRatePlan - อัตราจ่าย
 * ประกอบด้วย items (rate ต่อประเภทเดิมพัน + ส่วนลด)
 */
class LottoRatePlan extends Model implements LottoRatePlanContract
{
    public $timestamps = false;
    protected $table = 'lotto_rate_plans';

    protected $fillable = [
        'group_id',
        'name',        // Default Plan, VIP Plan
        'description',
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

    public function items()
    {
        return $this->hasMany(LottoRatePlanItem::class, 'rate_plan_id');
    }
}

