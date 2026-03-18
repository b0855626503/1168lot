<?php

namespace Gametech\Promotion\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use Gametech\Promotion\Contracts\PromotionAmount as PromotionAmountContract;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class PromotionAmount extends Model implements PromotionAmountContract
{
    use  LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'promotions_amount';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $primaryKey = 'code';

    protected $fillable = [
        'pro_code',
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
        return $query->where('enable','Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('enable','N');
    }
}
