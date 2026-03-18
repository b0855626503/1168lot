<?php

namespace Gametech\LogAdmin\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Gametech\LogAdmin\Models\Activity::class,
    ];
}
