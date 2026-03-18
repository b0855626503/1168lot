<?php

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\SmkPay;
use Gametech\Payment\Models\BankAccountProxy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateBalanceSmkPay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;

    public $tries = 0;

    public $maxExceptions = 5;

    public $retryAfter = 0;

    public function __construct() {}

    public function handle()
    {
        $api = new SmkPay();

        // ค่าเริ่มต้น: ใช้ THB เป็นหลัก (เหมือนตัวอย่าง response)
        $currencyCodes = ['THB'];

        $resp = $api->request('GET', '/v1/me/balances', null, [
            'currency_code' => $currencyCodes,
        ]);

        if (! data_get($resp, 'success')) {
            $this->safeLog('warning', '[SMKPAY] get balances failed', [
                'resp' => $resp,
            ]);

            return 0;
        }

        $rows = (array) data_get($resp, 'data.data', []);
        if (empty($rows)) {
            $this->safeLog('warning', '[SMKPAY] get balances empty', [
                'resp' => $resp,
            ]);

            return 0;
        }

        // เลือก balance ของ THB ก่อน ถ้าไม่มี ก็หยิบตัวแรก
        $row = null;
        foreach ($rows as $r) {
            if ((string) data_get($r, 'currency_code') === 'THB') {
                $row = $r;
                break;
            }
        }
        if ($row === null) {
            $row = $rows[0];
        }

        $total = (float) (data_get($row, 'total_balance') ?? 0);
        $available = (float) (data_get($row, 'available_balance') ?? 0);
        $frozen = (float) (data_get($row, 'frozen_balance') ?? 0);
        $reserved = (float) (data_get($row, 'reserved_balance') ?? 0);

        // ✅ ตามที่สั่ง: total_balance -> balance
        $balance = $total;

        // ✅ ตามที่สั่ง: api_refresh เอา available/frozen/reserved มาเรียง
        $remark = 'available_balance ' . $available . ' / frozen_balance ' . $frozen . ' / reserved_balance ' . $reserved;

        // ✅ ตามที่สั่ง: ไม่ต้องแยก bank_type
        $bankCode = (int) config('smkpay.system_bank_code', 313);

        BankAccountProxy::where('banks', $bankCode)
            ->update([
                'balance' => $balance,
                'api_refresh' => $remark,
            ]);

        $this->safeLog('info', '[SMKPAY] balance updated', [
            'banks' => $bankCode,
            'balance' => $balance,
            'api_refresh' => $remark,
        ]);

        return 0;
    }

    private function safeLog(string $level, string $message, array $context = []): void
    {
        $channel = (string) config('smkpay.log_channel', 'smkpay_balance');

        try {
            Log::channel($channel)->{$level}($message, $context);
        } catch (\Throwable $e) {
            Log::{$level}($message, $context);
        }
    }
}
