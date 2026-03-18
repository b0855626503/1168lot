<?php

namespace Gametech\Admin\Http\Controllers;


use Gametech\Admin\DataTables\GameDataTable;
use Gametech\Admin\DataTables\GameListDataTable;
use Gametech\Admin\DataTables\GameSeamlessDataTable;
use Gametech\Admin\DataTables\GameTypeDataTable;
use Gametech\Game\Models\GameSeamless;
use Gametech\Game\Repositories\GameListRepository;
use Gametech\Game\Repositories\GameSeamlessRepository;
use Gametech\Game\Repositories\GameTypeRepository;
use Gametech\Game\Repositories\GameUserFreeRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class GameListController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $gameUserRepository;

    protected $gameSeamlessRepository;

    protected $memberRepository;

    protected $gameUserFreeRepository;

    public function __construct
    (
        GameListRepository $repository,
        GameSeamlessRepository $gameSeamlessRepo,
        GameUserRepository $gameUserRepo,
        GameUserFreeRepository $gameUserFreeRepo,
        MemberRepository $memberRepo
    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->gameUserRepository = $gameUserRepo;

        $this->gameSeamlessRepossitory = $gameSeamlessRepo;

        $this->gameUserFreeRepository = $gameUserFreeRepo;

        $this->memberRepository = $memberRepo;
    }


    public function index(GameListDataTable $gameListDataTable)
    {
//        $games = $this->gameTypeRepository->findWhere(['enable' => 'Y'] ,['id as name','id'])->pluck('name', 'id');
         $games = $this->gameSeamlessRepossitory->findWhere(['enable' => 'Y' , 'status_open' => 'Y'] ,['name','id'])->pluck('name', 'id');


        return $gameListDataTable->render($this->_config['view'], ['games' => $games]);
    }

    public function edit(Request $request)
    {
        $user = $this->user()->name . ' ' . $this->user()->surname;
        $id = $request->input('id');
        $status = $request->input('status');
        $method = $request->input('method');


        if($status == 1){
            $status = true;
        }else{
            $status = false;
        }
        $data[$method] = $status;

        $chk = $this->repository->find($id);
        if (!$chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

//        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function loadData(Request $request)
    {
        $id = $request->input('id');

        $data = $this->repository->find($id);
        if (!$data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');

    }

    public function update($id, Request $request)
    {
        $user = $this->user()->name . ' ' . $this->user()->surname;

        $data = json_decode($request['data'], true);


        $chk = $this->repository->find($id);
        if (!$chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $data['user_update'] = $user;
        $this->repository->updatenew($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

}
