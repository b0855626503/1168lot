<?php

namespace Gametech\Core\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Member\Models\MemberRewardLogProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Gametech\Core\Contracts\Reward as RewardContract;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class Reward extends Model implements RewardContract
{
    use  LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'rewards';

    const CREATED_AT = 'date_create';

    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'name',
        'short_details',
        'details',
        'qty',
        'points',
        'filepic',
        'active',
        'enable',
        'user_create',
        'user_update'
    ];

    protected $casts = [
        'code' => 'integer',
        'name' => 'string',
        'short_details' => 'string',
        'details' => 'string',
        'qty' => 'integer',
        'points' => 'decimal:2',
        'filepic' => 'string',
        'active' => 'string',
        'enable' => 'string',
        'user_create' => 'string',
        'user_update' => 'string'
    ];

    protected static function booted()
    {
        static::addGlobalScope('code', function (Builder $builder) {
            $builder->where('code', '<>', 0);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('active','Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('active','N');
    }

    public function scopeEnable($query)
    {
        return $query->where('enable','Y');
    }

    public function scopeDisable($query)
    {
        return $query->where('enable','N');
    }

    public function exchange()
    {
        return $this->hasMany(MemberRewardLogProxy::modelClass(),'reward_code');
    }
}
