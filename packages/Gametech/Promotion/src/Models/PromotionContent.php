<?php

namespace Gametech\Promotion\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Promotion\Contracts\PromotionContent as PromotionContentContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class PromotionContent extends Model implements PromotionContentContract
{
    use LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'promotions_content';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'id',
        'name_th',
        'sort',
        'content',
        'filepic',
        'enable',
        'user_create',
        'user_update'
    ];

    protected $casts = [
        'code' => 'integer',
        'name_th' => 'string',
        'content' => 'string',
        'filepic' => 'string',
        'enable' => 'string',
        'sort' => 'integer',
        'user_create' => 'string',
        'user_update' => 'string',
        'date_create' => 'datetime:Y-m-d H:i:s',
        'date_update' => 'datetime:Y-m-d H:i:s',
    ];

    protected static $rules = [
        'name_th' => 'required|string|max:100',
        'sort' => 'required|integer',
        'content' => 'required|string',
        'filepic' => 'required|string|max:255',
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
        return $query->where('enable', 'Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('enable', 'N');
    }


}
