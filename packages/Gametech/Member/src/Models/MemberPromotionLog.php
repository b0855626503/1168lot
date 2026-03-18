<?php

namespace Gametech\Member\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Gametech\Member\Contracts\MemberPromotionLog as MemberPromotionLogContract;

class MemberPromotionLog extends Model implements MemberPromotionLogContract
{


    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'members_promotionlog';


    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';


    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'date_start',
        'bill_code',
        'game_code',
        'game_name',
        'member_code',
        'gameuser_code',
        'pro_code',
        'pro_name',
        'turnpro',
        'amount',
        'balance',
        'bonus',
        'amount_balance',
        'total_amount_balance',
        'withdraw_limit_rate',
        'complete',
        'enable',
        'emp_code',
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
        'date_start' => 'date',
        'bill_code' => 'integer',
        'game_code' => 'integer',
        'game_name' => 'string',
        'member_code' => 'integer',
        'gameuser_code' => 'integer',
        'withdraw_limit_rate' => 'integer',
        'pro_code' => 'integer',
        'pro_name' => 'string',
        'turnpro' => 'decimal:2',
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'bonus' => 'decimal:2',
        'amount_balance' => 'decimal:2',
        'total_amount_balance' => 'decimal:2',
        'complete' => 'string',
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
    public static $rules = [
        'date_start' => 'nullable',
        'bill_code' => 'required|integer',
        'game_code' => 'required|integer',
        'game_name' => 'required|string|max:100',
        'member_code' => 'required|integer',
        'gameuser_code' => 'required|integer',
        'pro_code' => 'required|integer',
        'withdraw_limit_rate' => 'required|integer',
        'pro_name' => 'required|string|max:191',
        'turnpro' => 'required|numeric',
        'amount' => 'required|numeric',
        'balance' => 'required|numeric',
        'bonus' => 'required|numeric',
        'amount_balance' => 'required|numeric',
        'total_amount_balance' => 'required|numeric',
        'complete' => 'required|string',
        'enable' => 'required|string',
        'emp_code' => 'required|integer',
        'user_create' => 'required|string|max:100',
        'user_update' => 'required|string|max:100',
        'date_create' => 'nullable',
        'date_update' => 'nullable'
    ];
}
