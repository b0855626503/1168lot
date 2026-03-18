<?php

namespace Gametech\Game\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Gametech\Game\Contracts\GameSingle as GameSingleContract;

class GameSingle extends Model implements GameSingleContract
{
    use LaravelSubQueryTrait;


    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public $table = 'games_single';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'id',
        'name',
        'user_create',
        'date_create',
        'user_update',
        'date_update'
    ];
}
