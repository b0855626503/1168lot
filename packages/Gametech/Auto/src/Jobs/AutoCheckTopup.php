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


class AutoCheckTopup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $bankPaymentRepository;

    protected $memberRepository;

    protected $configRepository;

    protected $paymentPromotionRepository;

    protected $allLogRepository;


    protected $id;



    public function __construct
    (
        $id,
        BankPaymentRepository $bankPayment,
        MemberRepository $memberRepo,
        ConfigRepository $configRepo,
        PaymentPromotionRepository $paymentPromotionRepo,
        AllLogRepository $allLogRepo
    )
    {
        $this->bankPaymentRepository = $bankPayment;

        $this->memberRepository = $memberRepo;

        $this->configRepository = $configRepo;

        $this->paymentPromotionRepository = $paymentPromotionRepo;

        $this->allLogRepository = $allLogRepo;

        $this->id = $id;
    }


    public function handle()
    {

        $payments = $this->bankPaymentRepository->checkPayment(10,$this->id);
        foreach($payments as $i => $payment){
            $members = $this->memberRepository->loadAccount($this->id,$payment);
            $cnt = $members->count();
            if($cnt == 0){
                $param = [
                    "autocheck" => 'Y',
                    "user_create" => 'ไม่พบหมายเลขบัญชี'
                ];
                $this->bankPaymentRepository->update($param,$payment->code);
                continue;
            }elseif($cnt > 1){
                $param = [
                    "autocheck" => 'Y',
                    "user_create" => 'พบหมายเลขบัญชี ' . $cnt . ' บัญชี',
                ];
                $this->bankPaymentRepository->update($param,$payment->code);
                continue;
            }

            $member = $members->first();
            $param = [
                "member_topup" => $member->code,
                "autocheck" => 'W',
                "user_create" => '',
            ];
            $this->bankPaymentRepository->update($param,$payment->code);

        }

    }
}
