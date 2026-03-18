<?php

namespace Gametech\Marketing\Providers;

use Gametech\Marketing\Models\MarketingCampaign;
use Gametech\Marketing\Models\MarketingMember;
use Gametech\Marketing\Models\MarketingTeam;
use Gametech\Marketing\Models\RegistrationLink;
use Gametech\Marketing\Models\RegistrationLinkClick;
use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        MarketingTeam::class,
        MarketingCampaign::class,
        RegistrationLink::class,
        RegistrationLinkClick::class,
        MarketingMember::class,
    ];
}
