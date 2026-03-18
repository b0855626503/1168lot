<?php

namespace Gametech\Payment\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Gametech\Payment\Contracts\WithdrawDetail as WithdrawDetailContract;

class WithdrawDetail extends Model implements WithdrawDetailContract
{
    use LaravelSubQueryTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'withdraws_detail';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'withdraw_code',
        'game_code',
        'bill_code',
        'pro_code',
        'amount',
        'bonus',
        'turnpro',
        'amount_balance',
        'withdraw_limit',
        'withdraw_limit_rate',
        'withdraw_limit_amount',
        'member_code',
        'user_name',
        'user_pass',
        'balance',
        'enable',
        'user_create',
        'user_update'
    ];
}
