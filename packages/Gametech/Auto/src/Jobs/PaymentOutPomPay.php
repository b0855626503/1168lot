<?php

namespace Gametech\Auto\Jobs;

use App\Libraries\KbankOut;
use App\Libraries\PomPayOut;
use App\Libraries\ScbOut;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class PaymentOutPomPay implements ShouldQueue
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
        $bbl = new PomPayOut();
        $return['complete'] = false;
        $return['success'] = 'NORMAL';
        $return['msg'] = 'อนุมัติรายการเรียบร้อยแล้ว (รายการทั่วไป)';

        if (config('app.user_url') === '') {
            $baseurl = 'https://' . (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        } else {
            $baseurl = 'https://' . config('app.user_url') . '.' . (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        }


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
//        if($order->emp_approve > 0){
//            $return['success'] = 'NOTWAIT';
//            $return['msg'] = 'รายการนี้ มีทีมงานกดดำเนินการแล้ว';
//            return $return;
//        }
        if ($order->status_withdraw != 'W') {
            $return['success'] = 'NOTWAIT';
            $return['msg'] = 'รายการนี้ กำลังอยู่ระหว่างประมวลผล';
            return $return;
        }

        $uuid = Str::uuid()->__toString();
        $transactionId = $bbl->check_uuid($uuid);
        if (!$order->txid) {
            $order->txid = $transactionId;
        }
        $order->status_withdraw = 'A';
        $order->save();

        $transactionId = $order->txid;

        $member = app('Gametech\Member\Repositories\MemberRepository')->find($order->member_code);


        $amount = ($order['amount'] - $order['fee']);
        $bank_trans = $bbl->Banks($member->bank_code);
        if ($bank_trans == '500') {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = 'บัญชีธนาคารของสมาชิก ไม่รองรับการโอน ในขณะนี้';
            return $return;
        }

        $clientId = config('app.pompay_clientId');
        $custName = $member->user_name;
        $custBank = $bank_trans;
        $custBankAcc = $member->acc_no;
        $custMobile = $member->tel;
        $custEmail = $member->user_name;

        $callbackUrl = $baseurl . '/pompay/payout_callback';
        $clientSecret = config('app.pompay_clientSecret');
        $hash = hash('sha256', $clientId . $transactionId . $custName . $custBank . $custBankAcc . $custMobile . $custEmail . $amount . $callbackUrl . $clientSecret);


        $pompay = [
            'clientId' => $clientId,
            'transactionId' => $transactionId,
            'custName' => $custName,
            'custBank' => $custBank,
            'custBankAcc' => $custBankAcc,
            'custMobile' => $custMobile,
            'custEmail' => $custEmail,
            'amount' => $amount,
            'callbackUrl' => $callbackUrl,
            'hashVal' => $hash,
        ];

        $chk = $bbl->BankCurl($pompay, 'POST');
        if (isset($chk['status']) == 'success') {

            $order->status = 1;
            $order->status_withdraw = 'C';
            $order->remark_admin = $order->remark_admin . ' ทำรายการถอนเงินออกจาก PomPay โอนเข้าบัญชี ' . $member->name . ' เลขที่บัญชี ' . $member->acc_no . ' ธนาคาร ' . $member->bank->shortcode . ' จำนวน ' . $amount;
            $order->save();
            $return['complete'] = true;
            $return['success'] = 'COMPLETE';
            $return['msg'] = $chk['message'];

        } else {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = $chk['message'];
        }
        return $return;
    }
}
