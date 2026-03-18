<?php

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\WildPay;
use Gametech\Payment\Models\BankAccountProxy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateBalanceWildPay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;

    public $tries = 0;

    public $maxExceptions = 5;

    public $retryAfter = 0;

    public function __construct() {}

    public function handle()
    {
        $api = new WildPay;
        $param = [];

        $url = config('wildpay.api_url').'/payment/balance';
        $response = $api->create_balance($url, $param);

        if ($response['success'] === true) {

            $balance = $response['data']['available'] ?? 0;
            $remark = 'ยอดรอถอน (ลูกค้า) '.$response['data']['pendingPayout'].' / ยอดรอถอน (ทีมงาน) '.$response['data']['pendingSettlement'];
            BankAccountProxy::where('banks', 300)
                ->update(['balance' => $balance, 'api_refresh' => $remark]);
        }

        return 0;
    }
}
