<?php

namespace  Gametech\Auto\Listeners;


use Gametech\Auto\Events\PaymentOut;
use Gametech\Auto\Jobs\PaymentOutBbl;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class PaymentOutListen implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'payment';


    public function __construct()
    {

    }

    public function handle(PaymentOut $event)
    {

        $data = $event->items;

        $bankitem = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutOne($data['account_code']);

        $bankid = $bankitem->bank['shortcode'];

        switch ($bankid) {
            case 'TW':
//                PaymentTrue::dispatch($account)->onQueue('payment');
                break;
            case 'KBANK':
//                PaymentKbank::dispatch($account)->onQueue('payment');
                break;
            case 'BBL':
                PaymentOutBbl::dispatch($data,$data['id'])->onQueue('payment');
                break;
            case 'KTB':
//                PaymentKtb::dispatch($account)->onQueue('payment');
                break;
            case 'SCB':
//                PaymentScb::dispatch($account)->onQueue('payment');
                break;
        }

        return false;

    }

}
