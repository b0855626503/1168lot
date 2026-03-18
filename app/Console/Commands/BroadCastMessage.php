<?php

namespace App\Console\Commands;

use App\Events\RealTimeNewMessage;
use Illuminate\Console\Command;

class BroadCastMessage extends Command
{
    protected $signature = 'message:send
        {message : ข้อความที่ต้องการส่ง}
        {--ui=toast : toast หรือ swal}
        {--channel= : ค่าเริ่มต้น = {APP_NAME}_events}
        {--as=RealTime.Message.All : ชื่ออีเวนต์ที่ Echo listen}

        {--duration=20000 : เวลาแสดง toast (ms)}
        {--gravity=top : top|bottom}
        {--position=right : left|center|right}
        {--level= : danger|warning|success|info (กำหนดสีสไตล์สำเร็จ)}
        {--className= : ใส่คลาส CSS เพิ่ม เช่น bg-warning text-dark}
        {--style= : JSON style สำหรับ Toastify เช่น {"background":"#dc2626","color":"#fff"}}

        {--swal-title=แจ้งเตือน}
        {--swal-icon=info : success|error|warning|info|question}
        {--swal-timer= : ใส่ ms ถ้าอยากให้ปิดเอง}
        {--swal-confirm=1 : 1=แสดงปุ่ม OK, 0=ไม่แสดง}

        {--json= : ส่ง options ก้อนเดียวแบบ JSON เพื่อ override ทั้งหมด}';

    protected $description = 'ส่ง RealTimeNewMessage ไปยังช่อง broadcast (รองรับ Toastify/SweetAlert2 พร้อมตัวเลือกเวลา/ตำแหน่ง/สี)';

    public function handle(): int
    {
        $message = (string)$this->argument('message');

        // ถ้ามี --json ให้ลอง parse แล้วใช้แทนทุก option
        $json = $this->option('json');
        if (!empty($json)) {
            $opts = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('รูปแบบ --json ไม่ถูกต้อง: ' . json_last_error_msg());
                return self::INVALID;
            }
            $ui = $opts['ui'] ?? 'toast';
            $channel = $opts['channel'] ?? (config('app.name') . '_events');
            $as = $opts['as'] ?? 'RealTime.Message.All';
            $options = array_replace_recursive([
                'ui' => $ui,
                'channel' => $channel,
                'as' => $as,
                'toast' => [],
                'swal' => [],
            ], $opts);
            event(new RealTimeNewMessage($message, $options));
            $this->info("Broadcasted ({$options['as']}) on {$options['channel']} via JSON options.");
            return self::SUCCESS;
        }

        // โหมดปกติ: ดึง options ทีละตัว
        $ui = strtolower((string)$this->option('ui'));
        if (!in_array($ui, ['toast', 'swal'], true)) {
            $this->warn("ค่า --ui={$ui} ไม่ถูกต้อง จะใช้ค่า toast แทน");
            $ui = 'toast';
        }

        $channel = $this->option('channel') ?: (config('app.name') . '_events');
        $as = $this->option('as') ?: 'RealTime.Message.All';

        // Toast options
        $duration = (int)$this->option('duration');
        $gravity = (string)$this->option('gravity');
        $position = (string)$this->option('position');
        $level = $this->option('level');           // danger|warning|success|info
        $className = $this->option('className');   // เช่น bg-warning text-dark
        $styleJson = $this->option('style');       // JSON string

        $style = [];
        if (!empty($styleJson)) {
            $style = json_decode($styleJson, true) ?: [];
        }

        $toast = [
            'duration' => $duration > 0 ? $duration : 20000,
            'gravity' => in_array($gravity, ['top', 'bottom'], true) ? $gravity : 'top',
            'position' => in_array($position, ['left', 'center', 'right'], true) ? $position : 'right',
            'newWindow' => true,
            'close' => true,
            'stopOnFocus' => true,
        ];
        if (!empty($level)) $toast['level'] = strtolower((string)$level);
        if (!empty($className)) $toast['className'] = (string)$className;
        if (!empty($style)) $toast['style'] = $style;

        // Swal options
        $swalTimer = $this->option('swal-timer');
        $swalConfirm = $this->option('swal-confirm');

        $swal = [
            'title' => (string)($this->option('swal-title') ?: 'แจ้งเตือน'),
            'text' => $message,
            'icon' => (string)($this->option('swal-icon') ?: 'info'),
            'timer' => is_null($swalTimer) || $swalTimer === '' ? null : (int)$swalTimer,
            'showConfirmButton' => (bool)(is_null($swalConfirm) ? 1 : (int)$swalConfirm),
        ];

        $options = [
            'ui' => $ui,
            'channel' => $channel,
            'as' => $as,
            'toast' => $toast,
            'swal' => $swal,
        ];

        broadcast(new RealTimeNewMessage($message, $options));

        $this->info("Broadcasted ({$as}) on {$channel} as {$ui}.");
        return self::SUCCESS;
    }
}
