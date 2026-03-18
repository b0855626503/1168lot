<?php

namespace Gametech\API\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Gametech\API\Contracts\MicroGaming as MicroGamingContract;

class MicroGaming extends Model implements MicroGamingContract
{
    protected $connection = 'mongodb';
    protected $collection = 'microgaming';

    protected $primaryKey = 'id';
}
