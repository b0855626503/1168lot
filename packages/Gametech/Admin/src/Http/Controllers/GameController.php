<?php

namespace Gametech\Admin\Http\Controllers;


use Gametech\Admin\DataTables\GameDataTable;
use Gametech\API\Facades\Ping;
use Gametech\Game\Repositories\GameRepository;
use Gametech\Game\Repositories\GameUserFreeRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class GameController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $gameUserRepository;

    protected $memberRepository;

    protected $gameUserFreeRepository;

    public function __construct
    (
        GameRepository $repository,
        GameUserRepository $gameUserRepo,
        GameUserFreeRepository $gameUserFreeRepo,
        MemberRepository $memberRepo
    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->gameUserRepository = $gameUserRepo;

        $this->gameUserFreeRepository = $gameUserFreeRepo;

        $this->memberRepository = $memberRepo;
    }


    public function index(GameDataTable $gameDataTable)
    {
        return $gameDataTable->render($this->_config['view']);
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

    public function loadDebug(Request $request)
    {
        $user = $this->user()->name . ' ' . $this->user()->surname;
        $id = $request->input('id');
        $method = $request->input('method');


        $chk = $this->repository->findOrFail($id);


        if (empty($chk)) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $response = [];


        $member = $this->memberRepository->where('enable', 'Y')->first();
        $member->username ='boattester';
        $member->product_id = 'PGSOFT';
//        $member = $this->memberRepository->where('enable', 'Y')->first();


        switch ($method) {
            case 'add':
                $response = $this->gameUserRepository->addGameUser($chk->code, $member->code, collect($member)->toArray(), true);
                break;

            case 'pass':
                $game_user = $this->gameUserRepository->findOneWhere(['user_name' => $chk->user_demo, 'game_code' => $id]);
                if (!$game_user) {
                    return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
                }
                $user_pass = "Bb" . rand(100000, 999999);
                $response = $this->gameUserRepository->changeGamePass($chk->code, $game_user->code, [
                    'user_pass' => $user_pass,
                    'user_name' => $game_user->user_name,
                    'name' => $member['name'],
                    'firstname' => $member['firstname'],
                    'lastname' => $member['lastname'],
                    'gender' => $member['gender'],
                    'birth_day' => $member->birth_day->format('Y-m-d'),
                    'date_regis' => $member->date_regis->format('Y-m-d'),
                ], true);

                break;

            case 'balance':

                $response = $this->gameUserRepository->checkBalance($chk->id, $chk->user_demo, true);
                break;

            case 'deposit':
                $response = $this->gameUserRepository->UserDeposit($chk->code, $chk->user_demo, 100, true, true);
                break;

            case 'withdraw':
                $response = $this->gameUserRepository->UserWithdraw($chk->code, $chk->user_demo, 100, true, true);
                break;

            case 'login':
                $game_user = $this->gameUserRepository->findOneWhere(['user_name' => $chk->user_demo, 'game_code' => $id]);
                if (!$game_user) {
                    return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
                }
                $response = $this->gameUserRepository->autoLogin($chk->id, $chk->user_demo, $game_user->user_pass, true);
                break;
        }


        return $this->sendResponseNew($response, 'Load Complete');

    }

    public function loadDebugFree(Request $request)
    {
        $user = $this->user()->name . ' ' . $this->user()->surname;
        $id = $request->input('id');
        $method = $request->input('method');


        $chk = $this->repository->findOrFail($id);


        if (empty($chk)) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $response = [];


        $member = $this->memberRepository->where('enable', 'Y')->first();
        $member->username ='boattester';
        $member->product_id = 'PGSOFT';
//        $member = $this->memberRepository->where('enable', 'Y')->first();


        switch ($method) {
            case 'add':
                $response = $this->gameUserFreeRepository->addGameUser($chk->code, $member->code, collect($member)->toArray(), true);
                break;

            case 'pass':
                $game_user = $this->gameUserFreeRepository->findOneWhere(['user_name' => $chk->user_demofree, 'game_code' => $id]);
                if (!$game_user) {
                    return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
                }
                $user_pass = "Bb" . rand(100000, 999999);
                $response = $this->gameUserFreeRepository->changeGamePass($chk->code, $game_user->code, [
                    'user_pass' => $user_pass,
                    'user_name' => $game_user->user_name,
                    'name' => $member['name'],
                    'firstname' => $member['firstname'],
                    'lastname' => $member['lastname'],
                    'gender' => $member['gender'],
                    'birth_day' => $member->birth_day->format('Y-m-d'),
                    'date_regis' => $member->date_regis->format('Y-m-d'),
                ], true);

                break;

            case 'balance':

                $response = $this->gameUserFreeRepository->checkBalance($chk->id, $chk->user_demofree, true);
                break;

            case 'deposit':
                $response = $this->gameUserFreeRepository->UserDeposit($chk->code, $chk->user_demofree, 100, true, true);
                break;

            case 'withdraw':
                $response = $this->gameUserFreeRepository->UserWithdraw($chk->code, $chk->user_demofree, 100, true, true);
                break;

            case 'login':
                $game_user = $this->gameUserFreeRepository->findOneWhere(['user_name' => $chk->user_demofree, 'game_code' => $id]);
                if (!$game_user) {
                    return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
                }
                $response = $this->gameUserFreeRepository->autoLogin($chk->id, $chk->user_demofree, $game_user->user_pass, true);
                break;
        }


        return $this->sendResponseNew($response, 'Load Complete');

    }

    public function loadDebugFree_(Request $request)
    {
        $user = $this->user()->name . ' ' . $this->user()->surname;
        $id = $request->input('id');
        $method = $request->input('method');


        $chk = $this->repository->findOrFail($id);


        if (empty($chk)) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $response = [];


        $member = $this->memberRepository->where('enable', 'Y')->first();


        switch ($method) {
            case 'add':
                $response = $this->gameUserFreeRepository->addGameUser($chk->code, 0, collect($member)->toArray(), true);
                break;

            case 'pass':
                $game_user = $this->gameUserFreeRepository->findOneWhere(['user_name' => $chk->user_demofree, 'game_code' => $id]);
                if (!$game_user) {
                    return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
                }

                $user_pass = "Bb" . rand(100000, 999999);
                $response = $this->gameUserFreeRepository->changeGamePass($chk->code, $game_user->code, [
                    'user_pass' => $user_pass,
                    'user_name' => $game_user->user_name,
                    'name' => $member->name,
                    'firstname' => $member['firstname'],
                    'lastname' => $member['lastname'],
                    'gender' => $member['gender'],
                    'birth_day' => $member['birth_day'],
                    'date_regis' => $member['date_regis'],
                ], true);

                break;

            case 'balance':
                $response = $this->gameUserFreeRepository->checkBalance($chk->id, $chk->user_demofree, true);
                break;

            case 'deposit':
                $response = $this->gameUserFreeRepository->UserDeposit($chk->code, $chk->user_demofree, 1, true, true);
                break;

            case 'withdraw':
                $response = $this->gameUserFreeRepository->UserWithdraw($chk->code, $chk->user_demofree, 1, true, true);
                break;
        }


        return $this->sendResponseNew($response, 'Load Complete');

    }

    public function gameCheck()
    {
        $offline = [];
        $games = $this->repository->findWhere(['enable' => 'Y', 'status_open' => 'Y']);


        foreach ($games as $i => $item) {
            $url = config('game.' . $item->id . '.apiurl');

            if (is_null($url)) continue;
//            if ($item->id != 'joker' || $item->id != 'slotxo' || $item->id != 'dreamtech' || $item->id != 'jokerNew' || $item->id != 'slotx') {
//
//
//                $url = Str::of($url)->replace(':80', '')->__toString();
//
//                $health = Ping::check($url);
//
//                if ($health <> 200) {
////                    $offline[] = 'ขณะนี้เกม ' . $item->name . ' มีปัญหาในการเชื่อมต่อ';
//                }
//            }

            if ($item->batch_game == 'Y') {
                $normal = DB::table("users_" . $item->id)->where('enable', 'Y')->where('use_account', 'N')->where('freecredit', 'N')->count();
                if ($normal < 10) {
                    $offline[] = 'ขณะนี้เกม ' . $item->name . ' ID สำหรับสมัครจะหมดแล้ว เพิ่มได้ที่เมนู Batch User';
                }
            }
        }

        $response['data'] = $offline;
        return $this->sendResponseNew($response, 'Load Complete');


    }


}
