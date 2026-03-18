<?php

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\WildPay;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PaymentOutWildPay implements ShouldQueue
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
        $api = new WildPay;
        $return['complete'] = false;
        $return['success'] = 'NORMAL';
        $return['msg'] = 'อนุมัติรายการเรียบร้อยแล้ว (รายการทั่วไป)';

        $baseurl = 'https://'.config('app.admin_url').'.'.(is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));

        $id = $this->id;
        $balance_start = 0;
        $balance_stop = 0;

        $datenow = now()->toDateTimeString();

        $order = app('Gametech\Payment\Repositories\WithdrawRepository')->find($id);
        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutOne($order->account_code);
        if (! $bank) {
            $return['complete'] = true;

            return $return;
        }
        if ($order->amount < 200) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = 'ยอดเงินถอนออโต้ ขั้นต่ำ 200 บาท';

            return $return;
        }
        if ($order->status_withdraw != 'W') {
            $return['success'] = 'NOTWAIT';
            $return['msg'] = 'รายการนี้ กำลังอยู่ระหว่างประมวลผล';

            return $return;
        }

        $order_id = 'WRD-'.str_pad($order->code, 6, '0', STR_PAD_LEFT).'-'.date('YmdHis');

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

        $amount = (float) ($order['amount'] - $order['fee']);
//                $amount = number_format($amount, 2, '.', '');
//        $amount = number_format($amount, 0);

        $bank_trans = $api->Banks($member->bank_code);
        if ($bank_trans === false) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = 'บัญชีธนาคารของสมาชิก ไม่รองรับการโอน ในขณะนี้';

            return $return;
        }

        $time = time();
        $param = [
            'refId' => trim($order_id),
            'amount' => (float) $amount,
            'userId' => trim($member->user_name),
            'accountName' => trim($member->name),
            'accountNo' => trim($member->acc_no),
            'bankCode' => trim($bank_trans),
            'extendParams' => [
                'username' => trim($member->user_name),
            ],
            'timestamp' => trim(now('UTC')->toIso8601String()),
        ];

        $url = config('wildpay.api_url').'/payment/withdraw/payout';
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
                'detail' => $response['data']['transactionId'],
                //                'url' => $response['data']['qrcode'],
                'qrcode' => '',
                'status' => $response['data']['status'],
                'user_create' => $member->name,
                'user_update' => $member->name,
            ]);

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
                'refer_code' => $id,
                'refer_table' => 'withdraws',
                'remark' => 'รายการแจ้งถอนที่ '.$order->code.' / ไอดีที่ถอน : '.$member->user_name.' จำนวนเงิน '.$amount.' ถูกส่งถอนผ่าน Payment WildPay '.$order_id.' [ '.$response['data']['transactionId'].' ]',
                'kind' => 'OTHER',
                'amount' => $amount,
                'amount_balance' => 0,
                'withdraw_limit' => 0,
                'withdraw_limit_amount' => 0,
                'method' => 'D',
                'member_code' => $member->code,
                'user_name' => $member->user_name,
                'emp_code' => 0,
                'emp_name' => 'SYSTEM',
            ]);


            $order->status = 1;
            $order->status_withdraw = 'A';
            $order->remark_admin = $order->remark_admin.' กำลัง ทำรายการถอนเงินออกจาก WildPay โอนเข้าบัญชี '.$member->name.' เลขที่บัญชี '.$member->acc_no.' ธนาคาร '.$member->bank->shortcode.' จำนวน '.$amount;
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
