<?php

namespace Gametech\Promotion\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Promotion\Contracts\PromotionTime as PromotionTimeContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spiritix\LadaCache\Database\LadaCacheTrait;


class PromotionTime extends Model implements PromotionTimeContract
{
    use  LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'promotions_time';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $primaryKey = 'code';

    protected $fillable = [
        'pro_code',
        'time_start',
        'time_stop',
        'deposit_amount',
        'deposit_stop',
        'amount',
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
        return $query->where('enable', 'Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('enable', 'N');
    }
}
