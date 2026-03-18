<?php

namespace Gametech\Payment\Models;

use Awobaz\Compoships\Compoships;
use DateTimeInterface;
use Gametech\Game\Models\GameProxy;
use Gametech\Game\Models\GameUserFreeProxy;
use Gametech\Member\Models\MemberProxy;
use Gametech\Payment\Contracts\BillFree as BillFreeContract;
use Gametech\Promotion\Models\PromotionProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BillFree extends Model implements BillFreeContract
{
    use Compoships;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'bills_free';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $primaryKey = 'code';

    protected $dateFormat = 'Y-m-d H:i:s';

//    protected $dates = ['date_create', 'date_update'];
//    public $timestamps = false;

    protected $fillable = [
        'ref_id',
        'member_code',
        'game_code',
        'pro_code',
        'transfer_type',
        'amount',
        'balance_before',
        'balance_after',
        'credit',
        'credit_bonus',
        'credit_before',
        'credit_after',
        'credit_balance',
        'ip',
        'auto',
        'remark',
        'enable',
        'emp_code',
        'user_create',
        'user_update'
    ];

    protected $casts = [
        'code' => 'integer',
        'ref_id' => 'string',
        'member_code' => 'integer',
        'game_code' => 'integer',
        'pro_code' => 'integer',
        'transfer_type' => 'string',
        'amount' => 'decimal:2',
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
        return $query->where('bills_free.enable', 'Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('bills_free.enable', 'N');
    }

    public function scopeGetpro($query)
    {
        return $query->where('bills_free.pro_code', '>', 0);
    }

    public function member()
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'member_code');
    }

    public function promotion()
    {
        return $this->belongsTo(PromotionProxy::modelClass(), 'pro_code');
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
        return $this->hasMany(WithdrawFreeProxy::modelClass(), 'member_code');
    }

    public function member_wallet()
    {
        return $this->morphMany(MemberProxy::modelClass(), 'wallet_transaction');
    }

    public function game_user()
    {
        return $this->belongsTo(GameUserFreeProxy::modelClass(), ['member_code', 'game_code'], ['member_code', 'game_code']);
    }

    public function gamesUser()
    {
        return $this->hasMany(GameUserFreeProxy::modelClass(), 'member_code', 'member_code')->where('game_code', $this->game_code);
    }
}
