<?php

namespace Gametech\Core\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Member\Models\MemberProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Gametech\Core\Contracts\Refer as ReferContract;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class Refer extends Model implements ReferContract
{
    use LaravelSubQueryTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'refers';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'name',
        'sort',
        'enable',
        'user_create',
        'user_update'
    ];

    protected static function booted()
    {
        static::addGlobalScope('code', function (Builder $builder) {
            $builder->where('code', '<>', 0);
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

    public function members()
    {
        return $this->hasMany(MemberProxy::modelClass(),'refer_code');
    }

}
