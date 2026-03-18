<?php

namespace Gametech\Payment\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Gametech\Payment\Contracts\Bonus as BonusContract;

class Bonus extends Model implements BonusContract
{
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'bonus';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'member_code',
        'name',
        'value',
        'cashback',
        'refer_coupon',
        'turnpro',
        'amount_limit',
        'status',
        'date_expire',
        'user_create',
        'user_update'
    ];
}
