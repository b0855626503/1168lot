<?php

namespace Gametech\Core\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use Gametech\Core\Contracts\AllLog as AllLogContract;
use Illuminate\Database\Eloquent\Model;


class AllLog extends Model implements AllLogContract
{
    use LaravelSubQueryTrait;

    protected $table = 'all_log';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'member_code',
        'member_user',
        'status_log',
        'before_credit',
        'amount',
        'after_credit',
        'pro_id',
        'pro_amount',
        'game_code',
        'gamebalance',
        'bank_payment_id',
        'username',
        'remark',
        'bonus',
        'ip',
        'type_record',
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
        'member_user' => 'string',
        'status_log' => 'boolean',
        'before_credit' => 'string',
        'amount' => 'decimal:2',
        'after_credit' => 'string',
        'pro_id' => 'integer',
        'pro_amount' => 'decimal:2',
        'game_code' => 'integer',
        'gamebalance' => 'decimal:2',
        'bank_payment_id' => 'integer',
        'username' => 'string',
        'remark' => 'string',
        'bonus' => 'string',
        'ip' => 'string',
        'type_record' => 'integer',
        'enable' => 'string',
        'user_create' => 'string',
        'user_update' => 'string'
    ];
}
