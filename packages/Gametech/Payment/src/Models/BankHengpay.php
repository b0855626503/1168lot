<?php

namespace Gametech\Payment\Models;

use DateTimeInterface;
use Gametech\Payment\Contracts\BankHengpay as BankHengpayContract;
use Illuminate\Database\Eloquent\Model;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class BankHengpay extends Model implements BankHengpayContract
{

    use LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'banks_hengpay';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'member_code',
        'referenceNo',
        'amount',
        'user_create',
        'user_update',
        'date_create',
        'date_update'

    ];

}
