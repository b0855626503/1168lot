<?php

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\OnPay;
use Gametech\Payment\Models\BankAccountProxy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class UpdateBalanceOnPay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;

    /**
     * จำกัดจำนวน retry เพื่อไม่ให้ job ยิงรัว
     */
    public $tries = 5;

    public $maxExceptions = 5;

    /**
     * หน่วงเวลาก่อน retry (กันถล่ม API)
     */
    public $backoff = [10, 30, 60, 120, 300];

    public function __construct() {}

    public function handle()
    {
        /**
         * ======================================================
         * Throttle: อนุญาตให้รันได้แค่ 1 ครั้งต่อ 5 นาที
         * ======================================================
         */
        $throttleKey = 'onpay:balance:throttle';
        $throttleTtl = 5 * 60; // 5 นาที

        // ถ้า add ไม่ได้ แปลว่าเพิ่งมีคนรันไปแล้วในช่วงนี้
        if (! Cache::add($throttleKey, now()->timestamp, $throttleTtl)) {
            // exit เร็ว ไม่ยิง API
            return 0;
        }

        /**
         * ======================================================
         * Circuit breaker (พัก 5 นาทีเมื่อ fail ติดต่อกัน)
         * ======================================================
         */
        $cbKey   = 'onpay:balance:circuit_open_until';
        $failKey = 'onpay:balance:fail_count';

        $openUntilTs = (int) Cache::get($cbKey, 0);
        $nowTs = now()->timestamp;

        if ($openUntilTs > $nowTs) {
            $until = now()->setTimestamp($openUntilTs)->toDateTimeString();
            $remark = "OnPay API พักชั่วคราว (circuit breaker) ถึง {$until}";
            $this->touchApiRefresh($remark);

            return 0;
        }

        /**
         * ======================================================
         * Call API
         * ======================================================
         */
        $api = new OnPay;
        $param = [];

        $url = rtrim((string) config('onpay.api_url'), '/') . '/api/v1/merchant/merchantsbalance';
        $response = $api->create_balance($url, $param);

        if (($response['success'] ?? false) === true) {

            // success -> reset circuit breaker
            Cache::forget($failKey);
            Cache::forget($cbKey);

            $collection = collect($response['data'] ?? []);

            $deposit  = $collection->firstWhere('type', 'deposit')['balance'] ?? 0;
            $withdraw = $collection->firstWhere('type', 'withdraw')['balance'] ?? 0;

            $remark = 'ยอด Deposit '.$deposit.' / ยอด Withdraw '.$withdraw;

            BankAccountProxy::where('banks', 310)->where('bank_type', 1)
                ->update([
                    'balance'     => $deposit,
                    'api_refresh' => $remark,
                ]);

            BankAccountProxy::where('banks', 310)->where('bank_type', 2)
                ->update([
                    'balance'     => $withdraw,
                    'api_refresh' => $remark,
                ]);

        } else {

            // fail -> increment counter
            $failCount = (int) Cache::increment($failKey);
            Cache::put($failKey, $failCount, now()->addMinutes(30));

            $msg  = (string) ($response['msg'] ?? 'ผิดพลาดในการเชื่อมต่อ ดึงยอด');
            $code = (string) ($response['code'] ?? '');
            $hint = $code !== '' ? " (code: {$code})" : '';

            $remark = "ดึงยอด OnPay ไม่สำเร็จ{$hint}: {$msg}";

            // เปิด circuit breaker เมื่อ fail >= 3 ครั้ง
            if ($failCount >= 3) {
                $openUntilTs = now()->addMinutes(5)->timestamp;
                Cache::put($cbKey, $openUntilTs, now()->addMinutes(6));

                $until = now()->setTimestamp($openUntilTs)->toDateTimeString();
                $remark .= " | เปิด circuit breaker พักถึง {$until}";
            }

            $this->touchApiRefresh($remark);
        }

        return 0;
    }

    private function touchApiRefresh(string $remark): void
    {
        BankAccountProxy::where('banks', 310)->where('bank_type', 1)
            ->update(['api_refresh' => $remark]);

        BankAccountProxy::where('banks', 310)->where('bank_type', 2)
            ->update(['api_refresh' => $remark]);
    }
}
