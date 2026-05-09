<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoFrontendThemeSetting as LottoFrontendThemeSettingContract;
use Illuminate\Database\Eloquent\Model;

class LottoFrontendThemeSetting extends Model implements LottoFrontendThemeSettingContract
{
    protected $table = 'lotto_frontend_theme_settings';

    protected $fillable = [
        'preset_key',
        'tokens',
        'custom_tokens',
        'is_customized',
        'version',
        'updated_by',
    ];

    protected $casts = [
        'tokens' => 'array',
        'custom_tokens' => 'array',
        'is_customized' => 'boolean',
        'version' => 'integer',
    ];
}
