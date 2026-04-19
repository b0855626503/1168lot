<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoNavbar as LottoNavbarContract;
use Illuminate\Database\Eloquent\Model;

class LottoNavbar extends Model implements LottoNavbarContract
{
    protected $table = 'lotto_navbars';

    protected $fillable = [
        'code',
        'name',
        'is_active',
        'is_published',
        'published_version',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'published_version' => 'integer',
        'published_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(LottoNavbarItem::class, 'navbar_id');
    }
}
