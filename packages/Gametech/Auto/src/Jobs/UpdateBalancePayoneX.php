<?php

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\PayoneX;
use Gametech\Payment\Models\BankAccountProxy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateBalancePayoneX implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;

    public $tries = 0;

    public $maxExceptions = 5;

    public $retryAfter = 0;

    public function __construct() {}

    public function handle()
    {
        $api = new PayoneX;
        $token = $this->auth();
        $url = config('payonex.api_url').'/profile/balance';
        $response = $api->create_balance($url, $token);

        if ($response['success'] === true) {

            $balance = $response['data']['balance'] ?? 0;
            $remark = 'ยอด settle Balance '. ($response['data']['settleBalance'] ?? 0);
            BankAccountProxy::where('banks', 307)
                ->update(['balance' => $balance, 'api_refresh' => $remark]);
        }

        return 0;
    }

    public function auth()
    {
        $api = new PayoneX;
        $bank_account = BankAccountProxy::where('banks', 307)
            ->where('bank_type', 1)
            ->where('enable', 'Y')
            ->where('status_auto', 'Y')->first();

        if ($bank_account) {
//            if (!$bank_account->token) {
            if (!$bank_account->device_id || $bank_account->expired_date < now()->toDateTimeString()) {
                $url = config('payonex.api_url') . '/authenticate';
                $result = $api->auth($url);
                if ($result['success'] === true) {
                    $bank_account->device_id = $result['token'];
                    $bank_account->expired_date = date('Y-m-d H:i:s', strtotime("+24 hours"));
                    $bank_account->save();
                    return $result['token'];
                }

            } else {
                return $bank_account->device_id;
            }
//            } else {
//                $this->token = $bank_account->token;
//
//            }
        }
    }
}
