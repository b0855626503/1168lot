<?php

namespace Gametech\API\Models;

use MongoDB\Laravel\Eloquent\Model;
use Gametech\API\Contracts\Slotxo as SlotxoContract;

class Slotxo extends Model implements SlotxoContract
{
    protected $connection = 'mongodb';
    protected $table = 'slotxo';

    protected $primaryKey = 'id';
}
