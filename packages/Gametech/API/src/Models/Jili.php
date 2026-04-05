<?php

namespace Gametech\API\Models;

use MongoDB\Laravel\Eloquent\Model;
use Gametech\API\Contracts\Jili as JiliContract;

class Jili extends Model implements JiliContract
{
    protected $connection = 'mongodb';
    protected $table = 'jili';

    protected $primaryKey = 'id';
}
