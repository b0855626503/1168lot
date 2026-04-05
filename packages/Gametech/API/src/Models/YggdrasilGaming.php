<?php

namespace Gametech\API\Models;

use MongoDB\Laravel\Eloquent\Model;
use Gametech\API\Contracts\YggdrasilGaming as YggdrasilGamingContract;

class YggdrasilGaming extends Model implements YggdrasilGamingContract
{
    protected $connection = 'mongodb';
    protected $table = 'yggdrasilgaming';

    protected $primaryKey = 'id';
}
