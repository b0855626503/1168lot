<?php

declare(strict_types=1);

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\WealthPay;
use Gametech\Payment\Models\BankAccountProxy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * WealthPay Balance Job
 *
 * POST /balance → { balance, freeze_balance, unsettle_balance }
 * Dispatch จาก deposit() และ deposit_callback()
 */
class UpdateBalanceWealthPay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 0;
    public $maxExceptions = 5;
    public $retryAfter = 0;

    public function __construct() {}

    public function handle()
    {
        $api = new WealthPay;
        $resp = $api->request('/balance');

        if (! data_get($resp, 'success')) {
            $this->safeLog('warning', '[WEALTHPAY] get balance failed', ['resp' => $resp]);

            return 0;
        }

        $data = (array) data_get($resp, 'data.data', []);
        $balance = (float) data_get($data, 'balance', 0);
        $freezeBalance = (float) data_get($data, 'freeze_balance', 0);
        $unsettleBalance = (float) data_get($data, 'unsettle_balance', 0);

        $remark = 'balance '.$balance.' / freeze '.$freezeBalance.' / unsettle '.$unsettleBalance;
        $bankCode = (int) config('wealthpay.system_bank_code', 317);

        BankAccountProxy::where('banks', $bankCode)->update([
            'balance' => $balance,
            'api_refresh' => $remark,
        ]);

        $this->safeLog('info', '[WEALTHPAY] balance updated', [
            'banks' => $bankCode,
            'balance' => $balance,
            'api_refresh' => $remark,
        ]);

        return 0;
    }

    private function safeLog(string $level, string $message, array $context = []): void
    {
        $channel = (string) config('wealthpay.log_channel', 'wealthpay_api');

        try {
            Log::channel($channel)->{$level}($message, $context);
        } catch (\Throwable $e) {
            Log::{$level}($message, $context);
        }
    }
}
