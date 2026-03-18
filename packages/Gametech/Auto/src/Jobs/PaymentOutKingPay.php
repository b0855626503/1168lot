<?php

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\KingPay;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PaymentOutKingPay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;

    public $tries = 0;

    public $maxExceptions = 5;

    public $retryAfter = 0;

    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function handle()
    {
        $api = new KingPay;
        $return['complete'] = false;
        $return['success'] = 'NORMAL';
        $return['msg'] = 'อนุมัติรายการเรียบร้อยแล้ว (รายการทั่วไป)';

        $baseurl = 'https://' . config('app.admin_url') . '.' . (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));

        $id = $this->id;
        $balance_start = 0;
        $balance_stop = 0;

        $datenow = now()->toDateTimeString();

        $order = app('Gametech\Payment\Repositories\WithdrawRepository')->find($id);
        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutOne($order->account_code);
        if (!$bank) {
            $return['complete'] = true;

            return $return;
        }

        $min = config('kingpay.min_deposit');
        if ($order->amount < $min) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = 'ยอดเงินถอนออโต้ ขั้นต่ำ ' . $min . ' บาท';

            return $return;
        }
        if ($order->status_withdraw != 'W') {
            $return['success'] = 'NOTWAIT';
            $return['msg'] = 'รายการนี้ กำลังอยู่ระหว่างประมวลผล';

            return $return;
        }

        $order_id = 'KWRD-' . str_pad($order->code, 6, '0', STR_PAD_LEFT) . '-' . date('YmdHis');

        //        $subMerId = config('app.ezpay_subid');
        //        $uuid = 'WDR-'.$subMerId.'-'.date('is').str_pad($order->code, 4, '0', STR_PAD_LEFT);
        //        $transactionId = $api->check_uuid2($uuid, $order->code, $subMerId);

        //        if (!$order->txid) {
        //            $order->txid = $transactionId;
        //        }
        $order->txid = $order_id;
//        $order->status_withdraw = 'A';
        $order->save();

        $transactionId = $order->txid;

        $member = app('Gametech\Member\Repositories\MemberRepository')->find($order->member_code);

        $amount = (float)($order['amount'] - $order['fee']);
//        $amount = number_format($amount, 2, '.', '');
//        $amount = number_format($amount, 0);

        $bankName = $api->Banks($member->bank_code);
        if ($bankName === false) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = 'บัญชีธนาคารของสมาชิก ไม่รองรับการโอน ในขณะนี้';

            return $return;
        }

//        $transactionId = 'KING-' . str_pad($member->code, 6, '0', STR_PAD_LEFT) . '-' . date('YmdHis');
        $clientId = config('kingpay.client_id');
        $merchantId = config('kingpay.merchant_no');
        $bankAccountNumber = $member->acc_no;
        $name = $member->name;
        $callbackUrl = route('api.kingpay.withdraw.callback');
        $phone = $member->tel;
        $timestamp = now()->timestamp;
        $signature = $api->JwT($timestamp);


        $time = time();
        $param = [
            'clientId' => trim($clientId),
            'amount' => (float)$amount,
            'merchantId' => trim($merchantId),
            'transactionId' => trim($transactionId),
            'bankAccountNumber' => trim($bankAccountNumber),
            'bankName' => trim($bankName),
            'name' => trim($name),
            'phone' => trim($phone),
            'callbackUrl' => trim($callbackUrl),
            'signature' => trim($signature),
            'timestamp' => $timestamp,
        ];

        $url = config('kingpay.api_url') . '/api/v1/payout/create';
        $response = $api->create_withdraw($url, $param);

        if ($response['success'] === true) {

            app('Gametech\Core\Repositories\CheckCaseRepository')->create([
                'method' => 2,
                'bank_code' => $bank->banks,
                'txid' => $order_id,
                'amount' => $amount,
                'payamount' => $amount,
                'username' => $member->user_name,
                'name' => $member->name,
                'detail' => $response['data']['referenceId'],
                //                'url' => $response['data']['qrcode'],
                'qrcode' => '',
                'status' => $response['data']['status'],
                'user_create' => $member->name,
                'user_update' => $member->name,
            ]);


            $order->status = 1;
            $order->status_withdraw = 'A';
            $order->remark_admin = $order->remark_admin . ' กำลัง ทำรายการถอนเงินออกจาก kingpay โอนเข้าบัญชี ' . $member->name . ' เลขที่บัญชี ' . $member->acc_no . ' ธนาคาร ' . $member->bank->shortcode . ' จำนวน ' . $amount;
            $order->save();
            $return['complete'] = true;
            $return['success'] = 'COMPLETE';
            $return['msg'] = $response['msg'];
        } else {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = $response['msg'];
        }

        return $return;
    }
}
