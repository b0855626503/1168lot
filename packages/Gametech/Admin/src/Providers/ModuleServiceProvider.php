<?php

namespace Gametech\Admin\Providers;

use Gametech\Admin\Models\Admin;
use Gametech\Admin\Models\Role;
use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        Admin::class,
        Role::class,
    ];
}
