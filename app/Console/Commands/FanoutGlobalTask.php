<?php

namespace App\Console\Commands;

use App\Jobs\RunGlobalTask;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Bus;


// ให้มีอยู่ทุกเว็บ

class FanoutGlobalTask extends Command
{
    protected $signature = 'global:fanout 
        {--payload= : JSON payload ส่งเข้างาน} 
        {--queue=global : ชื่อคิวร่วมที่แต่ละเว็บฟังอยู่}';

    protected $description = 'ส่งงานไปให้ทุกเว็บผ่านคิว global โดยใช้ redis prefix ต่อเว็บ';

    public function handle(): int
    {
        $sites = config('sites.apps', []);
        if (empty($sites)) {
            $this->error('ไม่มีรายชื่อเว็บใน config/sites.php');
            return self::INVALID;
        }

        $queue = (string)$this->option('queue');
        $payload = [];
        if ($json = $this->option('payload')) {
            $payload = json_decode($json, true) ?: [];
        }

        $jobs = [];

        foreach ($sites as $site) {
            // 1) ลงทะเบียน redis connection ชื่อเดียวกับเว็บ (ต่างกันที่ prefix)
            Config::set("database.redis.{$site}", array_merge(
                config('database.redis.default'), // host/pass/port/db เดียวกัน
                ['options' => ['prefix' => "{$site}_"]] // ถ้าคุณไม่อยากมี _ ก็ใส่ "{$site}"
            ));

            // 2) ลงทะเบียน queue connection ที่ชี้ไป redis connection ข้างบน
            Config::set("queue.connections.{$site}", array_merge(
                config('queue.connections.redis'),
                ['connection' => $site] // ใช้ redis connection ชื่อ $site
            ));

            // 3) ใส่ Job หนึ่งชิ้นต่อเว็บ → บังคับส่งไป connection (=เว็บ) และ queue เดียวกัน
            $jobs[] = (new RunGlobalTask(['site' => $site] + $payload))
                ->onConnection($site)
                ->onQueue($queue);
        }

        Bus::batch($jobs)->name("Global fanout to " . count($sites) . " sites")->dispatch();

        $this->info("Dispatched to " . count($sites) . " sites on queue '{$queue}'.");
        return self::SUCCESS;
    }
}
