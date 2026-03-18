<?php

namespace Gametech\Payment\Http\Controllers;

use App\Events\RealTimeMessage;
use App\Events\RealTimeNewMessage;
use Exception;
use Gametech\Payment\Helpers\WebhookHelper;
use Gametech\Payment\Libraries\OnPay;
use Carbon\Carbon;
use Gametech\Auto\Jobs\UpdateBalanceOnPay;
use Gametech\Core\Repositories\CheckCaseRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankAccountRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\BankRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OnPayController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberRepository;

    protected $bankRepository;

    protected $bankAccountRepository;

    protected $bankPaymentRepository;

    public function __construct(
        CheckCaseRepository   $repository,
        MemberRepository      $memberRepository,
        BankAccountRepository $bankAccountRepository,
        BankPaymentRepository $bankPaymentRepository,
        BankRepository        $bankRepository
    )
    {
        $this->_config = request('_config');

        $this->repository = $repository;

        $this->memberRepository = $memberRepository;

        $this->bankRepository = $bankRepository;

        $this->bankAccountRepository = $bankAccountRepository;

        $this->bankPaymentRepository = $bankPaymentRepository;
    }

    public function index($id)
    {

        $banks = $this->bankRepository->findWhere(['enable' => 'Y'])->pluck('name_th', 'shortcode')->toArray();
        $data = $this->repository->findOneWhere(['detail' => $id]);
        $member = $this->memberRepository->findOneWhere(['user_name' => $data->username]);

        return view('topup.box.onpay_new', compact('data', 'member', 'banks'));
    }

    public function indextest($id)
    {

        $data = $this->repository->findOneWhere(['detail' => $id]);
        $member = $this->memberRepository->findOneWhere(['user_name' => $data->username]);

        return view('topup.box.onpay_new', compact('data', 'member'));
    }

    public function deposit(Request $request)
    {
        $api = new OnPay();
        $request->validate([
            'amount' => 'required|numeric',
        ]);

        $member = auth()->guard('customer')->user();

        Log::channel('onpay_deposit_create')->info('เริ่มสร้างรายการฝาก', [
            'debug' => 'start',
        ]);

        $bank_account = $this->bankAccountRepository->findOneWhere([
            'banks' => 310, 'bank_type' => 1, 'enable' => 'Y', 'status_auto' => 'Y',
        ]);

        if (!$bank_account) {
            $return['success'] = false;
            $return['msg'] = __('app.topup.fail');

            return response()->json($return);
        }

        $amount = (float)$request->input('amount');
        $amount = number_format($amount, 2, '.', '');

        $min_deposit = config('onpay.min_deposit', 100);
        if ($amount < $min_deposit) {
            $return['success'] = false;
            $return['msg'] = __('app.topup.min_deposit', ['amount' => $min_deposit]);

            return response()->json($return);
        }


        $transactionId = 'ODEP-' . str_pad($member->code, 6, '0', STR_PAD_LEFT) . '-' . date('YmdHis');

        $acc_no = $member->acc_no;

        $bankName = $api->Banks($member->bank_code);

        $callbackUrl = route('api.onpay.deposit.callback');

        if ($bankName === false) {
            $return['success'] = false;
            $return['msg'] = __('app.topup.wrong_bank');

            return response()->json($return);
        }


        UpdateBalanceOnPay::dispatch()->onQueue('topup');

        $param = [
            'order_id' => trim($transactionId),
            'amount' => (float)$amount,
            'ref_account' => trim($acc_no),
            'ref_bank_code' => trim($bankName),
            'ref_name_th' => trim($member->name),
            'ref_name_en' => trim($member->name),
            'ref_user_id' => trim($member->user_name),
            'callback_url' => trim($callbackUrl),
        ];

        $url = config('onpay.api_url') . '/api/v1/deposit/create_qr_code';
        $response = $api->create($url, $param);
        if ($response['success'] === true) {
            $this->repository->create([
                'bank_code' => $bank_account->banks,
                'method' => 1,
                'txid' => $transactionId,
                'amount' => $amount,
                'payamount' => $response['data']['amount'],
                'username' => trim($member->user_name),
                'name' => $member->name,
                'detail' => $response['data']['txn_no'],
                'url' => $response['data']['qr_code_url'],
                'qrcode' => $response['data']['qr_code'],
                'status' => 'pending',
                'expired_date' => Carbon::parse($response['data']['expired_at'])->setTimezone('Asia/Bangkok'),
                'user_create' => $member->name,
                'user_update' => $member->name,
            ]);

            $return['url'] = route('api.onpay.index', ['id' => $response['data']['txn_no']]);
//            $return['url'] = $response['data']['qr_code_url'];
            $return['msg'] = __('app.topup.create');
            $return['code'] = $response['code'];
//            $return['target'] = 'blank';
            $return['success'] = true;
            return response()->json($return);
        }

        $return['msg'] = $response['msg'];

        return response()->json($return);

    }

    public function deposit_callback(Request $request)
    {
        $datenow = now()->toDateTimeString();
        $message = $request->all();

        Log::channel('onpay_deposit_callback')->info('Callback การฝาก', $message);

        $type = $message['type'];
        $refId = $message['txn_no'];
        $transactionId = $message['txn_ref_order_id'];
        $status = $message['status'];
        $status_des = $message['status_des'];


        $username = $message['txn_ref_user_id'] ?? '';

        UpdateBalanceOnPay::dispatch()->onQueue('topup');

        $case = $this->repository->findOneWhere(['txid' => $transactionId]);
        if ($case) {

            $this->repository->update([
                'status' => $status,
            ], $case->code);
        }


        if ($status === 'success' && $type === 'deposit' && $status_des === 'success_deposit') {

            $amount = $case->amount ?? $message['amount'];
            $payAmount = $amount;


            $member = $this->memberRepository->findOneWhere(['user_name' => $username]);
            $bank_account = $this->bankAccountRepository->findOneWhere([
                'banks' => 310, 'bank_type' => 1, 'enable' => 'Y', 'status_auto' => 'Y',
            ]);

            $bank = $this->bankRepository->find($bank_account->banks);
            $detail = ' REF ID : ' . $refId;
            $hash = md5($bank_account->code . $amount . $detail);

            $data = [
                'bank' => strtolower($bank->shortcode . '_' . $bank_account->acc_no),
                'detail' => $detail . ' จำนวน ' . $amount,
                'account_code' => $bank_account->code,
                'autocheck' => 'W',
                'bankstatus' => 1,
                'bank_name' => $bank->shortcode,
                'bank_time' => $datenow,
                'channel' => 'QR',
                'value' => $amount,
                'tx_hash' => $hash,
                'txid' => $transactionId,
                'status' => 0,
                'ip_admin' => request()->ip(),
                'member_topup' => $member->code,
                'remark_admin' => '',
                'emp_topup' => 0,
                'user_create' => 'รอระบบเติมอัตโนมัติ ทำรายการฝากเงินโดย OnPay QR',
                'create_by' => 'SYSAUTO',
            ];

            $check = $this->bankPaymentRepository->findOneWhere(['txid' => $transactionId]);
            if (!$check) {
                $this->bankPaymentRepository->create($data);
            }

        }

        return response()->json(['status' => 'success'], 200);


    }

    public function withdraw_callback(Request $request)
    {
        $config = core()->getConfigData();
        $datenow = now()->toDateTimeString();
        $message = $request->all();

        Log::channel('onpay_withdraw_callback')->info('Callback การฝาก', $message);

        $type = $message['type'];
        $refId = $message['txn_no'];
        $transactionId = $message['txn_ref_order_id'];
        $status = $message['status'];
        $status_des = $message['status_des'];


//        $username = $message['txn_ref_user_id'] ?? '';
//			$username = $message['extendParams']['username'] ?? '';

        UpdateBalanceOnPay::dispatch()->onQueue('topup');
//        UpdateBalanceWellPay::dispatch()->onQueue('topup');


        $case = $this->repository->findOneWhere(['txid' => $transactionId]);
        if ($case) {

            $this->repository->update([
                'status' => $status,
            ], $case->code);
        }


        if ($config->seamless == 'Y') {
            $data = app('Gametech\Payment\Repositories\WithdrawSeamlessRepository')->findOneWhere(['txid' => $transactionId, 'status_withdraw' => 'A']);
        } else {
            $data = app('Gametech\Payment\Repositories\WithdrawRepository')->findOneWhere(['txid' => $transactionId, 'status_withdraw' => 'A']);
        }

        if(!$data){
            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        $amount = $data['amount'];

        if ($status === 'success' && $type === 'withdraw' && $status_des === 'success_withdraw') {


            $data->remark_admin = '[ Ref No :' . $refId . ' ] โอนให้ลุกค้าแล้ว ';
            $data->status_withdraw = 'C';
            $data->save();

            app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
                'ip' => request()->ip(),
                'credit_type' => 'W',
                'balance_before' => 0,
                'balance_after' => 0,
                'credit' => 0,
                'total' => 0,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => $amount,
                'credit_after' => $amount,
                'pro_code' => 0,
                'bank_code' => 0,
                'auto' => 'N',
                'enable' => 'Y',
                'user_create' => 'System Auto',
                'user_update' => 'System Auto',
                'refer_code' => $data->code,
                'refer_table' => 'withdraws',
                'remark' => 'รายการแจ้งถอนที่ '.$data->code.' / ไอดีที่ถอน : '.$data->member_user.' จำนวนเงิน '.$amount.' โอนเงินให้ลูกค้าแล้ว OnPay '.$transactionId.' [ '.$refId.' ]',
                'kind' => 'OTHER',
                'amount' => $amount,
                'amount_balance' => 0,
                'withdraw_limit' => 0,
                'withdraw_limit_amount' => 0,
                'method' => 'D',
                'member_code' => $data->member_code,
                'user_name' => $data->member_user,
                'emp_code' => 0,
                'emp_name' => 'SYSTEM',
            ]);

            broadcast(new RealTimeNewMessage(
                'OnPay ' . $transactionId . ' โอนเงินให้ลูกค้าแล้ว ID : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' รายการแจ้งถอนที่ ' . $data->code,
                [
                    'ui' => 'toast',
                    'as' => 'RealTime.Message.All',
                    'toast' => [
                        'className' => 'gt-toast gt-toast-success',
                        'duration' => 30000,
                        'gravity' => 'top',
                        'position' => 'right',
                        'avatar' => '/assets/admin/icons/alert.webp',
                    ],
                ]
            ));
//            broadcast(new RealTimeMessage('OnPay ' . $transactionId . ' โอนเงินให้ลูกค้าแล้ว ID : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' รายการแจ้งถอนที่ ' . $data->code));

        } elseif ($status === 'refund' || $status === 'fail') {

            if ($config->seamless === 'Y') {
                $datanew = [
                    'refer_code' => $data->code,
                    'refer_table' => 'withdraws',
                    'remark' => 'คืนยอดจากการถอน ' . $transactionId,
                    'kind' => 'ROLLBACK',
                    'amount' => $amount,
                    'amount_balance' => $data->amount_balance,
                    'withdraw_limit' => $data->amount_limit,
                    'withdraw_limit_amount' => $data->amount_limit_rate,
                    'method' => 'D',
                    'member_code' => $data->member_code,
                    'emp_code' => 0,
                    'emp_name' => 'SYSTEM',
                ];
                $response = app('Gametech\Member\Repositories\MemberCreditLogRepository')->setWalletSeamlessWithdraw($datanew);
            } else {
                $datanew = [
                    'refer_code' => $data->code,
                    'refer_table' => 'withdraws',
                    'remark' => 'คืนยอดจากการถอน ' . $transactionId,
                    'kind' => 'ROLLBACK',
                    'amount' => $amount,
                    'amount_balance' => $data->amount_balance,
                    'withdraw_limit' => $data->amount_limit,
                    'withdraw_limit_amount' => $data->amount_limit_rate,
                    'pro_code' => $data->pro_code,
                    'pro_name' => $data->pro_name,
                    'method' => 'D',
                    'member_code' => $data->member_code,
                    'emp_code' => 0,
                    'emp_name' => 'SYSTEM',
                ];

                $response = app('Gametech\Member\Repositories\MemberCreditLogRepository')->setWalletSingleWithdraw($datanew);

            }
            if ($response) {

                broadcast(new RealTimeNewMessage(
                    'OnPay ยกเลิกรายการแจ้งถอน ของ ID '.$data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . $refId . ' ระบบคืนยอดให้ลูกค้าแล้ว',
                    [
                        'ui' => 'toast',
                        'as' => 'RealTime.Message.All',
                        'toast' => [
                            'className' => 'gt-toast gt-toast-error',
                            'duration' => 0,
                            'gravity' => 'top',
                            'position' => 'right',
                            'avatar' => '/assets/admin/icons/alert.webp',
                        ],
                    ]
                ));
//                broadcast(new \App\Events\RealTimeNewMessage(
//                    'OnPay Payment โอนเงินไม่สำเร็จ ID : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . $refId . ' ระบบคืนยอดให้ลูกค้าแล้ว',
//                    [
//                        'ui' => 'toast',
//                        'as' => 'RealTime.Message.All', // จะเปลี่ยนเป็น RealTime.Message.User ก็ได้ (อย่าลืม listen ฝั่ง JS ให้ตรง)
//                        'toast' => [
//                            'className' => 'bg-warning text-dark',
//                            'duration' => 0,
//                            'gravity' => 'top',
//                            'position' => 'center',
//                        ],
//                    ]
//                ));

//                broadcast(new RealTimeMessage('onpay Payment โอนเงินไม่สำเร็จ ID : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . $refId . ' ระบบคืนยอดให้ลูกค้าแล้ว'));
                $data->remark_admin = '[ Ref ID :' . $refId . ' ] โอนไม่สำเร็จ และ ระบบคืนยอดแล้ว';
            } else {

                broadcast(new RealTimeNewMessage(
                    'OnPay ยกเลิกรายการแจ้งถอน ของ ID '.$data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . $refId . ' ระบบคืนยอด ให้ลูกค้าไม่ได้',
                    [
                        'ui' => 'toast',
                        'as' => 'RealTime.Message.All',
                        'toast' => [
                            'className' => 'gt-toast gt-toast-error',
                            'duration' => 0,
                            'gravity' => 'top',
                            'position' => 'right',
                            'avatar' => '/assets/admin/icons/alert.webp',
                        ],
                    ]
                ));
//                broadcast(new \App\Events\RealTimeNewMessage(
//                    'OnPay Payment โอนเงินไม่สำเร็จ ID : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . $refId . ' ระบบคืนยอด ให้ลูกค้าไม่ได้',
//                    [
//                        'ui' => 'toast',
//                        'as' => 'RealTime.Message.All', // จะเปลี่ยนเป็น RealTime.Message.User ก็ได้ (อย่าลืม listen ฝั่ง JS ให้ตรง)
//                        'toast' => [
//                            'className' => 'bg-warning text-dark',
//                            'duration' => 0,
//                            'gravity' => 'top',
//                            'position' => 'center',
//                        ],
//                    ]
//                ));
//                broadcast(new RealTimeMessage('onpay Payment โอนไม่สำเร็จ และระบบคืนยอดไม่ได้  ID : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . $refId));
                $data->remark_admin = '[ Ref ID :' . $refId . ' ] โอนไม่สำเร็จ โปรดคืนยอดให้ลูกค้าเอง ระบบคืนไม่ได้';
            }

            //            $data->remark_admin = '[Order No :'.$transactionId.'] ผิดพลาดไม่สามารถดำเนินการได้ - '.$data->remark_admin;
            $data->status_withdraw = 'R';
            $data->status = 2;
            $data->save();

        } else {

            broadcast(new RealTimeMessage('OnPay ' . $transactionId . ' สถานะ ' . ucfirst($status) . ' รายการถอน ID : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' รายการแจ้งถอนที่ ' . $data->code));
        }

        return response()->json(['code' => 0, 'msg' => 'success']);

    }

    public function expire($txid)
    {
        $repo = $this->repository;

        $case = $repo->findOneWhere(['detail' => $txid]);
        if ($case && $case->status !== 'completed') {
            $repo->update([
                'status' => 'expired',
            ], $case->code);
        }

        return response()->json(['success' => true]);
    }

    public function checkStatus($txid)
    {
        $case = $this->repository->findOneWhere(['detail' => $txid]);

        if (!$case) {
            return response()->json(['success' => false, 'status' => 'NOT_FOUND']);
        }

        return response()->json([
            'success' => true,
            'status' => $case->status, // เช่น 'PAID', 'EXPIRED', 'PENDING'
        ]);
    }

    public function qrDownloaded($txid)
    {
        $case = $this->repository->findOneWhere(['detail' => $txid]);

        if (!$case) {
            return response()->json(['success' => false, 'status' => 'NOT_FOUND']);
        }

        $case->downloaded += 1;
        $case->save();

        return response()->json(['success' => true]);
    }
}
