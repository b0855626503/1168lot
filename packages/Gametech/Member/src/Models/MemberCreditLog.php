<?php

namespace Gametech\Member\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Admin\Models\AdminProxy;
use Gametech\Game\Models\GameProxy;
use Gametech\Game\Models\GameUserProxy;
use Gametech\Payment\Models\BankProxy;
use Gametech\Promotion\Models\PromotionProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use Gametech\Member\Contracts\MemberCreditLog as MemberCreditLogContract;

class MemberCreditLog extends Model implements MemberCreditLogContract
{
    use LaravelSubQueryTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'members_credit_log';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'member_code',
        'gameuser_code',
        'game_code',
        'bank_code',
        'pro_code',
        'refer_code',
        'refer_table',
        'credit_type',
        'amount',
        'bonus',
        'total',
        'balance_before',
        'balance_after',
        'credit',
        'credit_bonus',
        'credit_total',
        'credit_before',
        'credit_after',
        'ip',
        'auto',
        'remark',
        'kind',
        'enable',
        'emp_code',
        'user_create',
        'user_update',
        'amount_balance',
        'withdraw_limit',
        'withdraw_limit_amount'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'code' => 'integer',
        'member_code' => 'integer',
        'gameuser_code' => 'integer',
        'game_code' => 'integer',
        'bank_code' => 'integer',
        'pro_code' => 'integer',
        'refer_code' => 'integer',
        'refer_table' => 'string',
        'credit_type' => 'string',
        'amount' => 'decimal:2',
        'bonus' => 'decimal:2',
        'total' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'credit' => 'decimal:2',
        'credit_bonus' => 'decimal:2',
        'credit_total' => 'decimal:2',
        'credit_before' => 'decimal:2',
        'credit_after' => 'decimal:2',
        'ip' => 'string',
        'auto' => 'string',
        'remark' => 'string',
        'kind' => 'string',
        'enable' => 'string',
        'emp_code' => 'integer',
        'user_create' => 'string',
        'user_update' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    protected static $rules = [
        'member_code' => 'required|integer',
        'gameuser_code' => 'required|integer',
        'game_code' => 'required|integer',
        'bank_code' => 'required|integer',
        'pro_code' => 'required|integer',
        'refer_code' => 'required|integer',
        'refer_table' => 'required|string|max:10',
        'credit_type' => 'required|string',
        'amount' => 'required|numeric',
        'bonus' => 'required|numeric',
        'total' => 'required|numeric',
        'balance_before' => 'required|numeric',
        'balance_after' => 'required|numeric',
        'credit' => 'required|numeric',
        'credit_bonus' => 'required|numeric',
        'credit_total' => 'required|numeric',
        'credit_before' => 'required|numeric',
        'credit_after' => 'required|numeric',
        'ip' => 'required|string|max:30',
        'auto' => 'required|string',
        'remark' => 'required|string',
        'kind' => 'required|string|max:10',
        'enable' => 'required|string',
        'emp_code' => 'required|integer',
        'user_create' => 'required|string|max:100',
        'user_update' => 'required|string|max:100',
        'date_create' => 'nullable',
        'date_update' => 'nullable'
    ];


    public function scopeActive($query)
    {
        return $query->where('members_credit_log.enable','Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('members_credit_log.enable','N');
    }

    public function scopeAuto($query)
    {
        return $query->where('members_credit_log.auto','Y');
    }

    public function scopeNotauto($query)
    {
        return $query->where('members_credit_log.auto','N');
    }

    public function member()
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'member_code');
    }

    public function admin()
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'emp_code');
    }

    public function game_user()
    {
        return $this->belongsTo(GameUserProxy::modelClass(), 'gameuser_code');
    }

    public function game()
    {
        return $this->belongsTo(GameProxy::modelClass(), 'game_code');
    }

    public function promotion()
    {
        return $this->belongsTo(PromotionProxy::modelClass(), 'pro_code');
    }

    public function bank()
    {
        return $this->belongsTo(BankProxy::modelClass(), 'bank_code');
    }
}
