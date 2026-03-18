<?php

namespace Gametech\Admin\Http\Controllers;


use Gametech\Admin\DataTables\GameDataDataTable;
use Gametech\Admin\DataTables\GameDataTable;
use Gametech\Admin\DataTables\PGSlotDataTable;
use Gametech\Admin\DataTables\SeamlessDataTable;
use Gametech\API\Facades\Ping;
use Gametech\Game\Repositories\GameRepository;
use Gametech\Game\Repositories\GameSeamlessRepository;
use Gametech\Game\Repositories\GameUserFreeRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class GameLogController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $gameSeamlessRepository;

    protected $memberRepository;

    protected $gameUserFreeRepository;

    public function __construct
    (
        GameSeamlessRepository $repository,
        GameUserRepository     $gameUserRepo,
        GameUserFreeRepository $gameUserFreeRepo,
        MemberRepository       $memberRepo
    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;


    }


    public function index(SeamlessDataTable $PGSlotDataTable)
    {
//        $games = $this->repository->orderBy('name','ASC')->findWhere(['status_open' => 'Y', 'enable' => 'Y'])->pluck('name', 'id');
        return $PGSlotDataTable->render($this->_config['view']);
    }

    public function local(GameDataDataTable $PGSlotDataTable)
    {

        $games = $this->repository->orderBy('name','ASC')->findWhere(['status_open' => 'Y', 'enable' => 'Y'])->pluck('name', 'id');
        return $PGSlotDataTable->render($this->_config['view'], ['games' => $games]);
    }


}
