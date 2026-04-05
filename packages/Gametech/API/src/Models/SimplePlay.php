<?php

namespace Gametech\API\Models;

use MongoDB\Laravel\Eloquent\Model;
use Gametech\API\Contracts\SimplePlay as SimplePlayContract;

class SimplePlay extends Model implements SimplePlayContract
{
    protected $connection = 'mongodb';
    protected $table = 'simpleplay';

    protected $primaryKey = 'id';
}
