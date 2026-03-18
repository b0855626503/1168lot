<?php

namespace Gametech\Marketing\Models;

use Gametech\Marketing\Contracts\MarketingCampaign as MarketingCampaignContract;
use Illuminate\Database\Eloquent\Model;

class MarketingCampaign extends Model implements MarketingCampaignContract
{
    protected $fillable = [
        'name',
        'description',
        'team_id',
        'admin_username',
        'is_ended',
        'ended_at',
        'enable',
    ];

    protected $casts = [
        'ended_at' => 'date',
        'is_ended' => 'boolean',
        'enable' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('enable', true);
    }

    public function team()
    {
        return $this->belongsTo(MarketingTeamProxy::modelClass(), 'team_id');
    }

    public function members()
    {
        return $this->hasMany(MarketingMemberProxy::modelClass(), 'campaign_id');
    }

    public function registrationLink()
    {
        return $this->hasOne(RegistrationLinkProxy::modelClass(), 'campaign_id');
    }
}
