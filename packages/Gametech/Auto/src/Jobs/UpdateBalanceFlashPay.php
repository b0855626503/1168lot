<?php

declare(strict_types=1);

namespace Gametech\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * FlashPay Balance Job
 *
 * FlashPay มี GET /api/v1/balance แต่ response schema ยังไม่ทราบแน่ชัด
 * Job นี้ถูก dispatch จาก deposit และ deposit_callback
 * เพื่อ trigger auto-topup pipeline ที่ประมวลผล bank_payment records
 *
 * TODO: เมื่อได้ balance response schema จาก FlashPay แล้ว
 *       ขยาย job นี้ให้ query balance และอัปเดต BankAccountProxy
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
        return 0;
    }
}
