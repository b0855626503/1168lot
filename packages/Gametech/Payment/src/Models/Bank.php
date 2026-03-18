<?php

namespace Gametech\Payment\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Member\Models\MemberProxy;
use Gametech\Payment\Contracts\Bank as BankContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spiritix\LadaCache\Database\LadaCacheTrait;


class Bank extends Model implements BankContract
{
    use LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'banks';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'name_th',
        'shortcode',
        'bg_color',
        'enable',
        'name_en',
        'status_auto',
        'show_regis',
        'website',
        'filepic',
        'user_create',
        'user_update',
        'date_create',
        'date_update'
    ];

    protected static function booted()
    {
        static::addGlobalScope('code', function (Builder $builder) {
            $builder->where('banks.code', '>', 0);
        });
    }

    public function withdraws()
    {
        return $this->hasMany(WithdrawProxy::modelClass(), 'bankm_code');
    }

    public function bank_payment()
    {
        return $this->hasMany(BankPaymentProxy::modelClass(), 'account_code');
    }

    public function banks_account()
    {
        return $this->hasMany(BankAccountProxy::modelClass(), 'banks');
    }

    public function bank_account()
    {
        return $this->hasOne(BankAccountProxy::modelClass(), 'banks');
    }

    public function members()
    {
        return $this->hasMany(MemberProxy::modelClass(), 'bank_code');
    }


}
