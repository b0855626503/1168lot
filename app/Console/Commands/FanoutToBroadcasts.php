<?php

namespace App\Console\Commands;

use App\Jobs\RunGlobalTask;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class FanoutToBroadcasts extends Command
{
    protected $signature = 'global:fanout-broadcasts {--payload=}';
    protected $description = 'ส่งงานให้ทุกเว็บผ่านคิว broadcasts:{APP_NAME} บน connection=fanout';

    public function handle(): int
    {
        $apps = (array)config('sites.apps', []);
        if (empty($apps)) {
            $this->error('ยังไม่ตั้งรายชื่อเว็บใน config/sites.php');
            return self::INVALID;
        }

        $payload = [];
        if ($j = $this->option('payload')) {
            $payload = json_decode($j, true) ?: [];
        }

        foreach ($apps as $app) {
            $queue = 'broadcasts:' . Str::slug($app, '_');

            dispatch(
                (new RunGlobalTask(['site' => $app] + $payload))
                    ->onConnection('fanout')   // ใช้ redis.fanout (prefix กลาง fanout_)
                    ->onQueue($queue)          // ชื่อคิวเฉพาะเว็บ
            );

            $this->info("Dispatched to [$app] via fanout on queue [$queue]");
        }

        return self::SUCCESS;
    }
}
