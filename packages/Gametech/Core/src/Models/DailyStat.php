<?php

namespace Gametech\Core\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Gametech\Core\Contracts\DailyStat as DailyStatContract;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class DailyStat extends Model implements DailyStatContract
{
    use  LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'daily_stat';

    protected $primaryKey = 'code';

    public $fillable = [
        'date',
        'member_all',
        'member_new',
        'member_new_list',
        'member_new_refill',
        'member_new_refill_list',
        'member_all_refill',
        'deposit_count',
        'deposit_sum',
        'withdraw_count',
        'withdraw_sum',
        'bonus_sum',
        'setwallet_d_sum',
        'setwallet_w_sum'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'code' => 'integer',
        'date' => 'date',
        'member_all' => 'integer',
        'member_new' => 'integer',
        'member_new_refill' => 'integer',
        'member_all_refill' => 'integer',
        'deposit_count' => 'integer',
        'deposit_sum' => 'decimal:2',
        'withdraw_count' => 'integer',
        'withdraw_sum' => 'decimal:2',
        'bonus_sum' => 'decimal:2',
        'setwallet_d_sum' => 'decimal:2',
        'setwallet_w_sum' => 'decimal:2'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'date' => 'nullable',
        'member_all' => 'required|integer',
        'member_new' => 'required|integer',
        'member_new_refill' => 'required|integer',
        'member_all_refill' => 'required|integer',
        'deposit_count' => 'required|integer',
        'deposit_sum' => 'required|numeric',
        'withdraw_count' => 'required|integer',
        'withdraw_sum' => 'required|numeric',
        'bonus_sum' => 'required|numeric',
        'setwallet_d_sum' => 'required|numeric',
        'setwallet_w_sum' => 'required|numeric',
    ];
}
