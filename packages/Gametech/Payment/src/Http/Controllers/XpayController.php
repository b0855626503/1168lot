<?php

namespace Gametech\Payment\Http\Controllers;

use App\Events\RealTimeMessage;
use App\Libraries\XPay;
use Carbon\Carbon;
use Gametech\Auto\Jobs\UpdateBalancexpay;
use Gametech\Core\Repositories\CheckCaseRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankAccountRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\BankRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XpayController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberRepository;

    protected $bankRepository;

    protected $bankAccountRepository;

    protected $bankPaymentRepository;

    public function __construct(
        CheckCaseRepository $repository,
        MemberRepository $memberRepository,
        BankAccountRepository $bankAccountRepository,
        BankPaymentRepository $bankPaymentRepository,
        BankRepository $bankRepository
    ) {
        $this->_config = request('_config');

        $this->repository = $repository;

        $this->memberRepository = $memberRepository;

        $this->bankRepository = $bankRepository;

        $this->bankAccountRepository = $bankAccountRepository;

        $this->bankPaymentRepository = $bankPaymentRepository;
    }

    public function index($id)
    {

        $data = $this->repository->findOneWhere(['detail' => $id]);
        $member = $this->memberRepository->findOneWhere(['user_name' => $data->username]);

        return view('topup.qr', compact('data', 'member'));
    }

    public function deposit(Request $request)
    {
        $api = new XPay;
        $request->validate([
            'amount' => 'required|numeric',
        ]);

        $member = auth()->guard('customer')->user();

        Log::channel('xpay_deposit_create')->info('เริ่มสร้างรายการฝาก', [
            'debug' => 'start',
        ]);

        $amount = (float) $request->input('amount');
        $amount = number_format($amount, 2, '.', '');

        $min_deposit = config('payment.min_deposit', 100);
        if ($amount < $min_deposit) {
            $return['success'] = false;
            $return['msg'] = __('app.topup.min_deposit', ['amount' => $min_deposit]);

            return response()->json($return);
        }

        $forceCreate = $request->boolean('force', false);

        $check = $this->repository->orderBy('date_create', 'desc')
            ->findWhere(['username' => $member->user_name])
            ->first();

        if ($check && $check->status === 'CREATE') {
            $createdAt = Carbon::parse($check->date_create);

            // 🔴 เกิน 15 นาที → หมดอายุอัตโนมัติ
            if ($createdAt->diffInMinutes(now()) > 15) {
                $check->status = 'EXPIRED';
                $check->save();
            }
            // 🟡 ยังไม่เกิน → รอให้ user ตัดสินใจ (force หรือใช้รายการเดิม)
            elseif (! $forceCreate) {
                return response()->json([
                    'success' => false,
                    'status' => 'has_pending',
                    'msg' => __('app.topup.has_pending'),
                    'data' => [
                        'amount' => $check->amount,
                        'payamount' => $check->payamount,
                        'txid' => $check->txid,
                        'qrcode' => $check->qrcode,
                        'url' => route('api.payment.index', ['id' => $check->detail]),
                    ],
                ]);
            }
            // ✅ กดยืนยันสร้างใหม่ → ยกเลิกรายการ
            elseif ($forceCreate) {
                $params = [
                    'transactionId' => trim($check->detail),
                    'timestamp' => trim(now('UTC')->toIso8601String()),
                ];
                $cancelUrl = config('payment.api_url').'/payment/deposit/cancel';
                $response = $api->create_cancel($cancelUrl, $params);

                if ($response['success'] === true) {
                    $check->status = 'CANCEL'; // แก้จาก EXPIRED → CANCEL
                    $check->save();
                } else {
                    return response()->json([
                        'success' => false,
                        'msg' => __('app.topup.cancel_failed'),
                        'code' => $response['code'],
                    ]);
                }
            }
        }

        $order_id = 'DEP-'.str_pad($member->code, 6, '0', STR_PAD_LEFT).'-'.date('YmdHis');
        //        $amount = number_format($amount, 0);
        $cus_bank = $api->Banks($member->bank_code);
        if ($cus_bank === false) {
            $return['success'] = false;
            $return['msg'] = __('app.topup.wrong_bank');

            return response()->json($return);
        }

        $param = [
            'refId' => trim($order_id),
            'amount' => (float) $amount,
            'userId' => trim($member->user_name),
            'accountName' => trim($member->name),
            'accountNo' => trim($member->acc_no),
            'bankCode' => trim($cus_bank),
            'extendParams' => [
                'username' => trim($member->user_name),
            ],
            'timestamp' => trim(now('UTC')->toIso8601String()),
        ];

        $url = config('payment.api_url').'/payment/deposit/qrcode';
        $response = $api->create($url, $param);
        if ($response['success'] === true) {
            $this->repository->create([
                'txid' => $order_id,
                'amount' => $amount,
                'payamount' => $response['data']['transferAmount'],
                'username' => $member->user_name,
                'name' => $member->name,
                'detail' => $response['data']['transactionId'],
                //                'url' => $response['data']['qrcode'],
                'qrcode' => $response['data']['qrcode'],
                'status' => $response['data']['status'],
                'user_create' => $member->name,
                'user_update' => $member->name,
            ]);

            $return['url'] = route('api.payment.index', ['id' => $response['data']['transactionId']]);
            $return['msg'] = __('app.topup.create');
            $return['code'] = $response['code'];
            $return['success'] = true;

        } else {

            $return['success'] = false;
            if ($response['code'] === 9991 && $check && $check->status === 'CREATE') {
                $return['url'] = route('api.payment.index', ['id' => $check->detail]);
                $return['success'] = true;
                $return['code'] = $response['code'];
                $return['msg'] = __('app.topup.dup');

                return response()->json($return);
            }

        }
        $return['msg'] = $response['msg'];

        return response()->json($return);

    }

    public function deposit_callback(Request $request)
    {
        $datenow = now()->toDateTimeString();
        $message = $request->all();

        Log::channel('xpay_deposit_callback')->info('Callback การฝาก', $message);

        $refId = $message['refId'];
        $transactionId = $message['transactionId'];
        $amount = $message['amount'];
        $payAmount = $message['payAmount'] ?? $amount;
        $status = $message['status'];
        $username = $message['extendParams']['username'] ?? '';

        $case = $this->repository->findOneWhere(['txid' => $refId]);
        if ($case) {

            $this->repository->update([
                'status' => $status,
            ], $case->code);
        }

        if ($status === 'PAID') {

            $member = $this->memberRepository->findOneWhere(['user_name' => $username]);
            $bank_account = $this->bankAccountRepository->findOneWhere([
                'banks' => 303, 'bank_type' => 1, 'enable' => 'Y', 'status_auto' => 'Y',
            ]);

            UpdateBalancexpay::dispatch()->onQueue('topup');

            $bank = $this->bankRepository->find($bank_account->banks);
            $detail = ' REF ID : '.$refId.'('.$transactionId.')';
            $hash = md5($bank_account->code.$datenow.$amount.$detail);

            $data = [
                'bank' => strtolower($bank->shortcode.'_'.$bank_account->acc_no),
                'detail' => $detail.' จำนวน '.$amount,
                'account_code' => $bank_account->code,
                'autocheck' => 'W',
                'bankstatus' => 1,
                'bank_name' => $bank->shortcode,
                'bank_time' => $datenow,
                'channel' => 'QR',
                'value' => $amount,
                'tx_hash' => $hash,
                'txid' => $refId,
                'status' => 0,
                'ip_admin' => request()->ip(),
                'member_topup' => $member->code,
                'remark_admin' => '',
                'emp_topup' => 0,
                'user_create' => 'รอระบบเติมอัตโนมัติ ทำรายการฝากเงินโดย xpay QR',
                'create_by' => 'SYSAUTO',
            ];

            $check = $this->bankPaymentRepository->findOneWhere(['txid' => $refId]);
            if (! $check) {
                $this->bankPaymentRepository->create($data);
            }

            return response()->json(['code' => 0, 'msg' => 'success']);

        }

        return response()->json(['code' => 0, 'msg' => 'success']);

    }

    public function withdraw_callback(Request $request)
    {
        $config = core()->getConfigData();
        $datenow = now()->toDateTimeString();
        $message = $request->all();

        Log::channel('xpay_withdraw_callback')->info('Callback การฝาก', $message);

        $refId = $message['refId'];
        $transactionId = $message['transactionId'];
        $amount = $message['amount'];
        $payAmount = $message['payAmount'] ?? $amount;
        $status = $message['status'];
        $username = $message['extendParams']['username'] ?? '';

        UpdateBalancexpay::dispatch()->onQueue('topup');

        if ($config->seamless == 'Y') {
            $data = app('Gametech\Payment\Repositories\WithdrawSeamlessRepository')->findOneWhere(['txid' => $refId]);
        } else {
            $data = app('Gametech\Payment\Repositories\WithdrawRepository')->findOneWhere(['txid' => $refId]);
        }

        if ($status === 'PAID') {

            $data->remark_admin = '[Order No :'.$transactionId.'] โอนให้ลุกค้าแล้ว - '.$data->remark_admin;
            $data->status_withdraw = 'C';
            $data->save();
            broadcast(new RealTimeMessage('xpay โอนเงินให้ลูกค้าแล้ว ID : '.$data->member_user.' จำนวนเงิน '.$amount.' รายการแจ้งถอนที่ '.$data->code));

        } else {

            if ($config->seamless == 'Y') {
                $datanew = [
                    'refer_code' => $data->code,
                    'refer_table' => 'withdraws',
                    'remark' => 'คืนยอดจากการถอน '.$refId,
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
                    'kind' => 'ROLLBACK',
                    'remark' => 'คืนยอดจากการแจ้งถอน '.$refId,
                    'amount' => $amount,
                    'method' => 'D',
                    'member_code' => $data->member_code,
                    'emp_code' => 0,
                    'emp_name' => 'SYSTEM',
                ];
                $response = app('Gametech\Member\Repositories\MemberCreditLogRepository')->setWallet($datanew);

            }
            if ($response) {
                broadcast(new RealTimeMessage('xpay Payment โอนเงินไม่สำเร็จ ID : '.$data->member_user.' จำนวนเงิน '.$amount.' Ref ID '.$refId.' ระบบคืนยอดให้ลูกค้าแล้ว'));
                $data->remark_admin = '[Order ID :'.$transactionId.'] โอนไม่สำเร็จ และ ระบบคืนยอดแล้ว';
            } else {
                broadcast(new RealTimeMessage('xpay Payment โอนไม่สำเร็จ และระบบคืนยอดไม่ได้  ID : '.$data->member_user.' จำนวนเงิน '.$amount.' Ref ID '.$refId));
                $data->remark_admin = '[Order ID :'.$transactionId.'] โอนไม่สำเร็จ โปรดคืนยอดให้ลูกค้าเอง ระบบคืนไม่ได้';
            }

            //            $data->remark_admin = '[Order No :'.$transactionId.'] ผิดพลาดไม่สามารถดำเนินการได้ - '.$data->remark_admin;
            $data->status_withdraw = 'R';
            $data->status = 2;
            $data->save();

        }

        return response()->json(['code' => 0, 'msg' => 'success']);

    }

    public function expire($txid)
    {
        $repo = $this->repository;

        $case = $repo->findOneWhere(['detail' => $txid]);
        if ($case && $case->status !== 'PAID') {
            $repo->update([
                'status' => 'EXPIRED',
            ], $case->code);
        }

        return response()->json(['success' => true]);
    }

    public function checkStatus($txid)
    {
        $case = $this->repository->findOneWhere(['detail' => $txid]);

        if (! $case) {
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

        if (! $case) {
            return response()->json(['success' => false, 'status' => 'NOT_FOUND']);
        }

        $case->downloaded += 1;
        $case->save();

        return response()->json(['success' => true]);
    }
}
