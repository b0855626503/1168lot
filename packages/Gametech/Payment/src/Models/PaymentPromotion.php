<?php

namespace Gametech\Payment\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Member\Models\MemberProxy;
use Gametech\Payment\Contracts\PaymentPromotion as PaymentPromotionContract;
use Gametech\Promotion\Models\PromotionProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPromotion extends Model implements PaymentPromotionContract
{
    use LaravelSubQueryTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'payments_promotion';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'member_code',
        'downline_code',
        'pro_code',
        'amount',
        'credit',
        'credit_bonus',
        'credit_before',
        'credit_after',
        'credit_balance',
        'ip',
        'remark',
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
        'member_code' => 'integer',
        'downline_code' => 'integer',
        'pro_code' => 'integer',
        'amount' => 'decimal:2',
        'credit' => 'decimal:2',
        'credit_bonus' => 'decimal:2',
        'credit_before' => 'decimal:2',
        'credit_after' => 'decimal:2',
        'credit_balance' => 'decimal:2',
        'ip' => 'string',
        'remark' => 'string',
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
        'member_code' => 'required|integer',
        'downline_code' => 'required|integer',
        'pro_code' => 'required|integer',
        'amount' => 'required|numeric',
        'credit' => 'required|numeric',
        'credit_bonus' => 'required|numeric',
        'credit_before' => 'required|numeric',
        'credit_after' => 'required|numeric',
        'credit_balance' => 'required|numeric',
        'ip' => 'required|string|max:30',
        'remark' => 'required|string|max:255',
        'enable' => 'required|string',
        'user_create' => 'required|string|max:100',
        'user_update' => 'required|string|max:100',
        'date_create' => 'nullable',
        'date_update' => 'nullable'
    ];

    protected static function booted()
    {
        static::addGlobalScope('code', function (Builder $builder) {
            $builder->where('code', '<>', 0);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('enable', 'Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('enable', 'N');
    }

    public function scopeAff($query)
    {
        return $query->where('pro_code', 6);
    }

    public function getSum()
    {
        return PaymentPromotion::withSum('items:credit_bonus');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'member_code');
    }

    public function promotion()
    {
        return $this->belongsTo(PromotionProxy::modelClass(), 'pro_code');
    }

    public function down(): BelongsTo
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'downline_code');
    }
}
