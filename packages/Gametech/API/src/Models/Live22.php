<?php

namespace Gametech\API\Models;

use MongoDB\Laravel\Eloquent\Model;
use Gametech\API\Contracts\Live22 as Live22Contract;

class Live22 extends Model implements Live22Contract
{
    protected $connection = 'mongodb';
    protected $table = 'live22';

    protected $primaryKey = 'id';
}
