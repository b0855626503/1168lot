<?php

namespace Gametech\LogUser\Providers;

use Gametech\LogUser\Models\ActivityUser;
use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        ActivityUser::class,
    ];
}
