<?php

namespace Gametech\API\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Gametech\API\Contracts\SpadeGaming as SpadeGamingContract;

class SpadeGaming extends Model implements SpadeGamingContract
{
    protected $connection = 'mongodb';
    protected $collection = 'spadegaming';

    protected $primaryKey = 'id';
}
