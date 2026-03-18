<?php

namespace Gametech\Admin\Http\Controllers;

use App\Libraries\KbankOut;
use App\Libraries\ScbOut;
use Gametech\Admin\DataTables\WithdrawNewDataTable;
use Gametech\Auto\Jobs\PaymentOutScbNew;
use Gametech\Member\Repositories\MemberCreditLogRepository;
use Gametech\Payment\Repositories\WithdrawNewRepository;
use Illuminate\Http\Request;


class WithdrawNewController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberCreditLogRepository;

    public function __construct
    (
        WithdrawNewRepository     $repository,
        MemberCreditLogRepository $memberCreditLogRepo
    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->memberCreditLogRepository = $memberCreditLogRepo;
    }


    public function index(WithdrawNewDataTable $withdrawNewDataTable)
    {
        return $withdrawNewDataTable->render($this->_config['view']);
    }

    public function loadData(Request $request)
    {
        $data = [];
        $from_bank = $request->input('from_bank');
        $to_bank = $request->input('to_bank');
        $account = $request->input('to_account');
//        $id = $request->input('to_account');

        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutOne($from_bank);
        if (!$bank) {
            return $this->sendResponseFail([], 'ไม่พบข้อมูล');
        }

        $kbank = new ScbOut();
        $bank_trans = $kbank->Banks($to_bank);
        if ($bank_trans == '500') {
            return $this->sendResponseFail([], 'ไม่พบข้อมูล');
        }
        $param['ToBankCode'] = $bank_trans;
        $param['ToBank'] = $account;
        $chk = $kbank->BankCurlTrans($bank['acc_no'], 'getname', $param, 'POST');
        if ($chk['status'] === true) {
            $data['name'] = $chk['data']['to']['accountName'];
        }

        if (!$data) {
            return $this->sendResponseFail([], 'ไม่พบข้อมูล');
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

    public function update($id, Request $request)
    {
        $ip = $request->ip();
        $user = $this->user()->name . ' ' . $this->user()->surname;
        $datenow = now()->toDateTimeString();

        $data = json_decode($request['data'], true);


        $chk = $this->repository->find($id);
        if (!$chk) {
            return $this->sendSuccess('ไม่พบข้อมูลดังกล่าว');
        }

        $status_wd = ($chk->status_withdraw ?? null);
        if (!is_null($status_wd)) {


            if ($chk->emp_approve > 0 || $chk->status_withdraw != 'W') {
                return $this->sendSuccess('รายการนี้ นี้มีผู้ทำรายการแล้ว');
            }


            $data['member_code'] = $chk->member_code;
            $data['amount'] = $chk->amount;
            $data['emp_approve'] = $this->id();
            $data['ip_admin'] = $ip;
            $data['user_update'] = $user;
            $data['date_approve'] = $datenow;
            $this->repository->update($data, $id);

            $return = PaymentOutKbank::dispatchNow($id);
            switch ($return['success']) {
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

        } else {

            if ($chk->emp_approve > 0) {
                return $this->sendSuccess('รายการนี้ นี้มีผู้ทำรายการแล้ว');
            }

            $data['emp_approve'] = $this->id();
            $data['status'] = 1;
            $data['ip_admin'] = $ip;
            $data['user_update'] = $user;
            $data['date_approve'] = $datenow;
            $this->repository->update($data, $id);

            return $this->sendSuccess('ดำเนินการสำเร็จแล้ว');
        }


    }

    public function refill(Request $request)
    {
        $ip = $request->ip();
        $user = $this->user()->name . ' ' . $this->user()->surname;
        $datenow = now()->toDateTimeString();

        $config = core()->getConfigData();

        $request->validate([
            'to_bank' => 'required|integer',
            'to_account' => 'required|string',
            'to_name' => 'required|string',
            'amount' => 'required|numeric',
            'account_code' => 'required|integer',
            'remark' => 'nullable|string'
        ]);


        $data['to_bank'] = $request->input('to_bank');
        $data['to_account'] = $request->input('to_account');
        $data['to_name'] = $request->input('to_name');
        $data['amount'] = $request->input('amount');
        $data['account_code'] = $request->input('account_code');
        $data['remark'] = $request->input('remark');
        $data['emp_approve'] = $this->id();
        $data['emp_name'] = $this->user()->user_name;
        $data['ip_admin'] = $ip;
        $data['ip'] = $ip;
        $data['user_update'] = $user;
        $data['status_withdraw'] = 'A';
        $data['status'] = 0;
        $response = $this->repository->create($data);



        $return = PaymentOutScbNew::dispatchNow($response->code);
//        switch ($return['success']) {
//
//
//            case 'COMPLETE':
//            case 'NOTWAIT':
//            case 'MONEY':
//                break;
//
//            case 'NOMONEY':
//            case 'FAIL_AUTO':
//            default:
//                $datanew['status'] = 0;
//                $this->repository->update($datanew, $response->code);
//
//
//        }

        return $this->sendSuccess($return['msg']);


    }

    public function clear(Request $request)
    {
        $config = core()->getConfigData();
        $user = $this->user()->name . ' ' . $this->user()->surname;
        $id = $request->input('id');
        $remark = $request->input('remark');

        $chk = $this->repository->find($id);

        if (!$chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        if ($chk->emp_approve > 0) {
            return $this->sendSuccess('รายการนี้ นี้มีผู้ทำรายการแล้ว');
        }


        $datanew = [
            'refer_code' => $id,
            'refer_table' => 'withdraws',
            'remark' => 'คืนยอดจากการถอน',
            'kind' => 'ROLLBACK',
            'amount' => $chk->amount,
            'method' => 'D',
            'member_code' => $chk->member_code,
            'emp_code' => $this->id(),
            'emp_name' => $this->user()->name . ' ' . $this->user()->surname
        ];

        if ($config->multigame_open == 'Y') {
            $response = $this->memberCreditLogRepository->setWallet($datanew);
        } else {
            $response = $this->memberCreditLogRepository->setWalletSingle($datanew);
        }


        if ($response) {
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
        $user = $this->user()->name . ' ' . $this->user()->surname;
        $id = $request->input('id');

        $chk = $this->repository->find($id);

        if (!$chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
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

//        $responses = collect(app('Gametech\Payment\Repositories\BankRepository')->getBankOutAccount()->toArray());
//
//        $responses = $responses->map(function ($items) {
//            $item = (object)$items;
//            return [
//                'value' => $item->bank_account['code'],
//                'text' => $item->name_th . ' [' . $item->bank_account['acc_no'] . ']'
//            ];
//
//        })->prepend($banks);


        $result['banks'] = $responses;
        return $this->sendResponseNew($result, 'complete');
    }

    public function loadBanks()
    {
        $banks = [
            'value' => '',
            'text' => '== เลือกธนาคาร =='
        ];

        $responses = collect(app('Gametech\Payment\Repositories\BankRepository')->findWhere(['enable' => 'Y'])->toArray());

        $responses = $responses->map(function ($items) {
            $item = (object)$items;
            return [
                'value' => $item->code,
                'text' => $item->name_th
            ];

        })->prepend($banks);

        $bankss = [
            'value' => '',
            'text' => 'เลือกบัญชีที่ใช้โอน'
        ];

        $responsess = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutAllWithApi()->toArray();

        $responsess = collect($responsess)->map(function ($items) {
            $item = (object)$items;
//            dd($item);
            return [
                'value' => $item->code,
                'text' => $item->bank['name_th'] . ' [' . $item->acc_no . '] ' . $item->acc_name
            ];

        })->prepend($bankss);


        $result['banks'] = $responses;
        $result['bankss'] = $responsess;
        return $this->sendResponseNew($result, 'ดำเนินการเสร็จสิ้น');
    }

}
