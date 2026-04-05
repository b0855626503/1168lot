<?php

namespace Gametech\API\Models;

use MongoDB\Laravel\Eloquent\Model;
use Gametech\API\Contracts\Gamatron as GamatronContract;

class Gamatron extends Model implements GamatronContract
{
    protected $connection = 'mongodb';
    protected $table = 'gamatron';

    protected $primaryKey = 'id';
}
