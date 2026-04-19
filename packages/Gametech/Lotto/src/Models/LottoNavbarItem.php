<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoNavbarItem as LottoNavbarItemContract;
use Illuminate\Database\Eloquent\Model;

class LottoNavbarItem extends Model implements LottoNavbarItemContract
{
    protected $table = 'lotto_navbar_items';

    protected $fillable = [
        'navbar_id',
        'key',
        'item_type',
        'icon_type',
        'icon',
        'label_json',
        'action_type',
        'action_value',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'navbar_id' => 'integer',
        'label_json' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function navbar()
    {
        return $this->belongsTo(LottoNavbar::class, 'navbar_id');
    }
}
