<?php

declare(strict_types=1);

namespace Gametech\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * WealthPay Balance Job
 *
 * Wealthwave Flex API ยังไม่มี balance enquiry endpoint
 * Job นี้ถูก dispatch จาก deposit และ deposit_callback
 * เพื่อ trigger auto-topup pipeline ที่ประมวลผล bank_payment records
 *
 * หาก Wealthwave เพิ่ม balance endpoint ในอนาคต สามารถขยาย job นี้
 * ให้ query balance และอัปเดต BankAccountProxy ได้
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
        return 0;
    }
}
