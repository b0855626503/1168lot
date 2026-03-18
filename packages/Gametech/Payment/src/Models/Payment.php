<?php

namespace Gametech\Payment\Models;

use DateTimeInterface;
use Gametech\Payment\Contracts\Payment as PaymentContract;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model implements PaymentContract
{
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'payments';

    const CREATED_AT = 'date_create';

    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'date_pay',
        'name',
        'amount',
        'enable',
        'ip',
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
        'date_pay' => 'date',
        'name' => 'string',
        'amount' => 'decimal:2',
        'enable' => 'string',
        'ip' => 'string',
        'user_create' => 'string',
        'user_update' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'date_pay' => 'required',
        'name' => 'required|string|max:255',
        'amount' => 'required|numeric',
        'enable' => 'required|string',
        'ip' => 'required|string|max:255',
        'user_create' => 'required|string|max:100',
        'user_update' => 'required|string|max:100',
        'date_create' => 'required',
        'date_update' => 'required'
    ];
}
