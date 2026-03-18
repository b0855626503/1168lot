<?php

namespace Gametech\Auto\Jobs;

use App\Libraries\KbankOut;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PaymentOutKbankNew implements ShouldQueue
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
        $return['complete'] = false;
        $return['success'] = 'NORMAL';
        $return['msg'] = 'อนุมัติรายการเรียบร้อยแล้ว (รายการทั่วไป)';

        $id = $this->id;
        $balance_start = 0;
        $balance_stop = 0;

        $datenow = now()->toDateTimeString();

        $order = app('Gametech\Payment\Repositories\WithdrawNewRepository')->find($id);
        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutOne($order->account_code);
        if (!$bank) {
            $return['complete'] = true;
            return $return;
        }

        $order->from_bank = $order->bankaccount->banks;
        $order->from_name = $order->bankaccount->acc_name;
        $order->from_account = $order->bankaccount->acc_no;
        $order->save();


        $bbl = new KbankOut();

        $chk = $bbl->BankCurl($bank->acc_no, 'getbalance', 'POST');
        if ($chk['status'] === true) {

            $balance_start = str_replace(',', '', $chk['data']['availableBalance']);
            if ($balance_start >= 0) {
                $bank->balance = $balance_start;
            }


            $amount = $order->amount;

            if ($balance_start >= $amount) {

                $bank_trans = $bbl->Banks($order->to_bank);
                if($bank_trans == '500'){
                    $return['success'] = 'FAIL_AUTO';
                    $return['msg'] = 'บัญชีธนาคารของสมาชิก ไม่รองรับการโอน ในขณะนี้';
                    return $return;
                }
                $data['toAccount'] = $order->to_account;
                $data['toBankCode'] = $bank_trans;
                $data['amount'] = $amount;
                $param = ($data);
                $curl = $bbl->BankCurlTrans($bank->acc_no, 'transfer', $param, 'POST');

                if ($curl['status'] === true) {

                    $bank_date = explode(' ', $curl['data']['date']);
                    $date = explode('/', $bank_date[0]);
                    $newdate = (2500 + $date[2] - 543) . '-' . $date[1] . '-' . $date[0];

                    $balance_stop = str_replace(',', '', $curl['data']['availableBalance']);

                    $order->ref = $curl['data']['transactionReference'];
                    $order->status = 1;
                    $order->status_withdraw = 'C';
                    $order->date_bank = $newdate;
                    $order->time_bank = $bank_date[1];
                    $order->remark_admin = $order->remark_admin . ' ทำรายการถอนเงินออกจากบัญชีอัตโนมัติ โอนเข้าบัญชี ' . $order->to_name . ' เลขที่บัญชี ' . $order->to_account . ' ธนาคาร ' . $order->bank->shortcode . ' จำนวน ' . $order->amount;
                    $order->save();


                    $bank->balance = $balance_stop;
                    $bank->checktime = $datenow;
                    $bank->save();

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
