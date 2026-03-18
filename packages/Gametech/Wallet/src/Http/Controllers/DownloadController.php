<?php

namespace Gametech\Wallet\Http\Controllers;


use Gametech\Game\Repositories\GameRepository;
use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Support\Facades\Storage;


class DownloadController extends AppBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    private $gameRepository;

    private $memberRepository;

    /**
     * Create a new Repository instance.
     *
     * @param GameRepository $gameRepo
     * @param MemberRepository $memberRepo
     */
    public function __construct
    (
        GameRepository $gameRepo,
        MemberRepository $memberRepo
    )
    {
        $this->middleware('customer');

        $this->_config = request('_config');

        $this->gameRepository = $gameRepo;

        $this->memberRepository = $memberRepo;
    }

    public function index()
    {
        $games = $this->loadGame();

        $config = core()->getConfigData();

        if($config['multigame_open'] == 'Y'){
            return view($this->_config['view'], compact('games'));
        }else{
            return view($this->_config['view_single'], compact('games'));
        }


    }

    public function loadGame(): array
    {
        $responses = [];

        $results = collect($this->gameRepository->getGameUserById($this->id(),false)->toArray());


        foreach($results as $i => $result){
            $responses[strtolower($result['game_type'])][$i] = $result;
            $responses[strtolower($result['game_type'])][$i]['image'] = Storage::url('game_img/'.$result['filepic']);
        }

        return $responses;

    }


}
