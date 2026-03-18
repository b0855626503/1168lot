<?php

namespace Gametech\Admin\Http\Controllers;


use App\Events\RealTimeMessage;
use App\Libraries\Coingecko;
use Gametech\Payment\Models\BankPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


class CommSpayController extends AppBaseController
{

    protected $_config;

    public function __construct()
    {
        $this->_config = request('_config');

//        $this->middleware('api');
    }

    public function encrypt_decrypt($action, $string, $apikey = '{your_api_key}', $secretkey = '{your_secret_key}')
    {
        $output = false;
        $encrypt_method = 'AES-256-CBC';
        $secret_key = $apikey;
        $secret_iv = $secretkey;
        // hash
        $key = substr(hash('sha256', $secret_key, true), 0, 32);
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encrypt_method, $key, OPENSSL_RAW_DATA, $iv);
            $output = base64_encode($output);
            $output = urlencode($output);

        } elseif ($action == 'decrypt') {
            $output = openssl_decrypt(base64_decode(urldecode($string)), $encrypt_method, $key, OPENSSL_RAW_DATA, $iv);
        }

        return $output;
    }

    public function callback(Request $request)
    {
        $datenow = now()->toDateTimeString();
        $ip = $request->ip();
        $mobile = 'test';
        $messages = $request->all();
        $message = [];
        $merchant_api_key = config('app.commspay_api_key');
        $merchant_secert_key = config('app.commspay_secret_key');

        $path = storage_path('logs/commspay/webhook_' . $mobile . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- CALLBACK --', true), FILE_APPEND);
        file_put_contents($path, print_r($messages, true), FILE_APPEND);

        $data = $messages['key'];
        $key = $this->encrypt_decrypt('decrypt', $data, $merchant_api_key, $merchant_secert_key);
        parse_str($key, $message);

        file_put_contents($path, print_r($message, true), FILE_APPEND);


        if ($message['transaction_status'] == 2) {



//            $who = app('Gametech\Payment\Repositories\BankHengpayRepository')->findOneWhere(['referenceNo' => $message['data']['reference2']]);
            $member = app('Gametech\Member\Repositories\MemberRepository')->findOneWhere(['user_name' => $message['user_id']]);

            $bank_account = app('Gametech\Payment\Repositories\BankAccountRepository')->findOneWhere(['banks' => 105, 'bank_type' => 1, 'enable' => 'Y', 'status_auto' => 'Y']);

            if($bank_account->rate_auto == 'Y'){
                $api = new Coingecko();
                $rates = $api->convert();
                if($rates['success'] === true){
                    $rate = $rates['rate'];
                    $bank_account->rate = $rate;
                    $bank_account->rate_update = now()->toDateTimeString();
                    $dateupdate = now()->toDateTimeString();
                    $bank_account->save();
                }else{
                    $rate = $bank_account->rate;
                    $dateupdate = $bank_account->rate_update;
                }
            }else{
                $rate = $bank_account->rate;
                $dateupdate = $bank_account->rate_update;
            }



            $bank = app('Gametech\Payment\Repositories\BankRepository')->find($bank_account->banks);

            $detail = 'CommSpay Txno : ' . $message['transaction_no'];

            $amount = (float)$message['transaction_amount'];

            $hash = md5($bank_account->code . $datenow . $amount . $detail);

            $data = [
                'bank' => strtolower($bank->shortcode . '_' . $bank_account->acc_no),
                'detail' => $detail.' จำนวน '.$amount.' USDT , RateTHB '.$rate.' บาท , อัพเดทเมื่อ '.$dateupdate,
                'account_code' => $bank_account->code,
                'autocheck' => 'W',
                'bankstatus' => 1,
                'bank_name' => $bank->shortcode,
                'bank_time' => $datenow,
                'channel' => 'QR',
                'value' => ($amount * $rate),
                'tx_hash' => $hash,
                'txid' => $message['transaction_code'],
                'status' => 0,
                'usdt' => $amount,
                'rate' => $rate,
                'ip_admin' => $ip,
                'member_topup' => $member->code,
                'remark_admin' => '',
                'emp_topup' => 0,
                'user_create' => 'รอระบบเติมอัตโนมัติ ทำรายการฝากเงินโดย CommSpay QR',
                'create_by' => 'SYSAUTO'
            ];

            $chk = app('Gametech\Payment\Repositories\BankPaymentRepository')->findOneWhere(['txid' => $message['transaction_code']]);
            if (!$chk) {
                app('Gametech\Payment\Repositories\BankPaymentRepository')->create($data);
            }

//            $param = [
//                'status' => 200,
//                'qrCodeTransactionId' => $message['qrCodeTransactionId'],
//                'message' => 'Successfully received Payment Notification Callback'
//            ];

//            if($case) {
//                if ($message['status'] != 'SUCCESS') {
//                    broadcast(new RealTimeMessage($message['refId'] . ' โดย ' . $case->username . ' ' . $case->name . ' สถานะ ' . $message['status'] . ' (' . $message['msg'] . ')'));
//                }
//            }

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
        $config = core()->getConfigData();
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
