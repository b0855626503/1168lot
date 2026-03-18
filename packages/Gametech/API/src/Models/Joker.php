<?php

namespace Gametech\API\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Gametech\API\Contracts\Joker as JokerContract;

class Joker extends Model implements JokerContract
{
    protected $connection = 'mongodb';
    protected $collection = 'joker';

    protected $primaryKey = 'id';
}
