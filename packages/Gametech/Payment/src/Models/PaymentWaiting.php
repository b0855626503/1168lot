<?php

namespace Gametech\Payment\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Game\Models\GameProxy;
use Gametech\Member\Models\MemberProxy;
use Gametech\Payment\Contracts\PaymentWaiting as PaymentWaitingContract;
use Gametech\Promotion\Models\PromotionProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentWaiting extends Model implements PaymentWaitingContract
{
    use LaravelSubQueryTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'payments_waiting';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'member_code',
        'game_code',
        'pro_code',
        'emp_code',
        'transfer_type',
        'amount',
        'bonus',
        'total',
        'credit',
        'credit_bonus',
        'credit_before',
        'credit_after',
        'credit_balance',
        'remark',
        'confirm',
        'date_approve',
        'ip',
        'ip_admin',
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
        'game_code' => 'integer',
        'pro_code' => 'integer',
        'emp_code' => 'integer',
        'transfer_type' => 'boolean',
        'amount' => 'decimal:2',
        'bonus' => 'decimal:2',
        'total' => 'decimal:2',
        'credit' => 'decimal:2',
        'credit_bonus' => 'decimal:2',
        'credit_before' => 'decimal:2',
        'credit_after' => 'decimal:2',
        'credit_balance' => 'decimal:2',
        'remark' => 'string',
        'confirm' => 'string',
        'date_approve' => 'datetime',
        'ip' => 'string',
        'ip_admin' => 'string',
        'enable' => 'string',
        'user_create' => 'string',
        'user_update' => 'string'
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

    public function scopeWaiting($query)
    {
        return $query->where('confirm', 'X');
    }

    public function scopeComplete($query)
    {
        return $query->where('confirm', 'Y');
    }

    public function scopeReject($query)
    {
        return $query->where('confirm', 'N');
    }

    public function member()
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'member_code');
    }


    public function promotion()
    {
        return $this->belongsTo(PromotionProxy::modelClass(), 'pro_code');
    }

    public function game()
    {
        return $this->belongsTo(GameProxy::modelClass(), 'game_code');
    }
}
