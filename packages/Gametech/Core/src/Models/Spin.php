<?php

namespace Gametech\Core\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use Gametech\Core\Contracts\Spin as SpinContract;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class Spin extends Model implements SpinContract
{
    use  LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'spins_new';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'name',
        'amount',
        'types',
        'winloss',
        'spincolor',
        'filepic',
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
        'name' => 'string',
        'types' => 'string',
        'amount' => 'decimal:2',
        'winloss' => 'integer',
        'spincolor' => 'string',
        'filepic' => 'string',
        'enable' => 'string',
        'user_create' => 'string',
        'user_update' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|string|max:100',
        'amount' => 'required|numeric',
        'winloss' => 'required|integer',
        'spincolor' => 'required|string|max:20',
        'filepic' => 'required|string|max:100',
        'enable' => 'required|string',
        'user_create' => 'required|string|max:100',
        'user_update' => 'required|string|max:100',
        'date_create' => 'required',
        'date_update' => 'required'
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
}
