<?php

namespace Gametech\Game\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Game\Contracts\GameContent as GameContentContract;
use Illuminate\Database\Eloquent\Model;

class GameContent extends Model implements GameContentContract
{
    use LaravelSubQueryTrait;


    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public $table = 'games_content';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'id',
        'sort',
        'status_open',
        'enable',
        'filepic',
        'title',
        'content',
        'user_create',
        'date_create',
        'last_update',
        'user_update',
        'date_update'
    ];
}
