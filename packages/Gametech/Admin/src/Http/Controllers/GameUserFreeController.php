<?php

namespace Gametech\Admin\Http\Controllers;



use Gametech\Admin\DataTables\GameUserFreeDataTable;
use Gametech\Game\Repositories\GameUserFreeRepository;
use Illuminate\Http\Request;



class GameUserFreeController extends AppBaseController
{
    protected $_config;

    protected $repository;


    public function __construct
    (
        GameUserFreeRepository $repository
    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

    }


    public function index(GameUserFreeDataTable $gameUserDataTable)
    {
//        $games = $this->gameTypeRepository->findWhere(['enable' => 'Y'] ,['id as name','id'])->pluck('name', 'id');


        return $gameUserDataTable->render($this->_config['view']);
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
        $this->repository->update($data, $id);

        app('Gametech\Member\Repositories\MemberCreditFreeLogRepository')->create([
            'ip' => $request->ip(),
            'credit_type' => 'D',
            'balance_before' => 0,
            'balance_after' => 0,
            'credit' => 0,
            'total' => 0,
            'credit_bonus' => 0,
            'credit_total' => 0,
            'credit_before' => 0,
            'credit_after' => 0,
            'pro_code' => 0,
            'bank_code' => 0,
            'auto' => 'N',
            'enable' => 'Y',
            'user_create' => "System Auto",
            'user_update' => "System Auto",
            'refer_code' => 0,
            'refer_table' => 'games_user_free',
            'remark' => 'แก้ไขยอดเทริน หรือ ยอดอั้น โดยทีมงาน เทรินโปร : '.$data['turnpro'].' / อัตราอั้นถอน : '.$data['withdraw_limit_rate'].' (เท่า)',
            'kind' => 'OTHER',
            'amount' => 0,
            'amount_balance' => $data['amount_balance'],
            'withdraw_limit' => 0,
            'withdraw_limit_amount' => $data['withdraw_limit_amount'],
            'method' => 'D',
            'member_code' => $chk->member_code,
            'emp_code' => $this->id(),
            'emp_name' => $this->user()->name . ' ' . $this->user()->surname
        ]);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');

        $chk = $this->repository->find($id);

        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }

        $data['turnpro'] = 0;
        $data['amount_balance'] = 0;
        $data['withdraw_limit_rate'] = 0;
        $data['withdraw_limit_amount'] = 0;
        $this->repository->update($data, $id);

        app('Gametech\Member\Repositories\MemberCreditFreeLogRepository')->create([
            'ip' => $request->ip(),
            'credit_type' => 'D',
            'balance_before' => 0,
            'balance_after' => 0,
            'credit' => 0,
            'total' => 0,
            'credit_bonus' => 0,
            'credit_total' => 0,
            'credit_before' => 0,
            'credit_after' => 0,
            'pro_code' => 0,
            'bank_code' => 0,
            'auto' => 'N',
            'enable' => 'Y',
            'user_create' => "System Auto",
            'user_update' => "System Auto",
            'refer_code' => 0,
            'refer_table' => 'games_user_free',
            'remark' => 'รีเซตยอดเทรินออกทั้งหมด โดยทีมงาน',
            'kind' => 'OTHER',
            'amount' => 0,
            'amount_balance' => 0,
            'withdraw_limit' => 0,
            'withdraw_limit_amount' => 0,
            'method' => 'D',
            'member_code' => $chk->member_code,
            'emp_code' => $this->id(),
            'emp_name' => $this->user()->name . ' ' . $this->user()->surname
        ]);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

}
