<?php

namespace Gametech\API\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Gametech\API\Contracts\Askmebet as AskmebetContract;

class Askmebet extends Model implements AskmebetContract
{
    protected $connection = 'mongodb';
    protected $collection = 'askmebet';

    protected $primaryKey = 'id';
}
