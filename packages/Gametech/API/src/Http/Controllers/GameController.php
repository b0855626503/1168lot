<?php

namespace Gametech\API\Http\Controllers;

use Gametech\Game\Repositories\GameSeamlessRepository;
use Gametech\Game\Repositories\GameTypeRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GameController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $gameTypeRepository;

    protected $gameSeamlessRepository;

    public function __construct(
        GameUserRepository $repository,
        GameTypeRepository $gameTypeRepository,
        GameSeamlessRepository $gameSeamlessRepository
    ) {
        $this->_config = request('_config');

        $this->middleware('api')->except('gameLogin');

        $this->repository = $repository;

        $this->gameTypeRepository = $gameTypeRepository;

        $this->gameSeamlessRepository = $gameSeamlessRepository;
    }

    public function getGames($type, $provider)
    {

        $game = core()->getGame();
        $game_name = $this->gameSeamlessRepository->findOneByField('id', $provider);
        $games = $this->repository->getGameList($provider, $game_name->method);

        $gamelist = $games['games'];

        $method = $game_name->method;
        $transformedList = array_map(function ($item) use ($method) {
            return [
                "id" => $item["code"],
                "provider" => $item['product'], // ปรับจาก $item["product"] ถ้าอยาก map อัตโนมัติ
                "providerLogo" => [
                    "logoURL" => "",
                    "logoMobileURL" => "",
                    "logoTransparentURL" => ""
                ],
                "gameName" => $item["name"],
                "gameCategory" => $method,
                "gameType" => [$item["type"]],
                "image" => [
                    "vertical" => $item["img"],
                    "horizontal" => $item["img"],
                    "banner" => ""
                ],
                "status" => $item["enable"] ? "ACTIVE" : "INACTIVE",
                "rtp" => round(mt_rand(96000, 98000) / 1000, 8), // mock RTP
                "online" => rand(50, 100) // mock online player count
            ];
        }, $gamelist);

//        dd($transformedList);

        return response()->json($transformedList);
    }

    public function getProviders($type)
    {

        $games = [];
        $gameTypes = $this->gameTypeRepository->findWhere(['enable' => 'Y', 'status_open' => 'Y']);
        foreach ($gameTypes as $types) {
            $gameseamless = $this->gameSeamlessRepository->orderBy('sort')->findWhere(['game_type' => $types->id, 'status_open' => 'Y', 'enable' => 'Y']);
            $gameseamless = collect($gameseamless)->map(function ($items) {
                $items['filepic'] = Storage::url('game_img/'.strtolower($items->filepic).'?v='.date('ymd'));

                return (object) $items;

            });
            $games[strtolower($types->id)] = $gameseamless->toArray();
        }

        $game = $games[$type];
        $transformed = array_map(function ($item) {
            return [
                'provider' => $item['id'], // ใช้ id เป็นรหัส provider
                'providerTier' => 'vvip', // สมมุติค่า หรือดึงจาก logic อื่น
                'providerName' => $item['name'],
                'providerType' => $item['game_type'], // เช่น "COCK"
                'logoURL' => url($item['filepic']),
                'logoTransparentURL' => url($item['filepic']),
                'status' => $item['enable'] === 'Y' ? 'ACTIVE' : 'INACTIVE',
                'detailStatus' => $item['status_open'] === 'Y'
            ];
        }, $game);

        return response()->json($transformed);

    }

    public function gameLogin($type, $provider, $id)
    {

        $url = '';
        if (Auth::guard('customer')->check()) {
            $user = Auth::guard('customer')->user();
        } else {
            dd('cannot get auth');

            return view('wallet::customer.game.cannot');
        }

        dd($user->code);
        $game = core()->getGame();
        $result = $this->repository->autoLoginSingle($member_code, $type, $id, $provider);
        if ($result['success'] === true) {
            $url = $result['url'];
        }
        if ($url == '') {
            return view('wallet::customer.game.cannot');
        }

        return view($this->_config['view'], compact('url'));
    }
}
