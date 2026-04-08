<?php

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\XEPay;
use Gametech\Payment\Models\BankAccountProxy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateBalanceXEPay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 0;
    public $maxExceptions = 5;
    public $retryAfter = 0;

    public function __construct() {}

    public function handle()
    {
        $api = new XEPay();
        $resp = $api->getBalance();

        if (!data_get($resp, 'success')) {
            Log::channel('xepay_balance')->warning('[XEPAY] get balance failed', [
                'resp' => $resp,
            ]);

            return 0;
        }

        $row = (array) data_get($resp, 'data', []);
        $balance = (float) (data_get($row, 'Balance') ?? 0);
        $freeze = (float) (data_get($row, 'Freeze') ?? 0);
        $openBalance = (float) (data_get($row, 'OpenBalance') ?? 0);
        $usdtBalance = (float) (data_get($row, 'USDT_Balance') ?? 0);
        $usdtFreeze = (float) (data_get($row, 'USDT_Freeze') ?? 0);

        $remark = 'freeze ' . $freeze . ' / open_balance ' . $openBalance . ' / usdt_balance ' . $usdtBalance . ' / usdt_freeze ' . $usdtFreeze;
        $bankCode = (int) config('xepay.system_bank_code', 314);

        BankAccountProxy::where('banks', $bankCode)->update([
            'balance' => $balance,
            'api_refresh' => $remark,
        ]);

        Log::channel('xepay_balance')->info('[XEPAY] balance updated', [
            'banks' => $bankCode,
            'balance' => $balance,
            'api_refresh' => $remark,
        ]);

        return 0;
    }
}
