<?php

namespace Gametech\Payment\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Admin\Models\AdminProxy;
use Gametech\Member\Models\MemberProxy;
use Gametech\Payment\Contracts\BankPayment as BankPaymentContract;
use Gametech\Promotion\Models\PromotionProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;


class BankPayment extends Model implements BankPaymentContract
{
    use LaravelSubQueryTrait;

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'bank_payment';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'bank',
        'account_code',
        'report_id',
        'bankstatus',
        'bankname',
        'txid',
        'bank_time',
        'time',
        'type',
        'title',
        'channel',
        'value',
        'comm_value',
        'fee',
        'detail',
        'checktime',
        'tx_hash',
        'status',
        'webcode',
        'before_credit',
        'tranferer',
        'after_credit',
        'webbefore',
        'webafter',
        'score',
        'proclick',
        'pro_check',
        'pro_id',
        'pro_amount',
        'user_id',
        'date_topup',
        'codename',
        'msg',
        'atranferer',
        'topupstatus',
        'bonus',
        'user_get',
        'today_pro',
        'prochek_date',
        'procheck_user',
        'member_topup',
        'emp_topup',
        'ip_admin',
        'ip_topup',
        'date_approve',
        'date_cancel',
        'remark_admin',
        'enable',
        'user_create',
        'user_update',
        'date_create',
        'date_update',
        'create_by',
        'autocheck',
        'amount',
        'topup_by',
        'rate',
        'usdt',
        // ---- deposit idempotency state
        'deposit_status',
        'deposit_started_at',
        'deposited_at',
        'finalized_at',
        'deposit_attempt',
        'deposit_last_error',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'code' => 'integer',
        'bank' => 'string',
        'account_code' => 'integer',
        'report_id' => 'string',
        'bankstatus' => 'integer',
        'bankname' => 'string',
        'txid' => 'string',
        'bank_time' => 'datetime',
        'time' => 'string',
        'type' => 'string',
        'title' => 'string',
        'channel' => 'string',
        'value' => 'decimal:2',
        'comm_value' => 'decimal:2',
        'fee' => 'float',
        'detail' => 'string',
        'checktime' => 'string',
        'tx_hash' => 'string',
        'status' => 'integer',
        'webcode' => 'integer',
        'before_credit' => 'decimal:2',
        'tranferer' => 'string',
        'after_credit' => 'decimal:2',
        'webbefore' => 'decimal:2',
        'webafter' => 'decimal:2',
        'score' => 'decimal:2',
        'proclick' => 'string',
        'pro_check' => 'string',
        'pro_id' => 'integer',
        'pro_amount' => 'decimal:2',
        'user_id' => 'string',
        'date_topup' => 'datetime',
        'codename' => 'string',
        'msg' => 'string',
        'atranferer' => 'string',
        'topupstatus' => 'string',
        'bonus' => 'decimal:2',
        'user_get' => 'string',
        'today_pro' => 'integer',
        'prochek_date' => 'datetime',
        'procheck_user' => 'string',
        'member_topup' => 'integer',
        'emp_topup' => 'integer',
        'ip_admin' => 'string',
        'ip_topup' => 'string',
        'date_approve' => 'datetime',
        'date_cancel' => 'datetime',
        'remark_admin' => 'string',
        'enable' => 'string',
        'user_create' => 'string',
        'user_update' => 'string',
        'date_create' => 'datetime:Y-m-d H:i:s',
        'date_update' => 'datetime:Y-m-d H:i:s',
        'create_by' => 'string',
        'autocheck' => 'string',
        'topup_by' => 'string',
        'amount' => 'decimal:2',
        // ---- deposit idempotency state
        'deposit_status' => 'string',
        'deposit_started_at' => 'datetime:Y-m-d H:i:s',
        'deposited_at' => 'datetime:Y-m-d H:i:s',
        'finalized_at' => 'datetime:Y-m-d H:i:s',
        'deposit_attempt' => 'integer',
        'deposit_last_error' => 'string',
    ];



    public function scopeIncome($query)
    {
        return $query->where('bank_payment.value', '>', 0);
    }

    public function scopeProfit($query)
    {
        return $query->where('bank_payment.value', '<', 0);
    }

    public function scopeActive($query)
    {
        return $query->where('bank_payment.enable', 'Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('bank_payment.enable', 'N');
    }

    public function scopeWaiting($query)
    {
        return $query->where('bank_payment.status', 0);
    }

    public function scopeComplete($query)
    {
        return $query->where('bank_payment.status', 1);
    }

    public function scopeReject($query)
    {
        return $query->where('bank_payment.status', 2);
    }

    public function scopeClearout($query)
    {
        return $query->where('bank_payment.status', 3);
    }

    public function scopeCheck($query)
    {
        return $query->where('bank_payment.pro_check', 'Y');
    }

    public function scopeUncheck($query)
    {
        return $query->where('bank_payment.pro_check', 'N');
    }


    public function member()
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'member_topup');
    }

    public function BankAccount()
    {
        return $this->belongsTo(BankAccountProxy::modelClass(), 'account_code');
    }

    public function bank_account()
    {
        return $this->belongsTo(BankAccountProxy::modelClass(), 'account_code');
    }

    public function promotion()
    {
        return $this->belongsTo(PromotionProxy::modelClass(), 'pro_id');
    }

    public function admin()
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'emp_topup');
    }


}
