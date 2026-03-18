<?php

namespace Gametech\API\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Gametech\API\Contracts\YggdrasilGaming as YggdrasilGamingContract;

class YggdrasilGaming extends Model implements YggdrasilGamingContract
{
    protected $connection = 'mongodb';
    protected $collection = 'yggdrasilgaming';

    protected $primaryKey = 'id';
}
