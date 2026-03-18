<?php

namespace Gametech\Auto\Jobs;

use Gametech\Core\Repositories\AllLogRepository;
use Gametech\Core\Repositories\ConfigRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\PaymentPromotionRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class TopupAdminPayments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $item;

    public function __construct($item)
    {
        $this->item = $item;

    }


    public function handle()
    {
        $code = $this->item;
        $this->allLogRepository = app('Gametech\Core\Repositories\AllLogRepository');
        $this->bankPaymentRepository = app('Gametech\Payment\Repositories\BankPaymentRepository');
        $this->memberRepository = app('Gametech\Member\Repositories\MemberRepository');
        $this->paymentPromotionRepository = app('Gametech\Payment\Repositories\PaymentPromotionRepository');

        $payment = $this->bankPaymentRepository->findOneWhere(['code' => $code, 'status' => 0, 'autocheck' => 'W' , ['emp_code','<>',0]]);
        if ($payment->doesntExist()) {
            return false;
        }

        $alllog = $this->allLogRepository->where('bank_payment_id', $payment->code);
        if($alllog->exists()){
            $payment->autocheck = 'Y';
            $payment->status = 1;
            $payment->remark_admin = 'ตรวจสอบ All Log พบว่า มีรายการเติมไปแล้ว';
            $payment->topup_by = 'System Auto';
            return false;
        }

        $this->paymentPromotionRepository->checkFastStart($payment->amount,$payment->member_topup,$payment->code);
        return $this->bankPaymentRepository->refillPayment($payment);

    }
}
