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
use Illuminate\Support\Facades\Log;

/**
 * FlashPay Balance Job
 *
 * GET /api/v1/balance → { object, merchant, balance, currency, updatedAt }
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

        if (! data_get($resp, 'success')) {
            $this->safeLog('warning', '[FLASHPAY] get balance failed', ['resp' => $resp]);

            return 0;
        }

        $data = (array) data_get($resp, 'data', []);
        $balance = (float) data_get($data, 'balance', 0);
        $currency = (string) data_get($data, 'currency', 'THB');
        $updatedAt = (string) data_get($data, 'updatedAt', '');

        $remark = 'balance '.$balance.' '.$currency.' / updated '.$updatedAt;
        $bankCode = (int) config('flashpay.system_bank_code', 318);

        BankAccountProxy::where('banks', $bankCode)->update([
            'balance' => $balance,
            'api_refresh' => $remark,
        ]);

        $this->safeLog('info', '[FLASHPAY] balance updated', [
            'banks' => $bankCode,
            'balance' => $balance,
            'api_refresh' => $remark,
        ]);

        return 0;
    }

    private function safeLog(string $level, string $message, array $context = []): void
    {
        $channel = (string) config('flashpay.log_channel', 'flashpay_api');

        try {
            Log::channel($channel)->{$level}($message, $context);
        } catch (\Throwable $e) {
            Log::{$level}($message, $context);
        }
    }
}
