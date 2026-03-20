<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\MemberLottoPermission as MemberLottoPermissionContract;
use Illuminate\Database\Eloquent\Model;

/**
 * MemberLottoPermission - สิทธิ์การเล่นหวยของ member
 * เพื่อ visibility control
 */
class MemberLottoPermission extends Model implements MemberLottoPermissionContract
{
    protected $table = 'member_lotto_permissions';

    protected $fillable = [
        'member_id',
        'group_id',     // NULL = ทั้งหมด
        'is_allowed',   // whitelist / blacklist model
    ];

    protected $casts = [
        'is_allowed' => 'boolean',
    ];

    // Relationships
    public function member()
    {
        return $this->belongsTo(\Gametech\Member\Models\Member::class, 'member_id', 'code');
    }

    public function group()
    {
        return $this->belongsTo(LotteryGroup::class, 'group_id');
    }
}

