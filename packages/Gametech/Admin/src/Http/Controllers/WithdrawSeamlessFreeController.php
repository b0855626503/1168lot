<?php

namespace Gametech\Admin\Http\Controllers;


use Gametech\Admin\DataTables\WithdrawSeamlessFreeDataTable;
use Gametech\Auto\Jobs\PaymentOutSeamlessFreeKbank;
use Gametech\Member\Repositories\MemberCreditFreeLogRepository;
use Gametech\Payment\Repositories\WithdrawSeamlessFreeRepository;
use Illuminate\Http\Request;


class WithdrawSeamlessFreeController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberCreditLogRepository;

    public function __construct
    (
        WithdrawSeamlessFreeRepository $repository,
        MemberCreditFreeLogRepository  $memberCreditLogRepo
    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->memberCreditLogRepository = $memberCreditLogRepo;
    }


    public function index(WithdrawSeamlessFreeDataTable $withdrawDataTable)
    {
        return $withdrawDataTable->render($this->_config['view']);
    }

    public function loadData(Request $request)
    {
        $id = $request->input('id');


        $data = $this->repository->with(['member', 'bank'])->find($id);

        if (!$data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }


        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');

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

//        $return['success'] = 'NORMAL';
//        $return['msg'] = 'อนุมัติรายการเรียบร้อยแล้ว (รายการทั่วไป)';

        $return = PaymentOutSeamlessFreeKbank::dispatchNow($id);


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

        if ($return['complete'] === true) {


            $member = app('Gametech\Member\Repositories\MemberRepository')->find($chk->member_code);

//        if($member->status_pro == 0){
//            $member->status_pro = 1;
//            $member->save();
//        }

            $game_user = app('Gametech\Game\Repositories\GameUserFreeRepository')->findOneByField('member_code', $chk->member_code);


            $this->memberCreditLogRepository->create([
                'ip' => $ip,
                'credit_type' => 'D',
                'balance_before' => $member->balance_free,
                'balance_after' => $member->balance_free,
                'credit' => 0,
                'total' => $chk->amount,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => $member->balance_free,
                'credit_after' => $member->balance_free,
                'pro_code' => 0,
                'bank_code' => $chk->bankm_code,
                'auto' => 'N',
                'enable' => 'Y',
                'user_create' => "System Auto",
                'user_update' => "System Auto",
                'refer_code' => $id,
                'refer_table' => 'withdraws',
                'remark' => 'เครดิตที่หักออกจากระบบ ' . $chk->balance . ' / จะได้รับยอดเงินผ่านเลขที่บัญชี : ' . $member->acc_no,
                'kind' => 'CONFIRM_WD',
                'amount' => $chk->amount,
                'amount_balance' => $game_user->amount_balance,
                'withdraw_limit' => $game_user->withdraw_limit,
                'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                'method' => 'D',
                'member_code' => $chk->member_code,
                'user_name' => $member->user_name,
                'emp_code' => $this->id(),
                'emp_name' => $this->user()->name . ' ' . $this->user()->surname
            ]);
        }

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

        $datanew = [
            'refer_code' => $id,
            'refer_table' => 'withdraws',
            'remark' => 'คืนยอดจากการถอน',
            'kind' => 'ROLLBACK',
            'amount' => $chk->balance,
            'amount_balance' => $chk->amount_balance,
            'withdraw_limit' => $chk->amount_limit,
            'withdraw_limit_amount' => $chk->amount_limit_rate,
            'method' => 'D',
            'member_code' => $chk->member_code,
            'emp_code' => $this->id(),
            'emp_name' => $this->user()->name . ' ' . $this->user()->surname
        ];

        $response = $this->memberCreditLogRepository->setWalletSeamlessWithdraw($datanew);


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
                'text' => $item->bank['name_th'] . ' [' . $item->acc_no . ']'
            ];

        })->prepend($banks);


        $result['banks'] = $responses;
        return $this->sendResponseNew($result, 'complete');
    }

}
