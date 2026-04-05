<?php

namespace Gametech\API\Models;

use MongoDB\Laravel\Eloquent\Model;
use Gametech\API\Contracts\Netent as NetentContract;

class Netent extends Model implements NetentContract
{
    protected $connection = 'mongodb';
    protected $table = 'netent';

    protected $primaryKey = 'id';
}
