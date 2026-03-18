<?php

namespace Gametech\Marketing\Models;

use Gametech\Marketing\Contracts\MarketingTeam as MarketingTeamContract;
use Illuminate\Database\Eloquent\Model;

class MarketingTeam extends Model implements MarketingTeamContract
{
    protected $fillable = [
        'name',
        'username',
        'password_hash',
        'commission_rate',
        'bank_code',
        'bank_account_name',
        'bank_account_no',
        'enable',
    ];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'enable' => 'boolean',
        'commission_rate' => 'decimal:2',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('enable', true);
    }

    public function campaigns()
    {
        return $this->hasMany(MarketingCampaignProxy::modelClass(), 'team_id');
    }

    public function members()
    {
        return $this->hasMany(MarketingMemberProxy::modelClass(), 'team_id');
    }

    public function bank()
    {
        return $this->belongsTo(MarketingBankProxy::modelClass(), 'bank_code', 'code');
    }

    public function registrationLink()
    {
        return $this->hasOne(RegistrationLinkProxy::modelClass(), 'team_id');
    }

    public function setPasswordHashAttribute($value)
    {
        $this->attributes['password_hash'] = bcrypt($value);
    }
}
