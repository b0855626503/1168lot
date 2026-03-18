<?php

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\KingPay;
use Gametech\Payment\Models\BankAccountProxy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateBalanceKingPay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;

    public $tries = 0;

    public $maxExceptions = 5;

    public $retryAfter = 0;

    public function __construct() {}

    public function handle()
    {
        $api = new KingPay;
        $param = [
            'merchantId' => config('kingpay.merchant_no')
        ];

        $url = config('kingpay.api_url').'/api/v1/merchant/balance';
        $response = $api->create_balance($url, $param);

        if ($response['success'] === true) {

            $balance = $response['data']['balance'] ?? 0;
            $remark = 'ยอด operate Balance '. ($response['data']['operateBalance'] ?? 0) .' / ยอด freeze Balance '.($response['data']['freezeBalance'] ?? 0).' / ยอด parking Balance '. ($response['data']['parkingBalance'] ?? 0);
            BankAccountProxy::where('banks', 304)
                ->update(['balance' => $balance, 'api_refresh' => $remark]);
        }

        return 0;
    }
}
