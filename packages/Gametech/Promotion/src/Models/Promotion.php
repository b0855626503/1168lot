<?php

namespace Gametech\Promotion\Models;

use DateTimeInterface;
use Gametech\Member\Models\MemberCreditLogProxy;
use Gametech\Payment\Models\BillProxy;
use Gametech\Payment\Models\PaymentWaitingProxy;
use Gametech\Promotion\Contracts\Promotion as PromotionContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class Promotion extends Model implements PromotionContract
{
    use  LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'promotions';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'id',
        'name_th',
        'sort',
        'turnpro',
        'length_type',
        'amount_min',
        'bonus_min',
        'bonus_max',
        'bonus_price',
        'bonus_percent',
        'use_manual',
        'use_wallet',
        'use_auto',
        'content',
        'filepic',
        'active',
        'enable',
        'withdraw_limit',
        'withdraw_limit_rate',
        'user_create',
        'user_update',
        'slot',
        'casino',
        'sport',
        'huay',
        'lotto',
        'keno',
        'card',
        'cock',
        'poker',
        'fish',
        'cal_type',
        'cal_time',
        'cal_del',
        'cal_pro',
        'last_calculated_at'
    ];

    protected $casts = [
        'code' => 'integer',
        'id' => 'string',
        'name_th' => 'string',
        'sort' => 'integer',
        'withdraw_limit_rate' => 'decimal:2',
        'turnpro' => 'decimal:2',
        'withdraw_limit' => 'decimal:2',
        'length_type' => 'string',
        'bonus_min' => 'decimal:2',
        'bonus_max' => 'decimal:2',
        'bonus_price' => 'decimal:2',
        'bonus_percent' => 'decimal:2',
        'use_manual' => 'string',
        'use_wallet' => 'string',
        'use_auto' => 'string',
        'content' => 'string',
        'filepic' => 'string',
        'active' => 'string',
        'enable' => 'string',
        'user_create' => 'string',
        'user_update' => 'string',
        'date_create' => 'datetime:Y-m-d H:i:s',
        'date_update' => 'datetime:Y-m-d H:i:s',
    ];

    protected static $rules = [
        'name_th' => 'required|string|max:100',
        'sort' => 'required|integer',
        'withdraw_limit_rate' => 'required|numeric',
        'turnpro' => 'required|numeric',
        'length_type' => 'required|string|max:10',
        'bonus_min' => 'required|numeric',
        'bonus_max' => 'required|numeric',
        'bonus_price' => 'required|numeric',
        'bonus_percent' => 'required|numeric',
        'use_manual' => 'required|string',
        'use_wallet' => 'required|string',
        'use_auto' => 'required|string',
        'content' => 'required|string',
        'filepic' => 'required|string|max:255',
        'active' => 'required|string',
        'enable' => 'required|string',
        'user_create' => 'required|string|max:100',
        'user_update' => 'required|string|max:100',
        'date_create' => 'required',
        'date_update' => 'required'
    ];

    protected static function booted()
    {
        static::addGlobalScope('code', function (Builder $builder) {
            $builder->where('code', '<>', 0);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('enable','Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('enable','N');
    }


    public function bills()
    {
        return $this->hasMany(BillProxy::modelClass(), 'pro_code','code');
    }

    public function billss()
    {
        return $this->hasMany(BillProxy::modelClass(), 'pro_code','code');
    }

    public function bill()
    {
        return $this->hasOne(BillProxy::modelClass(), 'pro_code');
    }

    public function pro()
    {
        return $this->hasOne(MemberCreditLogProxy::modelClass(), 'pro_code','code');
    }

    public function payments_waiting()
    {
        return $this->hasMany(PaymentWaitingProxy::modelClass(), 'pro_code');
    }



}
