<?php

namespace Gametech\API\Models;

use MongoDB\Laravel\Eloquent\Model;
use Gametech\API\Contracts\Askmebet as AskmebetContract;

class Askmebet extends Model implements AskmebetContract
{
    protected $connection = 'mongodb';
    protected $table = 'askmebet';

    protected $primaryKey = 'id';
}
