<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\MemberLottoMarketPolicy as MemberLottoMarketPolicyContract;
use Illuminate\Database\Eloquent\Model;

class MemberLottoMarketPolicy extends Model implements MemberLottoMarketPolicyContract
{
    protected $table = 'member_lotto_market_policies';

    protected $fillable = [
        'member_id',
        'group_id',
        'market_id',
        'is_allowed',
        'source',
        'policy_version',
    ];

    protected $casts = [
        'is_allowed' => 'boolean',
        'policy_version' => 'integer',
    ];

    public function member()
    {
        return $this->belongsTo(\Gametech\Member\Models\Member::class, 'member_id', 'code');
    }

    public function group()
    {
        return $this->belongsTo(LotteryGroup::class, 'group_id');
    }

    public function market()
    {
        return $this->belongsTo(LotteryMarket::class, 'market_id');
    }
}

