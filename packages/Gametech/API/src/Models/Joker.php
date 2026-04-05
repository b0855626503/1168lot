<?php

namespace Gametech\API\Models;

use MongoDB\Laravel\Eloquent\Model;
use Gametech\API\Contracts\Joker as JokerContract;

class Joker extends Model implements JokerContract
{
    protected $connection = 'mongodb';
    protected $table = 'joker';

    protected $primaryKey = 'id';
}
