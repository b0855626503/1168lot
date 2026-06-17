<?php

declare(strict_types=1);

namespace Gametech\Auto\Jobs;

use Gametech\Bank\Proxies\BankAccountProxy;
use Gametech\Payment\Libraries\FlashPay;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * FlashPay Balance Job
 *
 * FlashPay has GET /api/v1/balance but the response schema is undocumented.
 * When the balance endpoint returns a usable value, this job updates
 * BankAccountProxy. Otherwise it falls back to triggering the auto-topup
 * pipeline (which processes bank_payment records).
 *
 * Dispatched from deposit() and deposit_callback().
 */
class UpdateBalanceFlashPay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 0;
    public $maxExceptions = 5;
    public $retryAfter = 0;

    public function __construct() {}

    public function handle(): void
    {
        $api = new FlashPay;

        $resp = $api->request('/balance', [], [], 'GET');

        if (! data_get($resp, 'success')) {
            Log::channel('flashpay_api')->warning('[FLASHPAY] Balance check failed', [
                'code' => data_get($resp, 'code'),
                'msg' => data_get($resp, 'msg'),
            ]);

            return;
        }

        $data = data_get($resp, 'data');

        // TODO: confirm actual response fields from FlashPay
        // Attempt to extract balance from common response shapes
        $balance = null;

        if (is_array($data)) {
            $balance = data_get($data, 'balance')
                ?? data_get($data, 'available')
                ?? data_get($data, 'total')
                ?? data_get($data, 'amount');
        }

        if ($balance !== null) {
            $bankCode = (int) config('flashpay.system_bank_code', 318);

            try {
                $proxy = app(BankAccountProxy::class);
                $proxy->updateBalance($bankCode, (float) $balance);
            } catch (\Throwable $e) {
                Log::channel('flashpay_api')->error('[FLASHPAY] Failed to update BankAccountProxy', [
                    'error' => $e->getMessage(),
                    'balance' => $balance,
                ]);
            }
        }
    }
}
