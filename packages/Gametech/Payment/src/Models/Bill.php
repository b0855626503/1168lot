<?php

namespace Gametech\Payment\Models;

use Awobaz\Compoships\Compoships;
use DateTimeInterface;
use Gametech\Admin\Models\AdminProxy;
use Gametech\Game\Models\GameProxy;
use Gametech\Game\Models\GameUserProxy;
use Gametech\Member\Models\MemberProxy;
use Gametech\Payment\Contracts\Bill as BillContract;
use Gametech\Promotion\Models\PromotionProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class Bill extends Model implements BillContract
{

    use Compoships;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'bills';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';


    protected $primaryKey = 'code';


    protected $fillable = [
        'ref_id',
        'member_code',
        'game_code',
        'pro_code',
        'pro_name',
        'transfer_type',
        'amount',
        'amount_request',
        'amount_limit',
        'balance_before',
        'balance_after',
        'credit',
        'credit_bonus',
        'credit_before',
        'credit_after',
        'credit_balance',
        'ip',
        'auto',
        'method',
        'remark',
        'refer_code',
        'refer_table',
        'enable',
        'complete',
        'emp_code',
        'user_create',
        'user_update',
        'date_create',
        'date_update'
    ];

    protected $casts = [
        'code' => 'integer',
        'ref_id' => 'string',
        'member_code' => 'integer',
        'game_code' => 'integer',
        'pro_code' => 'integer',
        'transfer_type' => 'string',
        'amount' => 'decimal:2',
        'amount_request' => 'decimal:2',
        'amount_limit' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'credit' => 'decimal:2',
        'credit_bonus' => 'decimal:2',
        'credit_before' => 'decimal:2',
        'credit_after' => 'decimal:2',
        'credit_balance' => 'decimal:2',
        'ip' => 'string',
        'auto' => 'string',
        'remark' => 'string',
        'enable' => 'string',
        'emp_code' => 'integer',
        'user_create' => 'string',
        'user_update' => 'string',
        'date_create' => 'datetime:Y-m-d H:i:s',
        'date_update' => 'datetime:Y-m-d H:i:s',
    ];




    public function scopeActive($query)
    {
        return $query->where('enable', 'Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('bills.enable', 'N');
    }

    public function scopeGetpro($query)
    {
        return $query->where('bills.pro_code', '>', 0);
    }

    public function scopeTopup($query)
    {
        return $query->where('bills.method', 'TOPUP');
    }

    public function member()
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'member_code');
    }

    public function promotion()
    {
        return $this->belongsTo(PromotionProxy::modelClass(), 'pro_code');
    }

    public function promotions()
    {
        return $this->belongsTo(PromotionProxy::modelClass(), 'pro_code');
    }

    public function billturn()
    {
        return $this->hasOne(PromotionProxy::modelClass(), 'code', 'pro_code')->where('turnpro', '>', 0);
    }

    public function emp()
    {
        return $this->hasOne(AdminProxy::modelClass(), 'code', 'emp_code');
    }

    public function pross()
    {
        return $this->hasMany(PromotionProxy::modelClass(), 'pro_code');
    }

    public function game()
    {
        return $this->belongsTo(GameProxy::modelClass(), 'game_code');
    }

    public function games()
    {
        return $this->hasMany(GameProxy::modelClass(), 'code', 'game_code');
    }

    public function withdraws()
    {
        return $this->hasMany(WithdrawProxy::modelClass(), 'member_code');
    }


    public function member_wallet()
    {
        return $this->morphMany(MemberProxy::modelClass(), 'wallet_transaction');
    }


    public function game_user()
    {
        return $this->belongsTo(GameUserProxy::modelClass(), ['member_code', 'game_code'], ['member_code', 'game_code']);
    }

    public function gamesUser()
    {
        return $this->hasMany(GameUserProxy::modelClass(), 'member_code', 'member_code')->where('game_code', $this->game_code);
    }

    public function members()
    {
        return $this->hasMany(MemberProxy::modelClass(), 'code', 'member_code');
    }

    public function bank()
    {
        return $this->hasOne(BankProxy::modelClass(), 'code', 'bank_code');
    }

}
