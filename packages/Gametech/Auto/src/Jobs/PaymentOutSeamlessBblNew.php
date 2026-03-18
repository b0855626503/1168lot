<?php

namespace Gametech\Auto\Jobs;


use App\Libraries\Bbl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PaymentOutSeamlessBblNew implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public $tries = 1;

    public $maxExceptions = 5;

    public $retryAfter = 130;

    public $id;


    public function __construct($id)
    {
        $this->id = $id;
    }


    public function handle()
    {
        $return['success'] = 'NORMAL';
        $return['msg'] = 'อนุมัติรายการเรียบร้อยแล้ว (รายการทั่วไป)';

        $id = $this->id;
        $balance_start = 0;
        $balance_stop = 0;

        $datenow = now()->toDateTimeString();

        $order = app('Gametech\Payment\Repositories\WithdrawSeamlessRepository')->find($id);
        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutOne($order->account_code);
//        $path = storage_path('logs/seamless/return'. now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r($return, true));

        if (!$bank) {
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

        $order->status_withdraw = 'A';
        $order->saveQuietly();


        $member = app('Gametech\Member\Repositories\MemberRepository')->find($order->member_code);


        $bbl = new Bbl();

        $chk = $bbl->BankCurl($bank['acc_no'], 'getbalance', 'POST');
        if (isset($chk['status']) && $chk['status'] == true) {

            $balance_start = str_replace(',', '', $chk['data']['balance']);
            if ($balance_start >= 0) {
                $bank->balance = $balance_start;
            }

            $bank->checktime = $datenow;
            $bank->saveQuietly();


            if ($balance_start >= $order['amount']) {

                $bank_trans = $bbl->Banks($member->bank_code);
                $data['ToBank'] = $member->acc_no;
                $data['ToBankCode'] = $bank_trans;
                $data['amount'] = ($order['amount'] - $order['fee']);
                $param = json_encode($data);
                $curl = $bbl->BankCurlTrans($bank['acc_no'], 'transfer', $param, 'POST');

                if (isset($curl['status']) && $curl['status'] == true) {

                    $bank_date = explode(' ', $curl['data']['transfer_date']);

                    $chk2 = $bbl->BankCurl($bank['acc_no'], 'getbalance', 'POST');

                    $order->status = 1;
                    $order->status_withdraw = 'C';
                    $order->date_bank = $bank_date[0];
                    $order->time_bank = $bank_date[1];
                    $order->remark_admin = $order->remark_admin . ' ทำรายการถอนเงินออกจากบัญชีอัตโนมัติ โอนเข้าบัญชี ' . $member->name . ' เลขที่บัญชี ' . $member->acc_no . ' ธนาคาร ' . $member->bank->shortcode . ' จำนวน ' . ($order->amount - $order->fee);
                    $order->saveQuietly();

                    if (isset($chk2['status']) && $chk2['status'] == true) {
                        $balance_stop = str_replace(',', '', $chk2['data']['balance']);
                        if ($balance_stop >= 0) {
                            $bank->balance = $balance_stop;
                        }

                        $bank->checktime = $datenow;
                        $bank->saveQuietly();


                        $balance_after = $balance_start - $data['amount'];


                        if ($balance_start > $balance_stop) {


                            $return['success'] = 'COMPLETE';
                            $return['msg'] = 'โอนเงินอัตโนมัติ สำเร็จแล้ว ยอดเงินก่อนทำรายการ ' . $balance_start . ' บาท คงเหลือ ' . $balance_stop . ' บาท';

                        } else {

                            $return['success'] = 'MONEY';
                            $return['msg'] = 'Api ถอนออโต้ แจ้งว่าดำเนินการโอนสำเร็จแล้ว แต่พบว่า ยอดเงินไม่ลดลง โปรดตรวจสอบยอดเงิน ถ้ายอดไม่ลด ให้โอนให้ลูกค้าแมนนวล  (รายการนี้จะไม่ดำเนินการซ้ำ)';
                        }
                        return $return;
                    } else {

                        $return['success'] = 'MONEY';
                        $return['msg'] = 'Api ถอนออโต้ แจ้งว่าดำเนินการโอนสำเร็จแล้ว แต่เช็คยอดหลังถอนไม่ได้ โปรดตรวจสอบยอดเงิน และทำการโอนให้ลูกค้าแมนนวล ถ้ายอดไม่โอน (รายการนี้จะไม่ดำเนินการซ้ำ)';
                    }
                    return $return;

                } else {
                    $return['success'] = 'FAIL_AUTO';
                    $return['msg'] = $curl['msg'];
                }
                return $return;

            } else {
                $return['success'] = 'NOMONEY';
                $return['msg'] = 'ยอดเงินใน บัญชีถอน ไม่เพียงพอ';
            }
            return $return;
        } else {
            $return['success'] = 'NOMONEY';
            $return['msg'] = 'API ไม่สามารถ ตรวจสอบยอดเงิน อีก 5 นาทีโปรดลองใหม่ ถ้ายังไม่ได้อีก โปรดแจ้งทีมงาน';
        }
        return $return;
    }
}
