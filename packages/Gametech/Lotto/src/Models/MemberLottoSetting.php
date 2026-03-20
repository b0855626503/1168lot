<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\MemberLottoSetting as MemberLottoSettingContract;
use Illuminate\Database\Eloquent\Model;

/**
 * Package-owned member lotto settings.
 *
 * Kept outside the core members table so this module can be removed cleanly.
 */
class MemberLottoSetting extends Model implements MemberLottoSettingContract
{
    protected $table = 'member_lotto_settings';

    protected $fillable = [
        'member_id',
        'rate_plan_id',
    ];

    public function member()
    {
        return $this->belongsTo(\Gametech\Member\Models\Member::class, 'member_id', 'code');
    }

    public function ratePlan()
    {
        return $this->belongsTo(LottoRatePlan::class, 'rate_plan_id');
    }
}
