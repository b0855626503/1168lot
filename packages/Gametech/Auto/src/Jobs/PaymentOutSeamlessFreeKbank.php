<?php

namespace Gametech\Auto\Jobs;

use App\Libraries\KbankOut;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PaymentOutSeamlessFreeKbank implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public $tries = 0;

    public $maxExceptions = 5;

    public $retryAfter = 130;

    public $id;


    public function __construct($id)
    {
        $this->id = $id;
    }


    public function handle()
    {
        $return['complete'] = false;
        $return['success'] = 'NORMAL';
        $return['msg'] = 'อนุมัติรายการเรียบร้อยแล้ว (รายการทั่วไป)';

        $id = $this->id;
        $balance_start = 0;
        $balance_stop = 0;

        $datenow = now()->toDateTimeString();

        $order = app('Gametech\Payment\Repositories\WithdrawSeamlessFreeRepository')->find($id);
        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutOne($order->account_code);
        if (!$bank) {
            $return['complete'] = true;
            return $return;
        }


        if ($order->status_withdraw != 'W') {
            $return['success'] = 'NOTWAIT';
            $return['msg'] = 'รายการนี้ กำลังอยู่ระหว่างประมวลผล';
            return $return;
        }

        $order->status_withdraw = 'A';
        $order->save();


        $member = app('Gametech\Member\Repositories\MemberRepository')->find($order->member_code);


        $bbl = new KbankOut();

        $chk = $bbl->BankCurl($bank['acc_no'], 'getbalance', 'POST');
        if ($chk['status'] === true) {

            $balance_start = str_replace(',', '', $chk['data']['availableBalance']);
            if ($balance_start >= 0) {
                $bank->balance = $balance_start;
            }

            $bank->checktime = $datenow;
            $bank->save();


            $amount = ($order['amount'] - $order['fee']);

            if ($balance_start >= $amount) {

                $bank_trans = $bbl->Banks($member->bank_code);
                $data['toAccount'] = $member->acc_no;
                $data['toBankCode'] = $bank_trans;
                $data['amount'] = $amount;
                $param = ($data);
                $curl = $bbl->BankCurlTrans($bank['acc_no'], 'transfer', $param, 'POST');

                if ($curl['status'] === true) {

                    $bank_date = explode(' ', $curl['data']['date']);
                    $date = explode('/', $bank_date[0]);
                    $newdate = (2000 + $date[2] - 543) . '-' . $date[1] . '-' . $date[0];

                    $balance_stop = str_replace(',', '', $curl['data']['availableBalance']);

                    $order->status = 1;
                    $order->status_withdraw = 'C';
                    $order->date_bank = $newdate;
                    $order->time_bank = $bank_date[1];
                    $order->remark_admin = $order->remark_admin . ' ทำรายการถอนเงินออกจากบัญชีอัตโนมัติ โอนเข้าบัญชี ' . $member->name . ' เลขที่บัญชี ' . $member->acc_no . ' ธนาคาร ' . $member->bank->shortcode . ' จำนวน ' . ($order->amount - $order->fee);
                    $order->save();

                    $return['complete'] = true;
                    $return['success'] = 'COMPLETE';
                    $return['msg'] = 'โอนเงินอัตโนมัติ สำเร็จแล้ว ยอดเงินก่อนทำรายการ ' . $balance_start . ' บาท คงเหลือ ' . $balance_stop . ' บาท';


                    return $return;

                } else {

                    $return['success'] = 'FAIL_AUTO';
                    $return['msg'] = $curl['msg'];
                    return $return;
                }

            } else {

                $return['success'] = 'NOMONEY';
                $return['msg'] = 'ยอดเงินใน บัญชีถอน ไม่เพียงพอ';
                return $return;
            }

        } else {

            $return['success'] = 'NOMONEY';
            $return['msg'] = 'API ไม่สามารถ ตรวจสอบยอดเงิน อีก 5 นาทีโปรดลองใหม่ ถ้ายังไม่ได้อีก โปรดแจ้งทีมงาน';
            return $return;

        }

    }
}
