<?php

namespace Gametech\Reward\Providers;

use Gametech\Reward\Models\RewardList;
use Gametech\Reward\Models\RewardRedemption;
use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [
        RewardList::class,
        RewardRedemption::class,
    ];
}