<?php

// app/Providers/XrayServiceProvider.php
namespace App\Providers;

use App\Support\Xray;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;
use Illuminate\Redis\Events\CommandExecuted as RedisCommandExecuted;
use Illuminate\Http\Client\Events\ResponseReceived;

class XrayServiceProvider extends ServiceProvider
{
    public function register() { $this->app->singleton(Xray::class, fn() => new Xray()); }

    public function boot()
    {
        // DB: เก็บทุก query (ms)
        DB::listen(function (QueryExecuted $e) {
            $x = app(Xray::class); if (!$x->active) return;
            $x->db[] = ['sql' => $e->sql, 'time' => (float)$e->time];
        });

        // Redis: ต้องใช้ Redis facade ของ Laravel เท่านั้นถึงจะยิง event นี้
        Event::listen(RedisCommandExecuted::class, function (RedisCommandExecuted $e) {
            $x = app(Xray::class); if (!$x->active) return;
            $x->redis[] = ['cmd' => $e->command, 'time' => (float)($e->time ?? 0)];
        });

        // Laravel HTTP client: เก็บเวลาตอบและสถานะ
        Event::listen(ResponseReceived::class, function (ResponseReceived $e) {
            $x = app(Xray::class); if (!$x->active) return;
            $stats = optional($e->response->transferStats());
            $ms = (int) round(($stats?->getTransferTime() ?? 0) * 1000);
            $x->http[] = [
                'uri'  => (string) $e->request->url(),
                'ms'   => $ms,
                'code' => $e->response->status(),
            ];
        });
    }
}
