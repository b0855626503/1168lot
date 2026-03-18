<?php

namespace Gametech\Admin\Http\Controllers;


use Gametech\Admin\DataTables\WithdrawfreeDataTable;
use Gametech\Auto\Jobs\PaymentOutFreeKbank;
use Gametech\Member\Repositories\MemberFreeCreditRepository;
use Gametech\Payment\Repositories\WithdrawFreeRepository;
use Illuminate\Http\Request;


class WithdrawfreeController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberFreeCreditRepository;

    public function __construct
    (
        WithdrawFreeRepository  $repository,
        MemberFreeCreditRepository $memberFreeCreditRepo
    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->memberFreeCreditRepository = $memberFreeCreditRepo;
    }


    public function index(WithdrawfreeDataTable $withdrawfreeDataTable)
    {
        return $withdrawfreeDataTable->render($this->_config['view']);
    }

    public function loadData(Request $request)
    {
        $id = $request->input('id');


        $data = $this->repository->with(['member','bank'])->find($id);

        if (!$data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }



        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');

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

    public function update($id,Request $request)
    {
        $ip = $request->ip();
        $user = $this->user()->name.' '.$this->user()->surname;
        $datenow = now()->toDateTimeString();

        $data = json_decode($request['data'],true);


        $chk = $this->repository->find($id);
        if(!$chk){
            return $this->sendSuccess('ไม่พบข้อมูลดังกล่าว');
        }

        if($chk->emp_approve > 0 || $chk->status_withdraw != 'W'){
            return $this->sendSuccess('รายการนี้ นี้มีผู้ทำรายการแล้ว');
        }

        $data['member_code'] = $chk->member_code;
        $data['amount'] = $chk->amount;
        $data['emp_approve'] = $this->id();
        $data['ip_admin'] = $ip;
        $data['user_update'] = $user;
        $data['date_approve'] = $datenow;
        $this->repository->update($data, $id);


        $return = PaymentOutFreeKbank::dispatchNow($id);
        switch($return['success']){
            case 'NORMAL':
                $datanew['status'] = 1;
                $this->repository->update($datanew, $id);
                break;

            case 'NOMONEY':
            case 'FAIL_AUTO':
                $datanew['account_code'] = 0;
                $datanew['status_withdraw'] = 'W';
                $datanew['status'] = 0;
                $datanew['emp_approve'] = 0;
                $datanew['ip_admin'] = '';
                $this->repository->update($datanew, $id);
                break;

            case 'COMPLETE':
            case 'NOTWAIT':
            case 'MONEY':
                break;

        }


        return $this->sendSuccess($return['msg']);


    }

    public function clear(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');
        $remark = $request->input('remark');

        $chk = $this->repository->find($id);

        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }

        $datanew = [
            'remark' => 'คืนยอดจากการถอน',
            'kind' => 'ROLLBACK',
            'amount' => $chk->amount,
            'method' => 'D',
            'member_code' => $chk->member_code,
            'emp_code' => $this->id(),
            'emp_name' => $this->user()->name.' '.$this->user()->surname
        ];

        $response = $this->memberFreeCreditRepository->setCredit($datanew);

        if($response){
            $data['ip_admin'] = $request->ip();
            $data['remark_admin'] = $remark;
            $data['status'] = 2;
            $data['emp_approve'] = $this->id();
            $data['user_update'] = $user;
            $data['date_approve'] = now()->toDateTimeString();
            $this->repository->update($data, $id);
        }


        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function destroy(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');

        $chk = $this->repository->find($id);

        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }


        $data['enable'] = 'N';
        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function fixSubmit(Request $request)
    {
        $user = $this->user()->name . ' ' . $this->user()->surname;
        $id = $request->input('id');

        $chk = $this->repository->find($id);

        if (!$chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }


        $data['emp_approve'] = 0;
        $data['status_withdraw'] = 'W';
        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function loadBank()
    {
        $banks = [
            'value' => '0',
            'text' => 'ไม่ระบุบัญชี'
        ];

        $responses = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutAll()->toArray();

        $responses = collect($responses)->map(function ($items) {
            $item = (object)$items;
//            dd($item);
            return [
                'value' => $item->code,
                'text' => $item->bank['name_th'] . ' [' . $item->acc_no . ']' . $item->acc_name
            ];

        })->prepend($banks);



        $result['banks'] = $responses;
        return $this->sendResponseNew($result,'complete');
    }


}
