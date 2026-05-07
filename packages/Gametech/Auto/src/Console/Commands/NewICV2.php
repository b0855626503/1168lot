<?php

namespace Gametech\Auto\Console\Commands;

// เปลี่ยนเป็น Job สแกน/แตกงาน
use Gametech\Auto\Jobs\ScanIC;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class NewICV2 extends Command
{
    protected $signature = 'newic2:list {date?} {--force : บังคับรันแม้มีงานค้างของวันนั้นแล้ว}';
    protected $description = 'Check and enqueue cashback calculation per member for a given date (default: yesterday)';

    public function handle(): int
    {
        // รับวันที่จากอาร์กิวเมนต์; ถ้าไม่ส่ง ให้เป็นเมื่อวาน (ใช้โซนเวลาระบบ)
        $dateArg = $this->argument('date');
        $date = $dateArg ? Carbon::parse($dateArg)->toDateString() : now()->subDay()->toDateString();

        $this->info("Enqueue IC scan for date: {$date}");

        // กันซ้ำระดับวันด้วย mutex (TTL 1 ชั่วโมง; ปรับได้)
        $mutexKey = "ic:scan:{$date}";
        $force = (bool) $this->option('force');

        if (! $force && ! Cache::add($mutexKey, 1, now()->addHour())) {
            $this->warn("Skipped: scan for {$date} already enqueued or running. Use --force to override.");

            return self::SUCCESS;
        }

        // โยนงานสแกน (แตกเป็นงานย่อยต่อสมาชิกภายใน)
        ScanIC::dispatch($date)->onQueue('default');

        $this->info('Queued. Monitor Horizon for progress.');

        return self::SUCCESS;
    }
}
