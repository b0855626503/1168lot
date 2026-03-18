<?php

namespace Gametech\API\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Gametech\API\Contracts\SimplePlay as SimplePlayContract;

class SimplePlay extends Model implements SimplePlayContract
{
    protected $connection = 'mongodb';
    protected $collection = 'simpleplay';

    protected $primaryKey = 'id';
}
