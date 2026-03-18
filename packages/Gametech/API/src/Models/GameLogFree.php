<?php

namespace Gametech\API\Models;

// use Illuminate\Database\Eloquent\Model;
use Gametech\API\Contracts\GameLogFree as GameLogFreeContract;
use Jenssegers\Mongodb\Eloquent\Model;
use MongoDB\BSON\UTCDateTime;

class GameLogFree extends Model implements GameLogFreeContract
{
    protected $connection = 'mongodb';

    protected $collection = 'gamelogfree';

    protected $fillable = ['input', 'output', 'company', 'game_user', 'method', 'response', 'amount', 'con_1', 'con_2', 'con_3', 'con_4', 'status', 'before_balance', 'after_balance', 'date_create', 'expireAt'];

    public static function mongoNow(): UTCDateTime
    {
        return new UTCDateTime((int) round(microtime(true) * 1000));
    }

    public function freshTimestamp()
    {
        return new UTCDateTime((int) round(microtime(true) * 1000));
    }
}
