<?php

namespace Gametech\Admin\Http\Controllers;

use Gametech\Admin\DataTables\WithdrawDataTable;
use Gametech\Auto\Jobs\PaymentOutAPay;
use Gametech\Auto\Jobs\PaymentOutAutoTransfer;
use Gametech\Auto\Jobs\PaymentOutDeepPay;
use Gametech\Auto\Jobs\PaymentOutKingPay;
use Gametech\Auto\Jobs\PaymentOutMaxPay;
use Gametech\Auto\Jobs\PaymentOutOnPay;
use Gametech\Auto\Jobs\PaymentOutPayoneX;
use Gametech\Auto\Jobs\PaymentOutSmkPay;
use Gametech\Auto\Jobs\PaymentOutWellPay;
use Gametech\Auto\Jobs\PaymentOutWildPay;
use Gametech\Member\Repositories\MemberCreditLogRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\WithdrawRepository;
use Illuminate\Http\Request;

class WithdrawController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberCreditLogRepository;

    protected $memberRepository;

    public function __construct(
        WithdrawRepository $repository,
        MemberCreditLogRepository $memberCreditLogRepo,
        MemberRepository $memberRepository
    ) {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->memberCreditLogRepository = $memberCreditLogRepo;

        $this->memberRepository = $memberRepository;
    }

    public function index(WithdrawDataTable $withdrawDataTable)
    {
        return $withdrawDataTable->render($this->_config['view']);
    }

    public function loadData(Request $request)
    {
        $id = $request->input('id');

        $data = $this->repository->with(['member', 'bank'])->find($id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');

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

    public function update($id, Request $request)
    {
        $ip = $request->ip();
        $user = $this->user()->name.' '.$this->user()->surname;
        $datenow = now()->toDateTimeString();

        $data = json_decode($request['data'], true);

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendSuccess('ไม่พบข้อมูลดังกล่าว');
        }

        if ($chk->emp_approve > 0 || $chk->status_withdraw != 'W') {
            return $this->sendSuccess('รายการนี้ นี้มีผู้ทำรายการแล้ว F5');
        }

        if ($chk->status === 1) {
            return $this->sendSuccess('รายการนี้ เสรฺ็จสิ้นแล้ว โปรด F5');
        }

        $amount = $data['amount'];
        if($amount <= 0){
            return $this->sendSuccess('ยอดที่ลูกค้าจะได้รับจริง เป็น 0 โปรด F5 แล้วทำรายการใหม่');
        }

        $data['member_code'] = $chk->member_code;
//        $data['amount'] = $chk->amount;
        $data['emp_approve'] = $this->id();
        $data['ip_admin'] = $ip;
        $data['user_update'] = $user;
        $data['date_approve'] = $datenow;
        $this->repository->update($data, $id);

        //        $return['success'] = 'NORMAL';
        //        $return['msg'] = 'อนุมัติรายการเรียบร้อยแล้ว (รายการทั่วไป)';

        //        $return = PaymentOutSeamlessKbank::dispatchNow($id);

        if ($data['account_code'] != 0) {

            $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutOne($data['account_code']);
            if (isset($bank)) {
                $bank_code = $bank->bank->code;
                if ($bank_code == 300) {
                    $return = PaymentOutWildPay::dispatchNow($id);
                } elseif ($bank_code == 304) {
                    $return = PaymentOutKingPay::dispatchNow($id);
                } elseif ($bank_code == 305) {
                    $return = PaymentOutWellPay::dispatchNow($id);
                } elseif ($bank_code == 307) {
                    $return = PaymentOutPayoneX::dispatchNow($id);

                } elseif ($bank_code == 308) {
                    $return = PaymentOutAPay::dispatchNow($id);
                } elseif ($bank_code == 310) {
                    $return = PaymentOutOnPay::dispatchNow($id);
                } elseif ($bank_code == 311) {
                    $return = PaymentOutMaxPay::dispatchNow($id);
                } elseif ($bank_code == 313) {
                    $return = PaymentOutSmkPay::dispatchNow($id);
                } elseif ($bank_code == 316) {
                    $return = PaymentOutDeepPay::dispatchNow($id);

                } else {
                    $return = PaymentOutAutoTransfer::dispatchNow($id);
                }
            } else {
                $return['success'] = 'NORMAL';
                $return['complete'] = true;
                $return['msg'] = 'อนุมัติรายการเรียบร้อยแล้ว (รายการทั่วไป)';
            }
        } else {
            $return['success'] = 'NORMAL';
            $return['complete'] = true;
            $return['msg'] = 'อนุมัติรายการเรียบร้อยแล้ว (รายการทั่วไป)';
        }

        switch ($return['success']) {
            case 'NORMAL':
                $datanew['status'] = 1;
                $this->repository->update($datanew, $id);
                break;

            case 'NOMONEY':
            case 'FAIL_AUTO':
                $datanew['txid'] = '';
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
            default:
                $datanew['txid'] = '';
                $datanew['account_code'] = 0;
                $datanew['status_withdraw'] = 'W';
                $datanew['status'] = 0;
                $datanew['emp_approve'] = 0;
                $datanew['ip_admin'] = '';
                $this->repository->update($datanew, $id);



        }

        if ($return['complete'] === true) {

            $member = app('Gametech\Member\Repositories\MemberRepository')->find($chk->member_code);

            $member->sum_withdraw += $chk->amount;
            $member->saveQuietly();

            $game_user = app('Gametech\Game\Repositories\GameUserRepository')->findOneByField('member_code', $chk->member_code);

            $this->memberCreditLogRepository->create([
                'ip' => $ip,
                'credit_type' => 'D',
                'balance_before' => $member->balance,
                'balance_after' => $member->balance,
                'credit' => 0,
                'total' => $chk->amount,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => $member->balance,
                'credit_after' => $member->balance,
                'pro_code' => 0,
                'bank_code' => $chk->bankm_code,
                'auto' => 'N',
                'enable' => 'Y',
                'user_create' => 'System Auto',
                'user_update' => 'System Auto',
                'refer_code' => $id,
                'refer_table' => 'withdraws',
                'remark' => 'เครดิตที่หักออกจากระบบ '.$chk->balance.' / จะได้รับยอดเงินผ่านเลขที่บัญชี : '.$member->acc_no,
                'kind' => 'CONFIRM_WD',
                'amount' => $chk->amount,
                'amount_balance' => $game_user->amount_balance,
                'withdraw_limit' => $game_user->withdraw_limit,
                'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                'method' => 'D',
                'member_code' => $chk->member_code,
                'user_name' => $member->user_name,
                'emp_code' => $this->id(),
                'emp_name' => $this->user()->name.' '.$this->user()->surname,
            ]);

            $bill = app('Gametech\Payment\Repositories\BillRepository')->findOneWhere(['refer_code' => $chk->code, 'refer_table' => 'withdraws', 'method' => 'WITHDRAW']);
            $bill->complete = 'Y';
            $bill->save();

        }

        return $this->sendSuccess($return['msg']);

    }

    public function clear(Request $request)
    {
        $config = $this->getCoreConfig();
        $user = $this->user()->name.' '.$this->user()->surname;

        $id = (int) $request->input('id');
        $remark = (string) $request->input('remark', '');

        // กัน double-click ระดับ endpoint (ไว ๆ)
        $lockKey = "withdraw:clear:{$id}";
        $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 15);

        if (! $lock->get()) {
            return $this->sendSuccess('รายการนี้กำลังดำเนินการอยู่ โปรดรอสักครู่แล้วลองใหม่');
        }

        try {
            $chk = null;

            // ===== 1) Claim แบบ atomic =====
            $claimed = \Illuminate\Support\Facades\DB::transaction(function () use ($id, $request, $remark, $user, &$chk) {
                // ล็อกแถว withdraws กัน request ซ้อน
                $chk = \Illuminate\Support\Facades\DB::table('withdraws')
                    ->where('code', $id)
                    ->lockForUpdate()
                    ->first();

                if (! $chk) {
                    return ['ok' => false, 'type' => 'not_found'];
                }

                $status = (int) ($chk->status ?? 0);

                if ($status === 1) {
                    return ['ok' => false, 'type' => 'done'];
                }

                if ($status === 2) {
                    return ['ok' => false, 'type' => 'already_cleared'];
                }

                if ($status === 9) {
                    return ['ok' => false, 'type' => 'processing'];
                }

                // ตั้งเป็นกำลังดำเนินการก่อน (กันซ้ำ)
                \Illuminate\Support\Facades\DB::table('withdraws')
                    ->where('code', $id)
                    ->update([
                        'ip_admin' => $request->ip(),
                        'remark_admin' => $remark,
                        'status' => 9, // PROCESSING
                        'emp_approve' => $this->id(),
                        'user_update' => $user,
                        'date_approve' => now()->toDateTimeString(),
                    ]);

                return ['ok' => true, 'type' => 'claimed'];
            });

            if (! $claimed['ok']) {
                if ($claimed['type'] === 'not_found') {
                    return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
                }
                if ($claimed['type'] === 'done') {
                    return $this->sendSuccess('รายการนี้ เสร็จสิ้นแล้ว โปรด F5');
                }
                if ($claimed['type'] === 'already_cleared') {
                    return $this->sendSuccess('รายการนี้ถูกคืนยอดแล้ว โปรด F5');
                }
                if ($claimed['type'] === 'processing') {
                    return $this->sendSuccess('รายการนี้กำลังถูกดำเนินการอยู่ โปรดรอสักครู่แล้ว F5');
                }

                return $this->sendSuccess('ไม่สามารถดำเนินการได้ โปรด F5');
            }

            // ===== 2) ทำการคืนยอด (นอก transaction เพื่อไม่ล็อก DB นาน) =====
            $datanew = [
                'refer_code' => $id,
                'refer_table' => 'withdraws',
                'remark' => 'คืนยอดจากการถอน',
                'kind' => 'ROLLBACK',
                'amount' => (float) ($chk->balance ?? 0),
                'amount_balance' => (float) ($chk->amount_balance ?? 0),
                'withdraw_limit' => (float) ($chk->amount_limit ?? 0),
                'withdraw_limit_amount' => (float) ($chk->amount_limit_rate ?? 0),
                'pro_code' => (int) ($chk->pro_code ?? 0),
                'pro_name' => (string) ($chk->pro_name ?? ''),
                'method' => 'D',
                'member_code' => (int) ($chk->member_code ?? 0),
                'emp_code' => $this->id(),
                'emp_name' => $this->user()->name.' '.$this->user()->surname,
            ];

            $response = $this->memberCreditLogRepository->setWalletSeamlessWithdraw($datanew);

            if ($response) {
                // finalize: คืนยอดสำเร็จ
               $this->repository->update([
                   'ip_admin' => $request->ip(),
                   'remark_admin' => $remark,
                   'status' => 2,
                   'emp_approve' => $this->id(),
                   'user_update' => $user,
                   'date_approve' => now()->toDateTimeString(),
               ], $id);
//                \Illuminate\Support\Facades\DB::table('withdraws')
//                    ->where('code', $id)
//                    ->update([
//                        'ip_admin' => $request->ip(),
//                        'remark_admin' => $remark,
//                        'status' => 2,
//                        'emp_approve' => $this->id(),
//                        'user_update' => $user,
//                        'date_approve' => now()->toDateTimeString(),
//                    ]);

                // ปิด bill เดิม (WITHDRAW) เป็น R ถ้ามี
                $bill = app('Gametech\Payment\Repositories\BillRepository')->findOneWhere([
                    'refer_code' => $chk->code,
                    'refer_table' => 'withdraws',
                    'method' => 'WITHDRAW',
                ]);

                if ($bill) {
                    $bill->complete = 'R';
                    $bill->save();
                }

                return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
            }

            // ถ้าไม่สำเร็จ: คืน status กลับไปให้ทำใหม่ได้
            \Illuminate\Support\Facades\DB::table('withdraws')
                ->where('code', $id)
                ->update([
                    'status' => 0,
                    'user_update' => $user,
                ]);

            return $this->sendError('ไม่สามารถคืนยอดได้ โปรดลองใหม่', 200);
        } finally {
            optional($lock)->release();
        }
    }

    public function destroy(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');

        $chk = $this->repository->find($id);

        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        if ($chk->status === 1) {
            return $this->sendSuccess('รายการนี้ เสรฺ็จสิ้นแล้ว โปรด F5');
        }
        $data['enable'] = 'N';
        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        $bill = app('Gametech\Payment\Repositories\BillRepository')->findOneWhere([
            'refer_code' => $chk->code,
            'refer_table' => 'withdraws',
            'method' => 'WITHDRAW',
        ]);

        if ($bill) {
            $bill->complete = 'N';
            $bill->save();
        }

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function fixSubmit(Request $request)
    {
        try {
            $user = $this->user()->name.' '.$this->user()->surname;
            $id = $request->input('id');

            \Log::info('Attempting to fix withdraw', ['id' => $id, 'user' => $user]);

            $chk = $this->repository->find($id);

            if (! $chk) {
                \Log::error('Withdraw not found', ['id' => $id]);
                return $this->sendError('ไม่พบรายการนี้', 200);
            }

            \Log::info('Current withdraw status', [
                'id' => $id,
                'status_withdraw' => $chk->status_withdraw,
                'emp_approve' => $chk->emp_approve,
                'status' => $chk->status
            ]);

            // ตรวจสอบว่า status ปัจจุบันสามารถคืนยอดได้หรือไม่
            if ($chk->status_withdraw === 'C' || $chk->status_withdraw === 'F') {
                \Log::error('Cannot fix withdraw with status', ['status' => $chk->status_withdraw]);
                return $this->sendError('ไม่สามารถคืนยอดรายการที่สถานะ: ' . $chk->status_withdraw, 200);
            }

            // ตรวจสอบว่ารายการนี้มีคนอนุมัติแล้วหรือยัง
            if ($chk->emp_approve > 0 && $chk->status == 1) {
                \Log::error('Withdraw already approved and completed', ['emp_approve' => $chk->emp_approve, 'status' => $chk->status]);
                return $this->sendError('รายการนี้มีผู้ทำรายการแล้ว ไม่สามารถคืนยอดได้', 200);
            }

            $data['emp_approve'] = 0;
            $data['status_withdraw'] = 'W';
            $data['user_update'] = $user;

            \Log::info('Updating withdraw with data', $data);

            $result = $this->repository->update($data, $id);

            if ($result) {
                \Log::info('Withdraw fixed successfully', ['id' => $id]);
                return $this->sendSuccess('รายการนี้ถูกคืนยอดแล้ว โปรด F5');
            } else {
                \Log::error('Failed to update withdraw', ['id' => $id]);
                return $this->sendError('ไม่สามารถคืนยอดได้ โปรดลองใหม่', 200);
            }

        } catch (\Exception $e) {
            \Log::error('Error in fixSubmit', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('เกิดข้อผิดพลาด: ' . $e->getMessage(), 200);
        }
    }

    public function loadBank()
    {
        $banks = [
            'value' => '0',
            'text' => 'ไม่ระบุบัญชี',
        ];

        $responses = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutAll()->toArray();

        $responses = collect($responses)->map(function ($items) {
            $item = (object) $items;

            //            dd($item);
            return [
                'value' => $item->code,
                'text' => $item->bank['name_th'].' ['.$item->acc_no.']'.$item->acc_name,
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

    public function loadUser(Request $request)
    {
        $id = $request->input('id');

        $response = $this->memberRepository->getUser($id);
        //        $data = $this->memberRepository->findOneWhere(['user_name' => $id , 'enable' => 'Y']);
        if (empty($response)) {
            $data = [
                'member_username' => '',
                'member_gameuser' => '',
                'member_name' => '',
                'member_account' => '',
                'member_bank' => '',
                'member_bank_pic' => '',
                'balance' => '',
                'member_code' => '',
            ];

            return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');
        }

        $data = [
            'member_username' => $response->user_name,
            'member_gameuser' => $response->user->user_name,
            'member_name' => $response->name,
            'member_account' => $response->acc_no,
            'member_bank' => $response->bank->name_th,
            'member_bank_pic' => $response->bank->filepic,
            'balance' => $response->user->balance,
            'member_code' => $response->code,
        ];

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');

    }

    public function create(Request $request)
    {
        $config = $this->getCoreConfig();
        $ip = $request->ip();
        $user = $this->user()->name.' '.$this->user()->surname;
        $datenow = now()->toDateTimeString();

        $data = json_decode($request['data'], true);
        $id = $data['member_code'];
        $amount = $data['amount'] * 1;
        $date = $data['date_record'];
        $time = $data['timedept'];

        $chk = $this->memberRepository->find($id);
        if (! $chk) {
            return $this->sendSuccess('ไม่พบข้อมูลดังกล่าว');
        }
        $balance = $chk->user->balance;

        if ($amount < 1) {

            //            session()->flash('error', 'พบข้อผิดพลาด คุณป้อนจำนวนไม่ถูกต้อง');
            return $this->sendError('พบข้อผิดพลาด คุณป้อนจำนวนไม่ถูกต้อง');

        } elseif ($balance < $amount) {

            return $this->sendError('ไม่สามารถดำเนินการได้ จำนวนเงินไม่เพียงพอ');

        } else {

            $response = $this->repository->withdrawSingleNew($id, $amount, $date, $time);
            if ($response['success'] === true) {
                //                session()->flash('success', 'คุณทำรายการแจ้งถอนเงิน สำเร็จแล้ว');
                return $this->sendSuccess('คุณทำรายการแจ้งถอนเงิน สำเร็จแล้ว');
            } else {
                //                session()->flash('error', $response['msg']);
                return $this->sendError($response['msg']);
            }

        }

    }
}
