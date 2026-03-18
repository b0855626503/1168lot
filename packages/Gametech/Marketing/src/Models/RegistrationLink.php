<?php

namespace Gametech\Marketing\Models;

use Gametech\Marketing\Contracts\RegistrationLink as RegistrationLinkContract;
use Illuminate\Database\Eloquent\Model;

class RegistrationLink extends Model implements RegistrationLinkContract
{
    public $timestamps = false;

    protected $fillable = [
        'code',
        'team_id',
        'campaign_id',
    ];

    protected $casts = [
        'team_id' => 'integer',
        'campaign_id' => 'integer',
    ];

    public function getFullUrlAttribute(): string
    {
        return route('customer.markering.register', ['code' => $this->code]);
    }

    public function team()
    {
        return $this->belongsTo(MarketingTeamProxy::modelClass(), 'team_id');
    }

    public function campaign()
    {
        return $this->belongsTo(MarketingCampaignProxy::modelClass(), 'campaign_id');
    }

    public function clicks()
    {
        return $this->hasMany(RegistrationLinkClickProxy::modelClass());
    }
}
