<?php

namespace Gametech\Payment\Http\Controllers;

use App\Events\RealTimeMessage;
use App\Libraries\SulifuPay;
use Gametech\Auto\Jobs\UpdateBalanceWildPay;
use Gametech\Core\Repositories\CheckCaseRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankAccountRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\BankRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SulifuPayController extends AppBaseController
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

    public function deposit_usd(Request $request)
    {
        $api = new SulifuPay;
        $request->validate([
            'amount' => 'required|numeric',
        ]);

        $member = auth()->guard('customer')->user();

        Log::channel('sulifu_deposit_create')->info('เริ่มสร้างรายการฝาก', [
            'debug' => 'start',
        ]);

        $amount = (float) $request->input('amount');
        $amount = number_format($amount, 2, '.', '');

        $min_deposit = 5;
        if ($amount < $min_deposit) {
            $return['success'] = false;
            $return['msg'] = __('app.topup.min_deposit', ['amount' => $min_deposit]);

            return response()->json($return);
        }

        $order_id = 'USD-'.str_pad($member->code, 6, '0', STR_PAD_LEFT).'-'.date('YmdHis');
        //        $amount = number_format($amount, 0);
        //                $playerPayBankName = $api->BanksKorea($member->bank_code);
        //        if ($cus_bank === false) {
        //            $return['success'] = false;
        //            $return['msg'] = __('app.topup.wrong_bank');
        //
        //            return response()->json($return);
        //        }

        $merNo = config('payment.merchant_no_usd');
        $cType = 'QR'; // QR Code type
        $bankCode = null;
        $notifyUrl = route('api.payment.deposit.callback.usd');
        $apikey = config('payment.api_key_usd');
        $orderAmount = (float) $amount;
        //        $idNo = $member->idNo; // Member ID or ID card number, can be member code or phone number
        //        $telCo = $member->telCo;
        $sign = md5($merNo.$order_id.$orderAmount.$apikey);
        $param = [
            'merNo' => trim($merNo),
            'tradeNo' => trim($order_id),
            'cType' => trim($cType),
            'bankCode' => trim($bankCode),
            'orderAmount' => $orderAmount,
            'playerId' => trim($member->user_name),
            'playerName' => trim($member->name),
            'playerPayAcc' => trim($member->acc_no),
            //            'playerPayBankName' => trim($playerPayBankName),
            'playerPhoneNumber' => trim($member->tel),
            //            'idNo' => trim($idNo),
            //            'telCo' => trim($telCo),
            'notifyUrl' => trim($notifyUrl),
            'sign' => trim($sign),

        ];

        $url = config('payment.api_url').'/pay/createOrder';
        $response = $api->create($url, $param);
        if ($response['success'] === true) {
            $this->repository->create([
                'txid' => $order_id,
                'amount' => $amount,
                'payamount' => $response['data']['param']['money'],
                'username' => $member->user_name,
                'name' => $member->name,
                'detail' => $response['data']['oid'],
                'url' => $response['data']['url'],
                //                'qrcode' => $response['data']['qrcode'],
                'status' => 'CREATED',
                'user_create' => $member->name,
                'user_update' => $member->name,
            ]);

            $return['url'] = $response['data']['url'];
            $return['msg'] = __('app.topup.create');
            //            $return['code'] = $response['code'];
            $return['success'] = true;

        } else {

            $return['success'] = false;

        }
        $return['msg'] = $response['msg'];

        return response()->json($return);

    }

    public function deposit_khr(Request $request)
    {
        $api = new SulifuPay;
        $request->validate([
            'amount' => 'required|numeric',
        ]);

        $member = auth()->guard('customer')->user();

        Log::channel('sulifu_deposit_create')->info('เริ่มสร้างรายการฝาก', [
            'debug' => 'start',
        ]);

        $amount = (float) $request->input('amount');
        $amount = number_format($amount, 2, '.', '');

        $min_deposit = 10000;
        if ($amount < $min_deposit) {
            $return['success'] = false;
            $return['msg'] = __('app.topup.min_deposit', ['amount' => $min_deposit]);

            return response()->json($return);
        }

        $order_id = 'KHR-'.str_pad($member->code, 6, '0', STR_PAD_LEFT).'-'.date('YmdHis');
        //        $amount = number_format($amount, 0);
        //                $playerPayBankName = $api->BanksKorea($member->bank_code);
        //        if ($cus_bank === false) {
        //            $return['success'] = false;
        //            $return['msg'] = __('app.topup.wrong_bank');
        //
        //            return response()->json($return);
        //        }

        $merNo = config('payment.merchant_no');
        $cType = 'QR'; // QR Code type
        $bankCode = null;
        $notifyUrl = route('api.payment.deposit.callback.khr');
        $apikey = config('payment.api_key');
        $orderAmount = (float) $amount;
        //        $idNo = $member->idNo; // Member ID or ID card number, can be member code or phone number
        //        $telCo = $member->telCo;
        $sign = md5($merNo.$order_id.$orderAmount.$apikey);
        $param = [
            'merNo' => trim($merNo),
            'tradeNo' => trim($order_id),
            'cType' => trim($cType),
            'bankCode' => trim($bankCode),
            'orderAmount' => $orderAmount,
            'playerId' => trim($member->user_name),
            'playerName' => trim($member->name),
            'playerPayAcc' => trim($member->acc_no),
            //            'playerPayBankName' => trim($playerPayBankName),
            'playerPhoneNumber' => trim($member->tel),
            //            'idNo' => trim($idNo),
            //            'telCo' => trim($telCo),
            'notifyUrl' => trim($notifyUrl),
            'sign' => trim($sign),

        ];

        $url = config('payment.api_url').'/pay/createOrder';
        $response = $api->create($url, $param);
        if ($response['success'] === true) {
            $this->repository->create([
                'txid' => $order_id,
                'amount' => $amount,
                'payamount' => $response['data']['param']['money'],
                'username' => $member->user_name,
                'name' => $member->name,
                'detail' => $response['data']['oid'],
                'url' => $response['data']['url'],
                //                'qrcode' => $response['data']['qrcode'],
                'status' => 'CREATED',
                'user_create' => $member->name,
                'user_update' => $member->name,
            ]);

            $return['url'] = $response['data']['url'];
            $return['msg'] = __('app.topup.create');
            //            $return['code'] = $response['code'];
            $return['success'] = true;

        } else {

            $return['success'] = false;

        }
        $return['msg'] = $response['msg'];

        return response()->json($return);

    }

    public function deposit_callback_usd(Request $request)
    {
        $datenow = now()->toDateTimeString();
        $message = $request->all();
        $statusArr = [
            '-1' => 'FAILED',
            '0' => 'PENDING',
            '1' => 'SUCCESS',
            '9' => 'REVIEW',
        ];
        Log::channel('sulifu_deposit_callback')->info('Callback การฝาก', $message);

        $refId = $message['tradeNo'];
        $transactionId = $message['tradeNo'];
        $amount = $message['topupAmount'];
        $payAmount = $message['topupAmount'] ?? $amount;
        $status = $message['tradeStatus'];
        //        $username = $message['extendParams']['username'] ?? '';

        $case = $this->repository->findOneWhere(['txid' => $refId]);
        if ($case) {

            $this->repository->update([
                'status' => $statusArr[$status] ?? 'UNKNOWN',
            ], $case->code);
        }

        if ($status === '1') {
            $mcode = explode('-', $refId);
            $username = $mcode[1] * 1 ?? '';
            $member = $this->memberRepository->findOneWhere(['code' => $username]);
            $bank_account = $this->bankAccountRepository->findOneWhere([
                'banks' => 301, 'bank_type' => 1, 'enable' => 'Y', 'status_auto' => 'Y',
            ]);

            //            UpdateBalanceWildPay::dispatch()->onQueue('topup');

            $bank = $this->bankRepository->find($bank_account->banks);
            $detail = ' REF ID : '.$refId;
            $hash = md5($bank_account->code.$datenow.$amount.$detail);

            $data = [
                'bank' => strtolower($bank->shortcode.'_'.$bank_account->acc_no),
                'detail' => $detail.' จำนวน '.$amount.' USD อัตราคูณ 10',
                'account_code' => $bank_account->code,
                'autocheck' => 'W',
                'bankstatus' => 1,
                'bank_name' => $bank->shortcode,
                'bank_time' => $datenow,
                'channel' => 'QR',
                'value' => $amount * 10,
                'tx_hash' => $hash,
                'txid' => $refId,
                'status' => 0,
                'ip_admin' => request()->ip(),
                'member_topup' => $member->code,
                'remark_admin' => '',
                'emp_topup' => 0,
                'user_create' => 'รอระบบเติมอัตโนมัติ ทำรายการฝากเงินโดย SulifuPay QR',
                'create_by' => 'SYSAUTO',
            ];

            $check = $this->bankPaymentRepository->findOneWhere(['txid' => $refId]);
            if (! $check) {
                $this->bankPaymentRepository->create($data);
            }

            return 'SUCCESS';

        }

        return 'SUCCESS';

    }

    public function deposit_callback_khr(Request $request)
    {
        $datenow = now()->toDateTimeString();
        $message = $request->all();
        $statusArr = [
            '-1' => 'FAILED',
            '0' => 'PENDING',
            '1' => 'SUCCESS',
            '9' => 'REVIEW',
        ];
        Log::channel('sulifu_deposit_callback')->info('Callback การฝาก', $message);

        $refId = $message['tradeNo'];
        $transactionId = $message['tradeNo'];
        $amount = $message['topupAmount'];
        $payAmount = $message['topupAmount'] ?? $amount;
        $status = $message['tradeStatus'];
        //        $username = $message['extendParams']['username'] ?? '';

        $case = $this->repository->findOneWhere(['txid' => $refId]);
        if ($case) {

            $this->repository->update([
                'status' => $statusArr[$status] ?? 'UNKNOWN',
            ], $case->code);
        }

        if ($status === '1') {
            $mcode = explode('-', $refId);
            $username = $mcode[1] * 1 ?? '';
            $member = $this->memberRepository->findOneWhere(['code' => $username]);
            $bank_account = $this->bankAccountRepository->findOneWhere([
                'banks' => 302, 'bank_type' => 1, 'enable' => 'Y', 'status_auto' => 'Y',
            ]);

            //            UpdateBalanceWildPay::dispatch()->onQueue('topup');

            $bank = $this->bankRepository->find($bank_account->banks);
            $detail = ' REF ID : '.$refId;
            $hash = md5($bank_account->code.$datenow.$amount.$detail);

            $data = [
                'bank' => strtolower($bank->shortcode.'_'.$bank_account->acc_no),
                'detail' => $detail.' จำนวน '.$amount.' KHR อัตราหาร 400',
                'account_code' => $bank_account->code,
                'autocheck' => 'W',
                'bankstatus' => 1,
                'bank_name' => $bank->shortcode,
                'bank_time' => $datenow,
                'channel' => 'QR',
                'value' => $amount / 400,
                'tx_hash' => $hash,
                'txid' => $refId,
                'status' => 0,
                'ip_admin' => request()->ip(),
                'member_topup' => $member->code,
                'remark_admin' => '',
                'emp_topup' => 0,
                'user_create' => 'รอระบบเติมอัตโนมัติ ทำรายการฝากเงินโดย SulifuPay QR',
                'create_by' => 'SYSAUTO',
            ];

            $check = $this->bankPaymentRepository->findOneWhere(['txid' => $refId]);
            if (! $check) {
                $this->bankPaymentRepository->create($data);
            }

            return 'SUCCESS';

        }

        return 'SUCCESS';

    }

    public function withdraw_callback(Request $request)
    {
        $config = core()->getConfigData();
        $datenow = now()->toDateTimeString();
        $message = $request->all();
        $statusArr = [
            '-2' => 'FAILED',
            '-1' => 'PAY_FAILED',
            '0' => 'PENDING',
            '1' => 'SUCCESS',
            '8' => 'API_AUDIT',
            '9' => 'MANUAL_AUDIT',
        ];

        Log::channel('sulifu_withdraw_callback')->info('Callback การฝาก', $message);

        $refId = $message['tradeNo'];
        $transactionId = $message['tradeNo'];
        $amount = $message['orderAmount'];
        $payAmount = $message['orderAmount'] ?? $amount;
        $status = $message['tradeStatus'];
        $username = $message['extendParams']['username'] ?? '';

        //        UpdateBalanceWildPay::dispatch()->onQueue('topup');

        if ($config->seamless == 'Y') {
            $data = app('Gametech\Payment\Repositories\WithdrawSeamlessRepository')->findOneWhere(['txid' => $refId]);
        } else {
            $data = app('Gametech\Payment\Repositories\WithdrawRepository')->findOneWhere(['txid' => $refId]);
        }

        if ($status === '1') {

            $data->remark_admin = '[Order No :'.$transactionId.'] โอนให้ลุกค้าแล้ว - '.$data->remark_admin;
            $data->status_withdraw = 'C';
            $data->save();
            broadcast(new RealTimeMessage('SulifuPay โอนเงินให้ลูกค้าแล้ว ID : '.$data->member_user.' จำนวนเงิน '.$amount.' รายการแจ้งถอนที่ '.$data->code));

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
                broadcast(new RealTimeMessage('Sulifu Payment โอนเงินไม่สำเร็จ ID : '.$data->member_user.' จำนวนเงิน '.$amount.' Ref ID '.$refId.' ระบบคืนยอดให้ลูกค้าแล้ว'));
                $data->remark_admin = '[Order ID :'.$transactionId.'] โอนไม่สำเร็จ และ ระบบคืนยอดแล้ว';
            } else {
                broadcast(new RealTimeMessage('Sulifu Payment โอนไม่สำเร็จ และระบบคืนยอดไม่ได้  ID : '.$data->member_user.' จำนวนเงิน '.$amount.' Ref ID '.$refId));
                $data->remark_admin = '[Order ID :'.$transactionId.'] โอนไม่สำเร็จ โปรดคืนยอดให้ลูกค้าเอง ระบบคืนไม่ได้';
            }

            //            $data->remark_admin = '[Order No :'.$transactionId.'] ผิดพลาดไม่สามารถดำเนินการได้ - '.$data->remark_admin;
            $data->status_withdraw = 'R';
            $data->status = 2;
            $data->save();

        }

        return 'SUCCESS';

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
