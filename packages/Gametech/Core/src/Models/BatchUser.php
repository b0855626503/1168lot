<?php

namespace Gametech\Core\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Core\Contracts\BatchUser as BatchUserContract;
use Gametech\Game\Models\GameProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class BatchUser extends Model implements BatchUserContract
{
    use  LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'batch_user';

    const CREATED_AT = 'date_create';

    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';


    protected $fillable = [
        'game_code',
        'game_id',
        'prefix',
        'batch_start',
        'batch_stop',
        'freecredit',
        'enable',
        'ip',
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
        'game_code' => 'integer',
        'game_id' => 'string',
        'prefix' => 'string',
        'batch_start' => 'integer',
        'batch_stop' => 'integer',
        'freecredit' => 'string',
        'enable' => 'string',
        'ip' => 'string',
        'user_create' => 'string',
        'user_update' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'game_code' => 'required|integer',
        'game_id' => 'required|string|max:50',
        'prefix' => 'required|string|max:10',
        'batch_start' => 'required|integer',
        'batch_stop' => 'required|integer',
        'freecredit' => 'required|string',
        'enable' => 'required|string',
        'ip' => 'required|string|max:100',
        'user_create' => 'required|string|max:100',
        'date_create' => 'required',
        'user_update' => 'required|string|max:100',
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

    public function game()
    {
        return $this->belongsTo(GameProxy::modelClass(),'game_code');
    }
}
