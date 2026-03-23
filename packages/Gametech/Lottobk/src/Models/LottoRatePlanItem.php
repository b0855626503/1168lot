<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoRatePlanItem as LottoRatePlanItemContract;
use Illuminate\Database\Eloquent\Model;

/**
 * LottoRatePlanItem - รายละเอียดอัตราจ่าย
 */
class LottoRatePlanItem extends Model implements LottoRatePlanItemContract
{
    public $timestamps = false;
    protected $table = 'lotto_rate_plan_items';

    protected $fillable = [
        'rate_plan_id',
        'bet_type',          // top_3, tod_3, etc.
        'payout',            // อัตราจ่าย เช่น 800 = x800
        'discount_percent',  // ส่วนลด %
    ];

    protected $casts = [
        'payout' => 'decimal:2',
        'discount_percent' => 'decimal:2',
    ];

    // Relationships
    public function ratePlan()
    {
        return $this->belongsTo(LottoRatePlan::class, 'rate_plan_id');
    }
}

