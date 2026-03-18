<?php

namespace Gametech\Payment\Models;

use DateTimeInterface;
use Gametech\Payment\Contracts\BankAccount as BankAccountContract;
use Illuminate\Database\Eloquent\Model;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class BankAccount extends Model implements BankAccountContract
{

    use LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'banks_account';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'local',
        'acc_name',
        'acc_no',
        'banks',
        'user_name',
        'user_pass',
        'balance',
        'status_auto',
        'status_topup',
        'bank_type',
        'smestatus',
        'checktime',
        'display_wallet',
        'sort',
        'enable',
        'user_create',
        'user_update',
        'date_create',
        'date_update',
        'website',
        'webhook',
        'pattern',
        'auto_transfer',
        'min_amount',
        'max_amount',
        'pompay_default',
        'secert',
        'token',
        'qrcode',
        'filepic',
        'rate_auto',
        'rate',
        'rate_update',
        'slip',
        'payment',
        'deposit_min',
        'bonus',
        'bonus_max',
        'date_start',
        'time_start',
        'date_end',
        'time_end',
        'start_at',
        'end_at',
        'remark',
        'visibility_scope',
    ];

    protected $casts = [
        'code' => 'integer',
        'acc_name' => 'string',
        'acc_no' => 'string',
        'banks' => 'integer',
        'user_name' => 'string',
        'user_pass' => 'string',
        'balance' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'status_auto' => 'string',
        'auto_transfer' => 'string',
        'status_topup' => 'string',
        'bank_type' => 'integer',
        'smestatus' => 'string',
        'checktime' => 'datetime',
        'display_wallet' => 'string',
        'sort' => 'integer',
        'enable' => 'string',
        'pattern' => 'string',
        'webhook' => 'string',
        'date_start' => 'date',
        'date_end'   => 'date',
        'start_at'   => 'datetime',
        'end_at'     => 'datetime',
        'user_create' => 'string',
        'user_update' => 'string',
        'date_create' => 'datetime:Y-m-d H:i',
        'date_update' => 'datetime:Y-m-d H:i',
        'rate_update' => 'datetime:Y-m-d H:i',
    ];

    public static $rules = [
        'acc_name' => 'required|string|max:255',
        'acc_no' => 'required|numeric|max:10',
        'banks' => 'nullable|integer',
        'user_name' => 'required|string|max:100',
        'user_pass' => 'required|string|max:100',
        'balance' => 'required|numeric',
        'min_amount' => 'numeric',
        'max_amount' => 'numeric',
        'status_auto' => 'required|string',
        'status_topup' => 'required|string',
        'bank_type' => 'required|integer',
        'smestatus' => 'required|string',
        'checktime' => 'nullable',
        'display_wallet' => 'required|string',
        'sort' => 'required|integer',
        'enable' => 'nullable|string',
        'website' => 'string',
        'webhook' => 'string',
        'pattern' => 'string',
        'user_create' => 'required|string|max:100',
        'user_update' => 'required|string|max:100',
        'date_create' => 'nullable',
        'date_update' => 'nullable',
        'created_at' => 'nullable',
        'updated_at' => 'nullable'
    ];

    protected $hidden = [
        'user_name', 'user_pass'
    ];


    public function scopeIn($query)
    {
        return $query->where('banks_account.bank_type', 1);
    }

    public function scopeOut($query)
    {
        return $query->where('banks_account.bank_type', 2);
    }

    public function scopeActive($query)
    {
        return $query->where('banks_account.enable', 'Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('banks_account.enable', 'N');
    }

    public function scopeShow($query)
    {
        return $query->where('banks_account.display_wallet', 'Y');
    }

    public function scopeHide($query)
    {
        return $query->where('banks_account.display_wallet', 'N');
    }

    public function scopeTopup($query)
    {
        return $query->where('banks_account.status_topup', 'Y');
    }

    public function scopeUntopup($query)
    {
        return $query->where('banks_account.status_topup', 'N');
    }


    public function bank_payment()
    {
        return $this->hasMany(BankPaymentProxy::modelClass(), 'account_code');
    }

    public function bank()
    {
        return $this->belongsTo(BankProxy::modelClass(), 'banks');
    }
}
