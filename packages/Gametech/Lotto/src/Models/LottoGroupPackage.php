<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoGroupPackage as LottoGroupPackageContract;
use Illuminate\Database\Eloquent\Model;

class LottoGroupPackage extends Model implements LottoGroupPackageContract
{
    protected $table = 'lotto_group_packages';

    protected $fillable = [
        'group_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'group_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(LotteryGroup::class, 'group_id');
    }

    public function betSettings()
    {
        return $this->hasMany(LottoGroupPackageBetSetting::class, 'package_id');
    }
}

