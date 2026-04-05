<?php

namespace Gametech\API\Models;

use MongoDB\Laravel\Eloquent\Model;
use Gametech\API\Contracts\Evoplay as EvoplayContract;

class Evoplay extends Model implements EvoplayContract
{
    protected $connection = 'mongodb';
    protected $table = 'evoplay';

    protected $primaryKey = 'id';
}
