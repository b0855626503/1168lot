<?php

namespace Gametech\Payment\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Admin\Models\AdminProxy;
use Gametech\Member\Models\MemberProxy;
use Gametech\Payment\Contracts\WithdrawFree as WithdrawFreeContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class WithdrawFree extends Model implements WithdrawFreeContract
{
    use LaravelSubQueryTrait;

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'withdraws_free';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'member_code',
        'member_user',
        'account_code',
        'bankout',
        'bankm_code',
        'amount',
        'balance',
        'date_record',
        'timedept',
        'ck_deposit',
        'check_status',
        'ck_withdraw',
        'ck_balance',
        'oldcredit',
        'aftercredit',
        'fee',
        'remark',
        'ckb_user',
        'ckb_date',
        'ip',
        'ip_admin',
        'remark_admin',
        'emp_approve',
        'date_approve',
        'user_create',
        'user_update',
        'date_create',
        'date_update',
        'enable',
        'status',
        'ck_step2',
        'date_bank',
        'time_bank',
        'status_withdraw',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'code' => 'integer',
        'member_code' => 'integer',
        'member_user' => 'string',
        'account_code' => 'integer',
        'bankout' => 'string',
        'bankm_code' => 'integer',
        'amount' => 'decimal:2',
        'date_record' => 'date',
        'timedept' => 'string',
        'ck_deposit' => 'string',
        'check_status' => 'string',
        'ck_withdraw' => 'string',
        'ck_balance' => 'string',
        'oldcredit' => 'decimal:2',
        'aftercredit' => 'decimal:2',
        'fee' => 'decimal:2',
        'remark' => 'string',
        'ckb_user' => 'string',
        'ckb_date' => 'datetime:Y-m-d H:00',
        'ip' => 'string',
        'ip_admin' => 'string',
        'remark_admin' => 'string',
        'emp_approve' => 'integer',

        'user_create' => 'string',
        'user_update' => 'string',
        'enable' => 'string',
        'status' => 'integer',
        'ck_step2' => 'integer',
        'date_bank' => 'date',
        'time_bank' => 'string',
        'status_withdraw' => 'string',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    protected static $rules = [
        'member_code' => 'nullable|integer',
        'member_user' => 'required|string|max:100',
        'account_code' => 'nullable|integer',
        'bankout' => 'required|string|max:100',
        'bankm_code' => 'nullable|integer',
        'amount' => 'required|numeric',
        'date_record' => 'nullable',
        'timedept' => 'nullable',
        'ck_deposit' => 'required|string',
        'check_status' => 'required|string',
        'ck_withdraw' => 'required|string',
        'ck_balance' => 'required|string',
        'oldcredit' => 'nullable|numeric',
        'aftercredit' => 'required|numeric',
        'fee' => 'required|numeric',
        'remark' => 'required|string',
        'ckb_user' => 'required|string|max:191',
        'ckb_date' => 'nullable|datetime:Y-m-d H:00',
        'ip' => 'required|string|max:50',
        'ip_admin' => 'nullable|string|max:50',
        'remark_admin' => 'nullable|string',
        'emp_approve' => 'nullable|integer',
        'date_approve' => 'nullable',
        'user_create' => 'required|string|max:100',
        'user_update' => 'required|string|max:100',
        'enable' => 'required|string',
        'status' => 'nullable|boolean',
        'ck_step2' => 'required|integer',
        'date_bank' => 'nullable|datetime:Y-m-d',
        'time_bank' => 'required|string|max:10',
        'status_withdraw' => 'required|string',
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
        return $query->where('status', 0);
    }

    public function scopeComplete($query)
    {
        return $query->where('status', 1);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(BankProxy::modelClass(), 'bankm_code');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'member_code');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'emp_approve');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(BillFreeProxy::modelClass(), 'member_code', 'member_code');
    }

    public function member_credit(): MorphMany
    {
        return $this->morphMany(MemberProxy::modelClass(), 'credit_transaction');
    }

    public function payment_last()
    {
        return $this->hasOne(BankPaymentProxy::modelClass(), 'member_topup', 'member_code')->latest();
    }
}
