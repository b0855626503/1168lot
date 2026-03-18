<?php

namespace Gametech\Admin\Http\Controllers;


use Gametech\Admin\DataTables\GameDataTable;
use Gametech\Admin\DataTables\GameSeamlessDataTable;
use Gametech\Admin\DataTables\GameTypeDataTable;
use Gametech\Game\Models\GameSeamless;
use Gametech\Game\Repositories\GameSeamlessRepository;
use Gametech\Game\Repositories\GameTypeRepository;
use Gametech\Game\Repositories\GameUserFreeRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class GameSeamlessController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $gameUserRepository;

    protected $gameTypeRepository;

    protected $memberRepository;

    protected $gameUserFreeRepository;

    public function __construct
    (
        GameSeamlessRepository $repository,
        GameTypeRepository $gameTypeRepo,
        GameUserRepository $gameUserRepo,
        GameUserFreeRepository $gameUserFreeRepo,
        MemberRepository $memberRepo
    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->gameUserRepository = $gameUserRepo;

        $this->gameTypeRepository = $gameTypeRepo;

        $this->gameUserFreeRepository = $gameUserFreeRepo;

        $this->memberRepository = $memberRepo;
    }


    public function index(GameSeamlessDataTable $gameDataTable)
    {
        $games = $this->gameTypeRepository->findWhere(['enable' => 'Y'] ,['id as name','id'])->pluck('name', 'id');


        return $gameDataTable->render($this->_config['view'], ['games' => $games]);
    }

    public function edit(Request $request)
    {
        $user = $this->user()->name . ' ' . $this->user()->surname;
        $id = $request->input('id');
        $status = $request->input('status');
        $method = $request->input('method');


        $data[$method] = $status;

        $chk = $this->repository->find($id);
        if (!$chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function update($id, Request $request)
    {
        $user = $this->user()->name . ' ' . $this->user()->surname;

        $data = json_decode($request['data'], true);


        $chk = $this->repository->find($id);
        if (!$chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        if($id == 1){
            if($data['method'] == 'seamless'){
                $data['id'] = 'PGSOFT2';
            }else{
                $data['id'] = 'PGSOFT';
            }
        }

        if($id == 67){
            if($data['method'] == 'seamless'){
                $data['id'] = 'PRAGMATIC_SLOT';
            }else{
                $data['id'] = 'PRAGMATIC';
            }
        }


        $data['user_update'] = $user;
        $this->repository->updatenew($data, $id);

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

    public function loadBetLimit(Request $request)
    {
        $id = $request->input('id');

        $banks = [
            'value' => '',
            'text' => '== ไม่ระบุ =='
        ];

        $responses = collect(app('Gametech\Game\Repositories\GameUserRepository')->betLimit($id));
//        dd($responses);
        $responses = $responses->map(function ($items){
            $item = (object)$items;
            return [
                'value' => $item->limit,
                'text' => 'Min : '.$item->Min.' - Max : '.$item->Max
            ];

        })->prepend($banks);



        $result['limit'] = $responses;
        return $this->sendResponseNew($result,'ดำเนินการเสร็จสิ้น');
    }

}
