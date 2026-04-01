<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoGroupPackageBetSetting as LottoGroupPackageBetSettingContract;
use Illuminate\Database\Eloquent\Model;

class LottoGroupPackageBetSetting extends Model implements LottoGroupPackageBetSettingContract
{
    protected $table = 'lotto_group_package_bet_settings';

    protected $fillable = [
        'package_id',
        'bet_type',
        'payout',
        'discount_percent',
        'is_enabled',
    ];

    protected $casts = [
        'package_id' => 'integer',
        'payout' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'is_enabled' => 'boolean',
    ];

    public function package()
    {
        return $this->belongsTo(LottoGroupPackage::class, 'package_id');
    }
}

