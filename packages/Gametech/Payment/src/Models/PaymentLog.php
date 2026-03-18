<?php

namespace Gametech\Payment\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Payment\Contracts\PaymentLog as PaymentLogContract;
use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model implements PaymentLogContract
{
    use LaravelSubQueryTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public $table = 'payments_log';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';
//    public $timestamps = false;

    protected $primaryKey = 'code';

    protected $fillable = [
        'bill_code',
        'transfer_type',
        'token',
        'member_code',
        'game_code',
        'amount',
        'confirm',
        'status',
        'msg',
        'showmsg',
        'enable',
        'ip',
        'user_create',
        'user_update',
        'date_create',
        'date_update'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'code' => 'integer',
        'bill_code' => 'integer',
        'transfer_type' => 'boolean',
        'token' => 'string',
        'member_code' => 'integer',
        'game_code' => 'integer',
        'amount' => 'decimal:2',
        'confirm' => 'string',
        'status' => 'string',
        'msg' => 'string',
        'showmsg' => 'string',
        'enable' => 'string',
        'ip' => 'string',
        'user_create' => 'string',
        'user_update' => 'string',
        'date_create' => 'datetime:Y-m-d H:i:s',
        'date_update' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'bill_code' => 'nullable|integer',
        'transfer_type' => 'required|boolean',
        'token' => 'required|string|max:255',
        'member_code' => 'required|integer',
        'game_code' => 'required|integer',
        'amount' => 'required|numeric',
        'confirm' => 'required|string',
        'status' => 'required|string|max:50',
        'msg' => 'required|string|max:255',
        'showmsg' => 'required|string',
        'enable' => 'required|string',
        'ip' => 'required|string|max:255',
        'user_create' => 'required|string|max:100',
        'user_update' => 'required|string|max:100',
        'date_create' => 'nullable',
        'date_update' => 'nullable'
    ];
}
