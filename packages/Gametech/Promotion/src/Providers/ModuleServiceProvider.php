<?php

namespace Gametech\Promotion\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Gametech\Promotion\Models\Promotion::class,
        \Gametech\Promotion\Models\PromotionContent::class,
        \Gametech\Promotion\Models\PromotionAmount::class,
        \Gametech\Promotion\Models\PromotionTime::class,
    ];
}
