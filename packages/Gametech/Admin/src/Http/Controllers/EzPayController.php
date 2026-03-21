<?php

namespace Gametech\Admin\Http\Controllers;


use App\Events\RealTimeMessage;
use Gametech\Payment\Models\BankPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


class EzPayController extends AppBaseController
{

    protected $_config;

    public function __construct()
    {
        $this->_config = request('_config');

//        $this->middleware('api');
    }

    public function index($mobile, Request $request)
    {
        $datenow = now()->toDateTimeString();
        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('tw', $mobile);
        if (!$data) {
            return 1;
        }

        if ($data->webhook != 'Y') {
            return 1;
        }

        $data->checktime = $datenow;
        $data->save();

        $messages = [];
        $message = base64_decode($request->input('message'));
        $message = Str::replace('}{', ',', $message);
        $message = Str::replace('"', '', $message);
        $message = Str::of($message)->between('{', '}');

        $path = storage_path('logs/tw/webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($message, true), FILE_APPEND);

        $message = Str::of($message)->explode(',');
        for ($i = 0; $i < count($message); $i++) {
            $key_value = explode(':', $message[$i]);
            if ($key_value[0] == 'received_time') {
                $messages[$key_value[0]] = $key_value[1] . ":" . $key_value[2] . ":" . $key_value[3];
            } else {
                $messages[$key_value[0]] = $key_value[1];
            }

        }


        $amount = $messages['amount'] / 100;
        $date = Str::replace('T', ' ', $messages['received_time']);
        $date = Str::replace('+0700', '', $date);

        $hash = md5($data->code . $date . $amount . $messages['sender_mobile']);

        $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
        $newpayment->account_code = $data->code;
        $newpayment->bank = 'twl_' . $mobile;
        $newpayment->bankstatus = 1;
        $newpayment->bankname = 'TW';
        $newpayment->report_id = '';
        $newpayment->bank_time = $date;
        $newpayment->type = $messages['event_type'];
        $newpayment->title = 'Webhook';
        $newpayment->channel = 'WEBHOOK';
        $newpayment->value = $amount;
        $newpayment->tx_hash = $hash;
        $newpayment->detail = $messages['sender_mobile'];
        $newpayment->atranferer = $messages['sender_mobile'];
        $newpayment->time = $date;
        $newpayment->create_by = 'SYSAUTO';
        $newpayment->save();

    }

    public function create(Request $request)
    {

        $pompay = [
            'clientId' => 'GENXPAY',
            'transactionId' => Str::uuid(),
            'custName' => 'boat',
            'amount' => '1.00',
            'returnUrl' => 'https://demo.168csn.com/pompay/return',
            'callbackUrl' => 'https://demo.168csn.com/pompay/callback',
        ];
        $clientId = $pompay['clientId'];
        $transactionId = $pompay['transactionId'];
        $custName = $pompay['custName'];
        $custBank = '';
        $custMobile = '';
        $custEmail = '';
        $amount = $pompay['amount'];
        $returnUrl = $pompay['returnUrl'];
        $callbackUrl = $pompay['callbackUrl'];
        $paymentMethod = '';
        $bankAcc = '';
        $clientSecret = 'f9e1645cb90af76b5741e3b181123f8a0ab4b23f';
        $hash = hash('sha256', $clientId . $transactionId . $custName . $custBank . $custMobile . $custEmail . $amount . $returnUrl . $callbackUrl . $paymentMethod . $bankAcc . $clientSecret);

        return view($this->_config['view'], compact('hash', 'pompay'));
    }

    public function callback(Request $request)
    {
        $datenow = now()->toDateTimeString();
        $ip = $request->ip();
        $mobile = 'test';
        $messages = [];
        $message = $request->all();

        $path = storage_path('logs/ezpay/webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- CALLBACK --', true), FILE_APPEND);
        file_put_contents($path, print_r($message, true), FILE_APPEND);

        $case = app('Gametech\Core\Repositories\CheckCaseRepository')->findOneWhere(['txid' => $message['refId']]);
        if($case) {

            app('Gametech\Core\Repositories\CheckCaseRepository')->update([
                'status' => $message['status'],
                'detail' => $message['msg']
            ], $case->code);
        }

        if ($message['status'] == 'SUCCESS') {
//
            $mcodes = explode('-',$message['refId']);
            $mcodes = Str::substr($mcodes[2], 4, 4);
//            $mcodes = Str::of($message['refId'])->between('SUK', 'X')->__toString();
            $mcode = ($mcodes * 1);
//            $who = app('Gametech\Payment\Repositories\BankHengpayRepository')->findOneWhere(['referenceNo' => $message['data']['reference2']]);
            $member = app('Gametech\Member\Repositories\MemberRepository')->findOneWhere(['code' => $mcode]);

            $bank_account = app('Gametech\Payment\Repositories\BankAccountRepository')->findOneWhere(['banks' => 104, 'bank_type' => 1, 'enable' => 'Y', 'status_auto' => 'Y']);

            $bank = app('Gametech\Payment\Repositories\BankRepository')->find($bank_account->banks);

            $detail = 'EzPay REF1 : ' . $message['refId'];

            $amount = $message['amount'];

            $hash = md5($bank_account->code . $datenow . $amount . $detail);

            $data = [
                'bank' => strtolower($bank->shortcode . '_' . $bank_account->acc_no),
                'detail' => $detail,
                'account_code' => $bank_account->code,
                'autocheck' => 'W',
                'bankstatus' => 1,
                'bank_name' => $bank->shortcode,
                'bank_time' => $datenow,
                'channel' => 'QR',
                'value' => $amount,
                'tx_hash' => $hash,
                'txid' => $message['refId'],
                'status' => 0,
                'ip_admin' => $ip,
                'member_topup' => $member->code,
                'remark_admin' => '',
                'emp_topup' => 0,
                'user_create' => 'รอระบบเติมอัตโนมัติ ทำรายการฝากเงินโดย EzPay QR',
                'create_by' => 'SYSAUTO'
            ];

            $chk = app('Gametech\Payment\Repositories\BankPaymentRepository')->findOneWhere(['txid' => $message['refId']]);
            if (!$chk) {
                app('Gametech\Payment\Repositories\BankPaymentRepository')->create($data);
            }

//            $param = [
//                'status' => 200,
//                'qrCodeTransactionId' => $message['qrCodeTransactionId'],
//                'message' => 'Successfully received Payment Notification Callback'
//            ];

            if($case) {
                if ($message['status'] != 'SUCCESS') {
                    broadcast(new RealTimeMessage($message['refId'] . ' โดย ' . $case->username . ' ' . $case->name . ' สถานะ ' . $message['status'] . ' (' . $message['msg'] . ')'));
                }
            }

            $param = [
                'status' => 'success',
                'message' => 'Success'
            ];

            return $param;
//            return $this->sendSuccess('ok');
        }

    }

    public function returns(Request $request)
    {

        $datenow = now()->toDateTimeString();
        $ip = $request->ip();
        $mobile = 'test';
        $messages = [];
        $message = $request->all();

//        if ($message['status'] == 'success') {
//
//
//            $member = app('Gametech\Member\Repositories\MemberRepository')->findOneWhere(['user_name' => strtolower($message['custSecondaryName'])]);
//
//            $bank_account = app('Gametech\Payment\Repositories\BankAccountRepository')->findOneWhere(['pompay_default' => 'Y', 'bank_type' => 1, 'enable' => 'Y', 'status_auto' => 'Y']);
//
//            $bank = app('Gametech\Payment\Repositories\BankRepository')->find($bank_account->banks);
//
//            $detail = 'POMPAY QR ' . $message['referenceId'];
//
//            $amount = $message['amount'];
//
//            $hash = md5($bank_account->code . $datenow . $amount . $detail);
//
//            $data = [
//                'bank' => strtolower($bank->shortcode . '_' . $bank_account->acc_no),
//                'detail' => $detail,
//                'account_code' => $bank_account->code,
//                'autocheck' => 'W',
//                'bankstatus' => 1,
//                'bank_name' => $bank->shortcode,
//                'bank_time' => $datenow,
//                'channel' => 'QR',
//                'value' => $amount,
//                'tx_hash' => $hash,
//                'txid' => $message['transactionId'],
//                'status' => 0,
//                'ip_admin' => $ip,
//                'member_topup' => $member->code,
//                'remark_admin' => '',
//                'emp_topup' => 0,
//                'user_create' => 'รอระบบเติมอัตโนมัติ ทำรายการฝากเงินโดย POMPAY QR',
//                'create_by' => 'SYSAUTO'
//            ];
//
//            $chk = app('Gametech\Payment\Repositories\BankPaymentRepository')->findOneWhere(['txid' => $message['transactionId']]);
//            if(!$chk){
//                $response = app('Gametech\Payment\Repositories\BankPaymentRepository')->create($data);
//            }
//
//
//        }

        $path = storage_path('logs/pompay/webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- RETURN --', true), FILE_APPEND);
        file_put_contents($path, print_r($message, true), FILE_APPEND);

        return view($this->_config['view']);

    }

    public function payout_create(Request $request)
    {
        $mobile = 'test';
        $clientId = 'GENXPAY';
        $transactionId = Str::uuid()->__toString();
        $custName = 'boat';
        $custBank = 'kkb';
        $custBankAcc = '7322436536';
        $custMobile = '';
        $amount = '1.00';
        $custEmail = '';
        $returnUrl = '';
        $callbackUrl = 'https://demo.168csn.com/pompay/payout_callback';

        $clientSecret = 'f9e1645cb90af76b5741e3b181123f8a0ab4b23f';
        $hash = hash('sha256', $clientId . $transactionId . $custName . $custBank . $custBankAcc . $custMobile . $custEmail . $amount . $callbackUrl . $clientSecret);

        $pompay = [
            'clientId' => $clientId,
            'transactionId' => $transactionId,
            'custName' => $custName,
            'custBank' => $custBank,
            'custBankAcc' => $custBankAcc,
            'amount' => $amount,
            'callbackUrl' => $callbackUrl,
            'hashVal' => $hash,
        ];

        $response = Http::asForm()->post('https://staging.pompay.asia/v2/payout', $pompay);
        $return = $response->json();

        $path = storage_path('logs/pompay/payout_webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- CREATE --', true), FILE_APPEND);
        file_put_contents($path, print_r($return, true), FILE_APPEND);

        dd($return);
//        return view($this->_config['view'], compact('hash','pompay'));
    }

    public function payout_callback(Request $request)
    {
        $config = $this->getCoreConfig();
        $datenow = now()->toDateTimeString();
        $ip = $request->ip();
        $mobile = 'test';
        $messages = [];
        $message = $request->all();
        $message['date'] = $datenow;

        if ($config->seamless == 'Y') {
            $data = app('Gametech\Payment\Repositories\WithdrawSeamlessRepository')->findOneWhere(['txid' => $message['refId']]);
        } else {
            $data = app('Gametech\Payment\Repositories\WithdrawRepository')->findOneWhere(['txid' => $message['refId']]);
        }

        if($message['status'] === 'SUCCESS') {
            $status = ['SUCCESS' => 'C'];
            $data->remark_admin = '[REF ID :' . $message['refId'] . '] โอนให้ลุกค้าแล้ว - ' . $data->remark_admin;
            $data->status_withdraw = $status[$message['status']];
            $data->save();
            broadcast(new RealTimeMessage('EzPay Payment โอนเงินให้ลูกค้าแล้ว ID : '.$data->member_user.' จำนวนเงิน '.$message['amount'].' รายการแจ้งถอนที่ '.$data->code));

        }elseif($message['status'] === 'INPROGRESS'){
            $status = ['INPROGRESS' => 'C'];
            $data->remark_admin = '[REF ID :' . $message['refId'] . '] กำลังดำเนินการ - ' . $data->remark_admin;
//            $data->status_withdraw = $status[$message['status']];
            $data->save();
            broadcast(new RealTimeMessage('EzPay Payment กำลังดำเนินการ ID : '.$data->member_user.' จำนวนเงิน '.$message['amount'].' รายการแจ้งถอนที่ '.$data->code));

        }elseif($message['status'] === 'FAIL'){
            $status = ['SUCCESS' => 'C'];
            $data->remark_admin = '[REF ID :' . $message['refId'] . '] ผิดพลาดไม่สามารถดำเนินการได้ - ' . $data->remark_admin;
//            $data->status_withdraw = $status[$message['status']];
            $data->status = 2;
            $data->save();
            broadcast(new RealTimeMessage('EzPay Payment ผิดพลาดไม่สามารถดำเนินการได้ ID : '.$data->member_user.' จำนวนเงิน '.$message['amount'].' รายการแจ้งถอนที่ '.$data->code));

        }

//        if($message['status'] === 'FAIL'){
//            $member =  app('Gametech\Member\Repositories\MemberRepository')->findOneWhere(['code' => $data->member_code]);
//            $credit_balance = ($member->balance + $message['amount']);
//
//            app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
//                'ip' => $ip,
//                'credit_type' => 'D',
//                'balance_before' => $member->balance,
//                'balance_after' => $credit_balance,
//                'credit' => 0,
//                'total' => $message['amount'],
//                'credit_bonus' => 0,
//                'credit_total' => 0,
//                'credit_before' => $member->balance,
//                'credit_after' => $credit_balance,
//                'pro_code' => 0,
//                'bank_code' => 0,
//                'auto' => 'N',
//                'enable' => 'Y',
//                'user_create' => "System Auto",
//                'user_update' => "System Auto",
//                'refer_code' => 0,
//                'refer_table' => 'members',
//                'remark' => 'คืนเครดิตลุกค้า อ้างอิง รายการอน EzPay  ' . $message['refId']. ' / รายการที่ : ' . $data->code.' / '.$message['msg'],
//                'kind' => 'SETWALLET',
//                'amount' => $message['amount'],
//                'amount_balance' => 0,
//                'withdraw_limit' => 0,
//                'withdraw_limit_amount' => 0,
//                'method' => 'D',
//                'member_code' => $data->member_code,
//                'user_name' => $data->member_user,
//                'emp_code' => 0,
//                'emp_name' => 'SYSTEM'
//            ]);
//
//            $member->balance +=  $message['amount'];
//            $member->save();
//
////            $data->status = 2;
////            $data->save();
//
//
//            broadcast(new RealTimeMessage('EzPay ไม่สามารถโอนเงินให้ลุกค้า ID : '.$data->member_user.' จำนวนเงิน '.$message['amount'].' ได้ ระบบคืนยอดเงินให้ลูกค้าแล้ว'));
//
//        }

        $path = storage_path('logs/ezpay/payout_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- CALLBACK --', true), FILE_APPEND);
        file_put_contents($path, print_r($message, true), FILE_APPEND);

        $param = [
            'status' => 'success',
            'message' => 'Success'
        ];

        return $param;

    }

    public function check_payout()
    {
        $mobile = 'test';
        $clientId = 'GENXPAY';
        $transactionId = '30ebca96-0e5c-4c09-a474-468dd0e8863c';
        $referenceId = 'PO24018A8CAD1E';
        $clientSecret = 'f9e1645cb90af76b5741e3b181123f8a0ab4b23f';


        $hash = hash('sha256', $clientId . $transactionId . $referenceId . $clientSecret);

        $pompay = [
            'clientId' => $clientId,
            'transactionId' => $transactionId,
            'referenceId' => $referenceId,
            'hashVal' => $hash
        ];

        $response = Http::get('https://staging.pompay.asia/v2/check_payout', $pompay);
        $return = $response->json();

        $path = storage_path('logs/pompay/payout_webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- CHECK --', true), FILE_APPEND);
        file_put_contents($path, print_r($return, true), FILE_APPEND);

        return $return;

    }

    public function check_uuid3($uuid, $id)
    {
        $data = app('Gametech\Payment\Repositories\BankPaymentRepository')->findOneWhere(['txid' => $uuid]);
        if (isset($data)) {
            $uuid = 'LUK' . str_pad($id, 7, "0", STR_PAD_LEFT) . 'X' . date('His');
            return $this->check_uuid3($uuid, $id);
        } else {
            return $uuid;
        }
    }
}
