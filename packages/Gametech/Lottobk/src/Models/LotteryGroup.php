<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LotteryGroup as LotteryGroupContract;
use Illuminate\Database\Eloquent\Model;

/**
 * LotteryGroup - กลุ่มหวย
 * เช่น หวยไทย / หุ้น / ต่างประเทศ
 */
class LotteryGroup extends Model implements LotteryGroupContract
{
    public $timestamps = false;
    protected $table = 'lotto_groups';

    protected $fillable = [
        'name',      // หมื่น / ออมสิน / ดาวโจนส์
        'code',      // unique: thailand, stock, international
        'is_enabled',
        'sort',
        'rollout_mode',
        'affect_existing_members',
        'policy_version',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'sort' => 'integer',
        'affect_existing_members' => 'boolean',
        'policy_version' => 'integer',
    ];

    // Relationships
    public function markets()
    {
        return $this->hasMany(LotteryMarket::class, 'group_id');
    }

    public function ratePlans()
    {
        return $this->hasMany(LottoRatePlan::class, 'group_id');
    }

    public function memberMarketPolicies()
    {
        return $this->hasMany(MemberLottoMarketPolicy::class, 'group_id');
    }
}

