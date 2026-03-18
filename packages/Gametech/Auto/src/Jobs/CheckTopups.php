<?php

namespace Gametech\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class CheckTopups implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bank;

    protected $limit;

    public function __construct($bank,$limit)
    {
        $this->bank = $bank;
        $this->limit = $limit;

    }


    public function handle()
    {
        $bank = $this->bank;
        $limit = $this->limit;

        $this->bankPaymentRepository = app('Gametech\Payment\Repositories\BankPaymentRepository');
        $this->memberRepository = app('Gametech\Member\Repositories\MemberRepository');
        $payments = $this->bankPaymentRepository->checkPayment($limit,$bank);

        foreach($payments as $i => $payment){

            $members = $this->memberRepository->loadAccount($bank,$payment);
            $cnt = $members->count();
            if($cnt == 0){

                $payment->autocheck = 'Y';
                $payment->status = 1;
                $payment->remark_admin = 'ตรวจสอบ All Log พบว่า มีรายการเติมไปแล้ว';
                $payment->topup_by = 'System Auto';
                $payment->save();
                return false;
            }elseif($cnt > 1){
                $payment->autocheck = 'Y';
                $payment->user_create = 'พบหมายเลขบัญชี ' . $cnt . ' บัญชี';
                $payment->topup_by = 'System Auto';
                $payment->save();
                return false;

            }

            $member = $members->first();

            $payment->member_topup = $member->code;
            $payment->autocheck = 'W';
            $payment->remark_admin = 'รอระบบเติมอัตโนมัติ';
            $payment->topup_by = 'System Auto';
            $payment->save();
            return true;
        }

    }
}
