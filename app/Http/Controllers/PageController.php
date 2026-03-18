<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_config = request('_config');
    }

    public function index(Request $request)
    {
        $config = core()->getConfigData();
        if($config->seamless == 'Y'){
            $games = [];
            $gameTypes = app('Gametech\Game\Repositories\GameTypeRepository')->findWhere(['enable' => 'Y']);
            foreach ($gameTypes as $type) {
                $games[$type->id] = app('Gametech\Game\Repositories\GameSeamlessRepository')->orderBy('sort')->findWhere(['game_type' => $type->id, 'status_open' => 'Y', 'enable' => 'Y']);
            }
            $game = app('Gametech\Game\Repositories\GameSeamlessRepository')->findOneWhere(['status_open' => 'Y', 'enable' => 'Y']);

        }else{
            if ($config->multigame_open == 'Y') {
                $games = app('Gametech\Game\Repositories\GameRepository')->findWhere(['status_open' => 'Y' , 'enable' => 'Y']);
                $games = collect($games)->mapToGroups(function ($items, $key) {
                    $item = (object)$items;
                    return [strtolower($item->game_type) => $item];
                });

            } else {
                $games = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['status_open' => 'Y' , 'enable' => 'Y']);

            }
        }

//        dd($games);

        $slides = app('Gametech\Core\Repositories\SlideRepository')->findWhere(['enable' => 'Y'])->toArray();

        $pro = app('Gametech\Promotion\Repositories\PromotionRepository')->orderBy('sort')->findWhere(['enable' => 'Y', 'use_wallet' => 'Y', ['code', '<>', 0]])->toArray();
        $pros = app('Gametech\Promotion\Repositories\PromotionContentRepository')->orderBy('sort')->findWhere(['enable' => 'Y', ['code', '<>', 0]])->toArray();
        $promotions = array_merge($pro,$pros);


        return view($this->_config['view'], compact('slides', 'games', 'promotions', 'game'));
    }
}
