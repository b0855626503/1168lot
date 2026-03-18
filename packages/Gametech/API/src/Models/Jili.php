<?php

namespace Gametech\API\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Gametech\API\Contracts\Jili as JiliContract;

class Jili extends Model implements JiliContract
{
    protected $connection = 'mongodb';
    protected $collection = 'jili';

    protected $primaryKey = 'id';
}
