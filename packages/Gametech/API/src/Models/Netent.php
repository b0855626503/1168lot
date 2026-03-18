<?php

namespace Gametech\API\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Gametech\API\Contracts\Netent as NetentContract;

class Netent extends Model implements NetentContract
{
    protected $connection = 'mongodb';
    protected $collection = 'netent';

    protected $primaryKey = 'id';
}
