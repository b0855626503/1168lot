<?php

namespace Gametech\API\Models;

use Gametech\API\Contracts\GameList as GameListContract;
use Jenssegers\Mongodb\Eloquent\Model;
use MongoDB\BSON\UTCDateTime;

class GameList extends Model implements GameListContract
{
    public $timestamps = true;

    protected $connection = 'mongodb';

    protected $collection = 'gamelist';

    protected $attributes = [
        'enable' => true,
        'click' => 0,
    ];

    protected $fillable = [
        'product', 'name', 'code', 'category',
        'type', 'img', 'rank', 'enable',
        'game', 'click', 'method',
    ];

    protected $casts = [
        'enable' => 'boolean',
        'click' => 'integer',
    ];

    public static function mongoNow(): UTCDateTime
    {
        return new UTCDateTime((int) round(microtime(true) * 1000));
    }

    public function freshTimestamp()
    {
        return new UTCDateTime((int) round(microtime(true) * 1000));
    }
}
