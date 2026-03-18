<?php

namespace Gametech\Game\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Gametech\Game\Contracts\GameSeamless as GameSeamlessContract;

class GameSeamless extends Model implements GameSeamlessContract
{
    use LaravelSubQueryTrait;


    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public $table = 'games_seamless';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'id',
        'game_type',
        'method',
        'name',
        'filepic',
        'icon',
        'sort',
        'status_open',
        'enable',
        'limit',
        'mobile',
        'user_create',
        'date_create',
        'user_update',
        'date_update'
    ];
}
