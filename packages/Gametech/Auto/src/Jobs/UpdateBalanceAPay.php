<?php

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\APay;
use Gametech\Payment\Models\BankAccountProxy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateBalanceAPay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;

    public $tries = 0;

    public $maxExceptions = 5;

    public $retryAfter = 0;

    public function __construct() {}

    public function handle()
    {
        $api = new APay;
        $token = $api->auth();
        $param = [
            'auth' => $token,
            'username' => config('apay.username'),
            'currency' => 'THB'
        ];

        $url = config('apay.api_url').'/balance';
        $response = $api->create_balance($url, $param);

        if ($response['success'] === true) {

            $balance = $response['data']['balance'] ?? 0;
            $remark = 'ยอด Deposit Balance '. ($response['data']['balance_deposit'] ?? 0) .' / ยอด Withdraw Balance '.($response['data']['balance_withdraw'] ?? 0).' / ยอด Pending Balance '. ($response['data']['pending'] ?? 0);
            BankAccountProxy::where('banks', 308)
                ->update(['balance' => $balance, 'api_refresh' => $remark]);
        }

        return 0;
    }
}
