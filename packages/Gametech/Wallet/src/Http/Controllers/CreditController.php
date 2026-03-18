<?php

namespace Gametech\Wallet\Http\Controllers;


use Gametech\Game\Repositories\GameRepository;
use Gametech\Game\Repositories\GameSeamlessRepository;
use Gametech\Game\Repositories\GameTypeRepository;
use Gametech\Game\Repositories\GameUserFreeRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;


class CreditController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected $gameUserFreeRepository;

    protected $memberRepository;

    protected $gameRepository;

    protected $gameTypeRepository;

    protected $gameSeamlessRepository;

    protected $withdrawFreeRepository;

    /**
     * Create a new Repository instance.
     * @param GameRepository $gameRepo
     */
    public function __construct
    (
        GameRepository $gameRepo,
        GameUserFreeRepository $gameUserFreeRepo,
        GameTypeRepository           $gameTypeRepo,
        GameSeamlessRepository       $gameSeamlessRepo
    )
    {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->gameRepository = $gameRepo;

        $this->gameUserFreeRepository = $gameUserFreeRepo;

        $this->gameTypeRepository = $gameTypeRepo;

        $this->gameSeamlessRepository = $gameSeamlessRepo;

    }

    public function index()
    {
        $config = collect(core()->getConfigData());
        $profile = $this->user();
        $games = $this->loadGame();

        if ($config['seamless'] == 'Y') {
            $gameuser = $this->gameUserFreeRepository->findOneByField('member_code', $profile->code);
            if (!$gameuser) {
                $game = $this->gameRepository->findOneWhere(['enable' => 'Y', 'status_open' => 'Y']);
                $this->gameUserFreeRepository->addGameUser($game->code, $profile->code, ['username' => $profile->user_name, 'product_id' => 'PGSOFT', 'user_create' => $profile->user_name]);
            }

            $games = [];
            $gameTypes = $this->gameTypeRepository->findWhere(['enable' => 'Y', 'status_open' => 'Y']);
            foreach ($gameTypes as $type) {
                $games[$type->id] = $this->gameSeamlessRepository->orderBy('sort')->findWhere(['game_type' => $type->id, 'status_open' => 'Y', 'enable' => 'Y']);
            }

            return view($this->_config['view'], compact('games'));
        }else{
            if($config['multigame_open'] == 'N'){
                $gameuser = $this->gameUserFreeRepository->findOneByField('member_code', $profile->code);
                if(!$gameuser){
                    $game = $this->gameRepository->findOneWhere(['enable' => 'Y', 'status_open' => 'Y']);
                    $this->gameUserFreeRepository->addGameUser($game->code, $profile->code, $profile);
                }
            }

        }

        $games = $games->mapToGroups(function ($items, $key){
            $item = (object)$items;

            return [strtolower($item->game_type) => [
                'code' => $item->code,
                'name' => $item->name,
                'image' =>  Storage::url('game_img/'.$item->filepic),
                'balance' => (!is_null($item->game_user_free) ? $item->game_user_free['balance'] : 0),
                'user_code' => (!is_null($item->game_user_free) ? $item->game_user_free['code'] : '')
            ]];

        });


        return view($this->_config['view'],compact('games','profile'));
    }



    public function loadGame(): Collection
    {
        return collect($this->gameRepository->getGameUserFreeById($this->id(), false)->toArray());

    }


}
