<?php

namespace Gametech\Game\Models;

use Illuminate\Database\Eloquent\Model;

use Gametech\Game\Contracts\User as UserContract;

class User extends Model implements UserContract
{
    protected $fillable = [];
}
