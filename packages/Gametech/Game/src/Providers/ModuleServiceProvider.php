<?php

namespace Gametech\Game\Providers;

use Gametech\Game\Models\Game;
use Gametech\Game\Models\GameContent;
use Gametech\Game\Models\GameSeamless;
use Gametech\Game\Models\GameSingle;
use Gametech\Game\Models\GameType;
use Gametech\Game\Models\GameUser;
use Gametech\Game\Models\GameUserEvent;
use Gametech\Game\Models\GameUserFree;
use Gametech\Game\Models\User;
use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        Game::class,
        GameType::class,
        GameSeamless::class,
        GameUser::class,
        GameUserEvent::class,
        GameUserFree::class,
        User::class,
        GameContent::class,
        GameSingle::class
    ];
}
