<?php

namespace Gametech\API\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Gametech\API\Contracts\Gamatron as GamatronContract;

class Gamatron extends Model implements GamatronContract
{
    protected $connection = 'mongodb';
    protected $collection = 'gamatron';

    protected $primaryKey = 'id';
}
