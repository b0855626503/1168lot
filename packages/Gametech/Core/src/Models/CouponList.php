<?php

namespace Gametech\Core\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Member\Models\MemberProxy;
use Illuminate\Database\Eloquent\Model;
use Gametech\Core\Contracts\CouponList as CouponListContract;

class CouponList extends Model implements CouponListContract
{
    use LaravelSubQueryTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'coupons_list';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'name',
        'coupon_code',
        'member_code',
        'cashback',
        'amount',
        'value',
        'turnpro',
        'amount_limit',
        'money',
        'date_start',
        'date_stop',
        'date_expire',
        'status',
        'enable',
        'user_create',
        'user_update',
        'date_create',
        'date_update'
    ];

    public function members()
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'member_code','code');
    }

    public function coupons()
    {
        return $this->belongsTo(CouponProxy::modelClass(), 'coupon_code','code');
    }
}
