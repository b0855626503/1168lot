<?php

namespace Gametech\Game\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;

use DateTimeInterface;
use Gametech\Member\Models\MemberProxy;
use Gametech\Payment\Models\BillFreeProxy;
use Gametech\Payment\Models\BillProxy;
use Gametech\Payment\Models\PaymentWaitingProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use Gametech\Game\Contracts\Game as GameContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spiritix\LadaCache\Database\LadaCacheTrait;


class Game extends Model implements GameContract
{
    use LaravelSubQueryTrait;


    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public $table = 'games';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'id',
        'game_type',
        'user_demo',
        'user_demofree',
        'name',
        'filepic',
        'link_ios',
        'link_android',
        'link_web',
        'batch_game',
        'auto_open',
        'sort',
        'status_open',
        'enable',
        'user_create',
        'date_create',
        'user_update',
        'date_update',
        'autologin',
        'token',
        'token_expired',
        'newuser'
    ];

    protected static function booted()
    {
        static::addGlobalScope('code', function (Builder $builder) {
            $builder->where('games.code', '>', 0);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('games.enable','Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('games.enable','N');
    }

    public function scopeOpen($query)
    {
        return $query->where('games.status_open','Y');
    }

    public function scopeClose($query)
    {
        return $query->where('games.status_open','N');
    }

    public function scopeImage($query)
    {
        return $query->where('games.filepic','<>','');
    }

    public function paymentWaiting(): HasMany
    {
        return $this->hasMany(PaymentWaitingProxy::modelClass(), 'game_code');
    }

    public function gamesUser(): HasMany
    {
        return $this->hasMany(GameUserProxy::modelClass(),'game_code');
    }

    public function gameUser(): HasOne
    {
        return $this->hasOne(GameUserProxy::modelClass(),'game_code');
    }

    public function gameUserFree(): HasOne
    {
        return $this->hasOne(GameUserFreeProxy::modelClass(),'game_code');
    }

    public function bills()
    {
        return $this->hasMany(BillProxy::modelClass(),'game_code');
    }

    public function bills_free()
    {
        return $this->hasMany(BillFreeProxy::modelClass(),'game_code');
    }


    public function member(): BelongsTo
    {
        return $this->belongsTo(MemberProxy::modelClass(),'code','code','code')->where('code',auth()->guard('customer')->id());
    }


//    public function memberUser()
//    {
//        return $this->hasOneThrough(MemberProxy::modelClass(),GameUserProxy::modelClass(),'game_code','code','code','member_code')->withoutGlobalScopes();
////        return $this->hasOneThrough(GameUserProxy::modelClass(),MemberProxy::modelClass(),'code','code','game_code','code')->withoutGlobalScopes();
//    }



}
