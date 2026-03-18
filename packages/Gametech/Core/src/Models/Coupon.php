<?php

namespace Gametech\Core\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use Gametech\Core\Contracts\Coupon as CouponContract;
use Illuminate\Database\Eloquent\Model;


class Coupon extends Model implements CouponContract
{
    use LaravelSubQueryTrait;

    protected $table = 'coupons';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'name',
        'cashback',
        'amount',
        'value',
        'turnpro',
        'amount_limit',
        'date_start',
        'date_stop',
        'money',
        'refill_start',
        'refill_stop',
        'date_expire',
        'same_coupon',
        'norefill',
        'newuser',
        'gen',
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
        'name' => 'string',
        'status_log' => 'boolean',
        'cashback' => 'string',
        'amount' => 'integer',
        'date_expire' => 'integer',
        'value' => 'decimal:2',
        'turnpro' => 'decimal:2',
        'amount_limit' => 'decimal:2',
        'gen' => 'string',
        'enable' => 'string',
        'user_create' => 'string',
        'user_update' => 'string'
    ];

    public function couponlists()
    {
        return $this->hasMany(CouponListProxy::modelClass(), 'coupon_code','code');
    }
}
