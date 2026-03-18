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
use Illuminate\Support\Facades\Http;


class GetPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $bankPaymentRepository;

    protected $memberRepository;

    protected $configRepository;

    protected $paymentPromotionRepository;

    protected $allLogRepository;


    public function __construct
    (
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
    }


    public function handle()
    {
        $date = now()->format('Y_m_d');

        $start = now()->subDays(1)->toDateString();
        $end = $date;

        $mobile_number = $this->acc_no;
        $date = $end;

        $file = 'https://sv2.168csn.com/tw/Transaction_'.$mobile_number.'_'.$date.'.json';

        $response = Http::get($file);

        if($response->failed()){

        }




//        $config = $this->configRepository->first();

        $payments = $this->bankPaymentRepository->loadPayment(10);
        foreach($payments as $i => $payment){
            $alllog = $this->allLogRepository->where('bank_payment_id',$payment['code']);
            if($alllog->count() == 0){
                $rechk = $this->bankPaymentRepository->findOneWhere(['code' => $payment['code'] , 'status' => 0 , 'autocheck' => 'W']);
                if($rechk->doesntExist()){
                    continue;
                }
            }

            $result = $this->bankPaymentRepository->refillPayment($payment);
            if(!$result){
                continue;
            }

        }

    }
}
