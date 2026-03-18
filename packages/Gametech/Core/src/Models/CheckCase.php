<?php

namespace Gametech\Core\Models;

use Gametech\Payment\Models\BankAccountProxy;
use Gametech\Payment\Models\BankProxy;
use Illuminate\Database\Eloquent\Model;
use Gametech\Core\Contracts\CheckCase as CheckCaseContract;

class CheckCase extends Model implements CheckCaseContract
{
    protected $table = 'check_case';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'bank_code',
        'txid',
        'username',
        'name',
        'amount',
        'payamount',
        'status',
        'detail',
        'url',
        'qrcode',
        'download',
        'enable',
        'method',
        'expired_date',
        'user_create',
        'user_update',
        'bankAccountNumber',
        'bankAccountName',
        'bankName',
        'promptpayNumber',
    ];


    public function bank_account()
    {
        return $this->belongsTo(BankAccountProxy::modelClass(), 'bank_code');
    }

    public function bank()
    {
        return $this->belongsTo(BankProxy::modelClass(), 'bank_code');
    }

}