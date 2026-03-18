<?php

namespace Gametech\Auto\Jobs;


use Gametech\Payment\Models\BankPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;



class TopupFastStart implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;

    public $tries = 0;

    public $maxExceptions = 3;

    protected $bankpayment;

    protected $alllog;

    protected $item;

    public function __construct($item)
    {
        $this->item = $item;

    }


    public function handle()
    {

        $payment = BankPayment::findOrFail($this->item);

        if (empty($payment)) {
            return false;
        }


        return app('Gametech\Payment\Repositories\PaymentPromotionRepository')->checkFastStart($payment->amount, $payment->member_topup, $payment->code);

    }
}
