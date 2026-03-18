<?php

namespace Gametech\Payment\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Member\Models\MemberProxy;
use Gametech\Payment\Contracts\BonusSpin as BonusSpinContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BonusSpin extends Model implements BonusSpinContract
{
    use LaravelSubQueryTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'bonus_spin';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'member_code',
        'bonus_name',
        'reward_type',
        'amount',
        'credit',
        'credit_before',
        'credit_after',
        'diamond_balance',
        'ip',
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
        'bonus_name' => 'string',
        'credit' => 'decimal:2',
        'credit_before' => 'decimal:2',
        'credit_after' => 'decimal:2',
        'diamond_balance' => 'decimal:2',
        'ip' => 'string',
        'enable' => 'string',
        'user_create' => 'string',
        'user_update' => 'string',
        'date_create' => 'datetime:Y-m-d H:i',
        'date_update' => 'datetime:Y-m-d H:i',
        'time_create' => 'datetime:H:i'

    ];

    /**
     * Validation rules
     *
     * @var array
     */
    protected static $rules = [
        'member_code' => 'required|integer',
        'bonus_name' => 'required|string|max:100',
        'credit' => 'required|numeric',
        'credit_before' => 'required|numeric',
        'credit_after' => 'required|numeric',
        'diamond_balance' => 'required|numeric',
        'ip' => 'required|string|max:30',
        'enable' => 'required|string',
        'user_create' => 'required|string|max:100',
        'user_update' => 'required|string|max:100'
    ];

    protected static function booted()
    {
        static::addGlobalScope('code', function (Builder $builder) {
            $builder->where('code', '<>', 0);
        });
    }


    public function scopeActive($query)
    {
        return $query->where('enable', 'Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('enable', 'N');
    }

    public function member()
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'member_code');
    }
}
