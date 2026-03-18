<?php

namespace Gametech\API\Models;

use Gametech\API\Contracts\GameLog as GameLogContract;
use Jenssegers\Mongodb\Eloquent\Model;
use MongoDB\BSON\UTCDateTime;

class GameData extends Model implements GameLogContract
{
    public $timestamps = true;

    protected $connection = 'mongodb';

    protected $collection = 'gamedatas';

    protected $fillable = ['productId', 'date_create', 'betId', 'roundId', 'username', 'gameName', 'stake', 'payout', 'betStatus', 'before_balance', 'after_balance', 'expireAt', 'isSingleState', 'skipBalanceUpdate'];

    public static function mongoNow(): UTCDateTime
    {
        return new UTCDateTime((int) round(microtime(true) * 1000));
    }

    public function freshTimestamp()
    {
        return new UTCDateTime((int) round(microtime(true) * 1000));
    }
}
