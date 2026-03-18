<?php

namespace Gametech\API\Providers;

use Gametech\API\Models\Askmebet;
use Gametech\API\Models\Evoplay;
use Gametech\API\Models\Gamatron;
use Gametech\API\Models\GameList;
use Gametech\API\Models\GameLog;
use Gametech\API\Models\GameLogFree;
use Gametech\API\Models\Jili;
use Gametech\API\Models\Joker;
use Gametech\API\Models\Live22;
use Gametech\API\Models\Mannaplay;
use Gametech\API\Models\MicroGaming;
use Gametech\API\Models\Netent;
use Gametech\API\Models\PGSoft;
use Gametech\API\Models\SimplePlay;
use Gametech\API\Models\Slotxo;
use Gametech\API\Models\SpadeGaming;
use Gametech\API\Models\YggdrasilGaming;
use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        Askmebet::class,
        Evoplay::class,
        Gamatron::class,
        Jili::class,
        Joker::class,
        Mannaplay::class,
        MicroGaming::class,
        Netent::class,
        PGSoft::class,
        SimplePlay::class,
        Slotxo::class,
        SpadeGaming::class,
        YggdrasilGaming::class,
        Live22::class,
        GameLog::class,
        GameLogFree::class,
        GameList::class
    ];
}
