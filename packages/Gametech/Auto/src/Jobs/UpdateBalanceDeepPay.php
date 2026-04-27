<?php

declare(strict_types=1);

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\DeepPay;
use Gametech\Payment\Models\BankAccountProxy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateBalanceDeepPay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 0;
    public $maxExceptions = 5;
    public $retryAfter = 0;

    public function __construct() {}

    public function handle()
    {
        $api = new DeepPay();
        $resp = $api->balance((string) config('deeppay.currency', 'THB'));

        if (!data_get($resp, 'success')) {
            $this->safeLog('warning', '[DEEPPAY] get balance failed', ['resp' => $resp]);
            return 0;
        }

        $data = (array) data_get($resp, 'data.data', []);
        $balance = (float) data_get($data, 'balance', 0);
        $balanceDeposit = (float) data_get($data, 'balance_deposit', 0);
        $balanceWithdraw = (float) data_get($data, 'balance_withdraw', 0);
        $minTransfer = (float) data_get($data, 'min_transfer', 0);

        $remark = 'balance_deposit ' . $balanceDeposit . ' / balance_withdraw ' . $balanceWithdraw . ' / min_transfer ' . $minTransfer;
        $bankCode = (int) config('deeppay.system_bank_code', 313);

        BankAccountProxy::where('banks', $bankCode)->update([
            'balance' => $balance,
            'api_refresh' => $remark,
        ]);

        $this->safeLog('info', '[DEEPPAY] balance updated', [
            'banks' => $bankCode,
            'balance' => $balance,
            'api_refresh' => $remark,
        ]);

        return 0;
    }

    private function safeLog(string $level, string $message, array $context = []): void
    {
        $channel = (string) config('deeppay.log_channel', 'deeppay_balance');

        try {
            Log::channel($channel)->{$level}($message, $context);
        } catch (\Throwable $e) {
            Log::{$level}($message, $context);
        }
    }
}
