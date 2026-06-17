<?php

declare(strict_types=1);

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\FlashPay;
use Gametech\Payment\Models\BankAccountProxy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * FlashPay Balance Job
 *
 * GET /api/v1/balance → { balance: 1234.56, currency: "THB" }
 * Dispatch จาก deposit() และ deposit_callback()
 */
class UpdateBalanceFlashPay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 0;
    public $maxExceptions = 5;
    public $retryAfter = 0;

    public function __construct() {}

    public function handle()
    {
        $api = new FlashPay;
        $resp = $api->request('/balance', [], [], 'GET');

        if (data_get($resp, 'success') === true) {
            $balance = (float) data_get($resp, 'data.balance', 0);

            BankAccountProxy::where('banks', (int) config('flashpay.system_bank_code', 318))
                ->update(['balance' => $balance]);
        }

        return 0;
    }
}
