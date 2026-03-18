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


class TopupAllPayments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $limit;

    public function __construct($limit)
    {
        $this->limit = $limit;

    }


    public function handle()
    {

        $this->allLogRepository = app('Gametech\Core\Repositories\AllLogRepository');
        $this->bankPaymentRepository = app('Gametech\Payment\Repositories\BankPaymentRepository');
        $this->memberRepository = app('Gametech\Member\Repositories\MemberRepository');
        $this->paymentPromotionRepository = app('Gametech\Payment\Repositories\PaymentPromotionRepository');

        $payments = $this->bankPaymentRepository->loadPayment($this->limit);

        foreach($payments as $item){

            $payment = $this->bankPaymentRepository->findOneWhere(['code' => $item->code, 'status' => 0, 'autocheck' => 'W']);
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
}
