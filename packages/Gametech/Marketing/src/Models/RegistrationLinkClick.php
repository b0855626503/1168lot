<?php

namespace Gametech\Marketing\Models;

use Gametech\Marketing\Contracts\RegistrationLinkClick as RegistrationLinkClickContract;
use Illuminate\Database\Eloquent\Model;

class RegistrationLinkClick extends Model implements RegistrationLinkClickContract
{
    public $timestamps = false;

    protected $fillable = [
        'registration_link_id',
        'ip',
        'user_agent',
        'referrer',
        'created_at',
    ];
}
