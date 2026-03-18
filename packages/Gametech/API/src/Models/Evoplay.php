<?php

namespace Gametech\API\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Gametech\API\Contracts\Evoplay as EvoplayContract;

class Evoplay extends Model implements EvoplayContract
{
    protected $connection = 'mongodb';
    protected $collection = 'evoplay';

    protected $primaryKey = 'id';
}
