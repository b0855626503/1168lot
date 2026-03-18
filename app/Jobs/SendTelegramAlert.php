<?php

namespace App\Jobs;

use App\Helpers\TelegramFailedBot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTelegramAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;            // รีไทรได้
    public $timeout = 10;           // วินาที
    public $backoff = [5, 30, 120]; // หน่วงก่อนรีไทร

    public function __construct(
        public string $endpoint,   // เช่น 'notify/send'
        public string $message
    )
    {
    }

    public function handle(): void
    {
        // ถ้าข้อความยาวเกิน 4000 ตัดให้หน่อยกันโดนปฏิเสธ
        $msg = mb_strimwidth($this->message, 0, 3900, '...');
        TelegramFailedBot::Send($this->endpoint, $msg);
    }
}