<?php

namespace Gametech\API\Models;

use MongoDB\Laravel\Eloquent\Model;
use Gametech\API\Contracts\SpadeGaming as SpadeGamingContract;

class SpadeGaming extends Model implements SpadeGamingContract
{
    protected $connection = 'mongodb';
    protected $table = 'spadegaming';

    protected $primaryKey = 'id';
}
