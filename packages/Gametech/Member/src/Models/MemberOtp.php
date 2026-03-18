<?php

namespace Gametech\Member\Models;

use DateTimeInterface;
use Gametech\Member\Contracts\MemberOtp as MemberOtpContract;
use Illuminate\Database\Eloquent\Model;

class MemberOtp extends Model implements MemberOtpContract
{
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'members_otp';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'mobile',
        'refer',
        'otp',
        'expired_at',
        'confirm',
        'enable',
        'user_create',
        'user_update'

    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'code' => 'integer',
        'mobile' => 'string',
        'refer' => 'string',
        'otp' => 'string',
        'expired_at' => 'datetime',
        'confirm' => 'string',
        'enable' => 'string',
        'user_create' => 'string',
        'user_update' => 'string'

    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'mobile' => 'required|string|max:10',
        'refer' => 'required|string|max:5',
        'otp' => 'required|string|max:5'
    ];

    public function isExpired()
    {
        return $this->expired_at->isPast();
    }

    public function isDiff()
    {
        return $this->expired_at->diffInSeconds();
    }

    public function scopeLast($query, $mobile)
    {
        return $query->where('mobile', $mobile)->latest()->first();
    }

}
