<?php

namespace Gametech\Game\Models;

use Awobaz\Compoships\Compoships;
use DateTimeInterface;
use Gametech\Game\Contracts\GameUserFree as GameUserFreeContract;
use Gametech\Member\Models\MemberProxy;
use Gametech\Payment\Models\BillFreeProxy;
use Gametech\Promotion\Models\PromotionProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameUserFree extends Model implements GameUserFreeContract
{
    use Compoships;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'games_user_free';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
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

    protected $casts = [
        'code' => 'integer',
        'game_code' => 'integer',
        'member_code' => 'integer',
        'user_name' => 'string',
        'user_pass' => 'string',
        'balance' => 'decimal:2',
        'enable' => 'string',
        'user_create' => 'string',
        'user_update' => 'string',
        'date_create' => 'datetime:Y-m-d H:i',
        'date_update' => 'datetime:Y-m-d H:i',
    ];

    public static $rules = [
        'game_code' => 'required|integer',
        'member_code' => 'required|integer',
        'user_name' => 'required|string|max:100',
        'user_pass' => 'required|string|max:255',
        'balance' => 'required|numeric',
        'enable' => 'required|string',
        'user_create' => 'required|string|max:100',
        'user_update' => 'required|string|max:100',

    ];

    protected static function booted()
    {
        static::addGlobalScope('code', function (Builder $builder) {
            $builder->where('games_user_free.code', '<>', 0);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('enable','Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('enable','N');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(GameProxy::modelClass(), 'game_code');
    }


    public function member()
    {
        return $this->hasOne(MemberProxy::modelClass(), 'member_code');
    }

    public function membernew()
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'member_code');
    }

    public function members()
    {
        return $this->hasMany(MemberProxy::modelClass(),'member_code');
    }

    public function promotion()
    {
        return $this->belongsTo(PromotionProxy::modelClass(), 'pro_code');
    }

    public function bills()
    {
        return $this->hasMany(BillFreeProxy::modelClass(), 'member_code','member_code');
    }

    public function bill()
    {
        return $this->hasOne(BillFreeProxy::modelClass(), 'member_code','member_code');
    }

}
