<?php

namespace Gametech\Admin\Http\Controllers;

use Gametech\Admin\DataTables\GameSingleDataTable;
use Gametech\Game\Repositories\GameSingleRepository;
use Gametech\Game\Repositories\GameUserFreeRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GameSingleController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $gameUserRepository;

    protected $memberRepository;

    protected $gameUserFreeRepository;

    public function __construct(
        GameSingleRepository $repository,
        GameUserRepository $gameUserRepo,
        GameUserFreeRepository $gameUserFreeRepo,
        MemberRepository $memberRepo
    ) {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->gameUserRepository = $gameUserRepo;

        $this->gameUserFreeRepository = $gameUserFreeRepo;

        $this->memberRepository = $memberRepo;
    }

    public function index(GameSingleDataTable $gameSingleDataTable)
    {
        return $gameSingleDataTable->render($this->_config['view']);
    }

    public function edit(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');
        $status = $request->input('status');
        $method = $request->input('method');

        $data[$method] = $status;

        $chk = $this->repository->find($id);
        if (! $chk) {
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
        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');

    }

    public function loadProvider(Request $request)
    {

        $host = config('app.user_domain_url');
        $cacheP = "private:lobby:providers:{$host}";
        $cached_provider = Cache::get($cacheP);
        $provider = $cached_provider['provider'] ?? null;
        //        dd($cacheP, $cached_provider);

//        $data = collect($provider)->pluck('lobbyName', 'lobbyId');
        $dropdown = collect($provider)
            ->pluck('lobbyName', 'lobbyId')
            ->map(fn ($name, $id) => ['value' => $id, 'text' => $name])
            ->sortBy('text', SORT_NATURAL | SORT_FLAG_CASE)
            ->prepend(['value' => '', 'text' => '== เลือกค่ายเกม =='])
            ->values()
            ->toArray();
        //        if (!$data) {
        //            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        //        }

        return $this->sendResponse($dropdown, 'ดำเนินการเสร็จสิ้น');

    }

    public function update($id, Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;

        $data = json_decode($request['data'], true);

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $data['user_update'] = $user;
        $this->repository->updatenew($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');

        $chk = $this->repository->find($id);

        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $this->repository->delete($id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function create(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;

        $data = json_decode($request['data'], true);

        $data['user_create'] = $user;
        $data['user_update'] = $user;

        $key = $data['id'];

        $host = config('app.user_domain_url');
        $cacheP = "private:lobby:providers:{$host}";
        $cached_provider = Cache::get($cacheP);
        $pname = $cached_provider['mapById'][$key] ?? null;

        $data['name'] = $pname['name'];

        $this->repository->create($data);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }
}
