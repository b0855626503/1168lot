<?php

namespace Gametech\API\Models;

use MongoDB\Laravel\Eloquent\Model;
use Gametech\API\Contracts\MicroGaming as MicroGamingContract;

class MicroGaming extends Model implements MicroGamingContract
{
    protected $connection = 'mongodb';
    protected $table = 'microgaming';

    protected $primaryKey = 'id';
}
