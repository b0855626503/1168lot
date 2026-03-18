<?php

namespace Gametech\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Gametech\Core\Contracts\LogType as LogTypeContract;

class LogType extends Model implements LogTypeContract
{
    protected $fillable = [];
}