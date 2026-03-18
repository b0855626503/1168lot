<?php

namespace Gametech\Game\Models;

use Awobaz\Compoships\Compoships;
use DateTimeInterface;
use Gametech\Core\Models\BillLastProxy;
use Gametech\Game\Contracts\GameUser as GameUserContract;
use Gametech\Member\Models\MemberProxy;
use Gametech\Payment\Models\BillProxy;
use Gametech\Promotion\Models\PromotionProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;


class GameUser extends Model implements GameUserContract
{
//    use LaravelSubQueryTrait;

    use Compoships;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'games_user';

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
        'bill_code' => 'integer',
        'pro_code' => 'integer',
        'withdraw_limit_rate' => 'integer',
        'user_name' => 'string',
        'user_pass' => 'string',
        'balance' => 'decimal:2',
        'amount' => 'decimal:2',
        'bonus' => 'decimal:2',
        'amount_balance' => 'decimal:2',
        'turnpro' => 'decimal:2',
        'withdraw_limit' => 'decimal:2',
        'withdraw_limit_amount' => 'decimal:2',
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
            $builder->where('games_user.code', '<>', 0);
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

    public function game()
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

    public function promotion()
    {
        return $this->belongsTo(PromotionProxy::modelClass(), 'pro_code');
    }

    public function billcode()
    {
        return $this->belongsTo(BillProxy::modelClass(), 'bill_code');
    }

    public function members()
    {
        return $this->hasMany(MemberProxy::modelClass(),'member_code');
    }

    public function bills()
    {
        return $this->hasMany(BillProxy::modelClass(), 'member_code','member_code');
    }

    public function bill()
    {
        return $this->hasOne(BillProxy::modelClass(), 'member_code','member_code');
    }

    public function lastbill()
    {
        return $this->hasOne(BillProxy::modelClass(), ['member_code', 'game_code'],['member_code', 'game_code'])->where('transfer_type',1)->where('enable','Y')->latest();
    }

    public function maxbill()
    {
        return $this->hasOne(BillLastProxy::modelClass(), ['member_code', 'game_code'],['member_code', 'game_code']);
    }

}
