<?php

namespace Gametech\API\Models;

use Gametech\API\Contracts\GameLog as GameLogContract;
use Jenssegers\Mongodb\Eloquent\Model;
use MongoDB\BSON\UTCDateTime;

class GameLog extends Model implements GameLogContract
{
    public $timestamps = true;

    protected $connection = 'mongodb';

    protected $collection = 'gamelog';

    protected $fillable = ['input', 'output', 'company', 'game_user', 'method', 'response', 'amount', 'con_1', 'con_2', 'con_3', 'con_4', 'status', 'before_balance', 'after_balance', 'date_create', 'expireAt'];
    //    public $timestamps = false;

    //    protected $primaryKey = 'id';

    //    protected $dates = ['expireAt'];
    //    protected $casts = [
    //        'before_balance' => 'decimal:2',
    //        'after_balance' => 'decimal:2',
    //    ];

    public static function mongoNow(): UTCDateTime
    {
        return new UTCDateTime((int) round(microtime(true) * 1000));
    }

    public function freshTimestamp()
    {
        return new UTCDateTime((int) round(microtime(true) * 1000));
    }

    //    protected $fillable = ['input','output','company','game_user','method','response','amount','jackpotAmount','betAmount','jackpot','total','betResults','betOn','betBy','betNo','gameTrnNo','game','jackpotResult','jackpotMultiple','status','chanel','invoiceNo','payout','remark','before_balance','after_balance','date_create','expireAt'];

}
