<?php

namespace Gametech\Payment\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Admin\Models\AdminProxy;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Models\MemberRemarkProxy;
use Gametech\Payment\Contracts\WithdrawNew as WithdrawNewContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class WithdrawNew extends Model implements WithdrawNewContract
{
    use LaravelSubQueryTrait;

    public $with = ['bank','bankaccount','frombank'];
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'withdraws_new';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'to_bank',
        'to_name',
        'to_account',
        'account_code',
        'from_bank',
        'from_name',
        'from_account',
        'amount',
        'remark',
        'ref',
        'ip',
        'ip_admin',
        'remark_admin',
        'emp_approve',
        'emp_name',
        'user_create',
        'user_update',
        'date_create',
        'date_update',
        'enable',
        'status',
        'date_bank',
        'time_bank',
        'status_withdraw'

    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'code' => 'integer',
        'to_bank' => 'integer',
        'to_name' => 'string',
        'to_account' => 'string',
        'from_bank' => 'integer',
        'from_name' => 'string',
        'from_account' => 'string',
        'account_code' => 'integer',
        'ref' => 'string',
        'emp_name' => 'string',
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
        'api' => 'string',


    ];

    /**
     * Validation rules
     *
     * @var array
     */
    protected static $rules = [
        'to_bank' => 'required|integer',
        'to_account' => 'required|string',
        'to_name' => 'required|string',
        'from_bank' => 'integer',
        'from_name' => 'string',
        'from_account' => 'string',
        'account_code' => 'integer',
        'amount' => 'required|numeric',
        'ip' => 'required|string|max:50',
        'ip_admin' => 'string|max:50',
        'remark_admin' => 'nullable|string',
        'emp_approve' => 'integer',
        'user_create' => 'required|string|max:100',
        'user_update' => 'required|string|max:100',
        'enable' => 'string',
        'emp_name' => 'string',
        'ref' => 'string',
        'status_withdraw' => 'required|string',
        'status' => 'integer',
        'date_bank' => 'nullable|datetime:Y-m-d',
        'time_bank' => 'required|string|max:10'
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
        return $this->belongsTo(BankProxy::modelClass(), 'to_bank');
    }

    public function frombank(): BelongsTo
    {
        return $this->belongsTo(BankProxy::modelClass(), 'from_bank');
    }

    public function admin()
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'emp_approve');
    }

    public function bankaccount()
    {
        return $this->belongsTo(BankAccountProxy::modelClass(), 'account_code');
    }



}
