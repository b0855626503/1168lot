<?php
// app/Http/Middleware/XraySlowRequest.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class XraySlowRequest
{
    public function handle(Request $request, Closure $next, $routeThreshold = null)
    {
        // ---------- Config & Switches ----------
        $cfg = [
            'enabled'          => (bool) config('xray.enabled', true),
            'threshold_ms'     => (int)  ($routeThreshold ?? $request->query('xray', $request->header('X-Xray-Threshold', config('xray.threshold_ms', 150)))),
            'sql_threshold_ms' => (int)  config('xray.sql_threshold_ms', 120),
            'redis_threshold_ms'=> (int) config('xray.redis_threshold_ms', 50),
            'http_threshold_ms'=> (int)  config('xray.http_threshold_ms', 120),
            'sample'           => (int)  config('xray.sample', 100), // 0..100 (%)
            'channel'          => (string) config('xray.channel', 'slowlog'),
            'expose_header'    => (bool) config('xray.expose_header', false),
            'health_paths'     => (array) config('xray.ignore_paths', ['/health', '/__ping', '/__static_ping']),
            'top_sql'          => (int)  config('xray.top_sql', 3),
            'top_repeated_sql' => (int)  config('xray.top_repeated_sql', 5),
        ];

        // ปิดทิ้งเร็ว ๆ ถ้าไม่เปิดใช้งาน และไม่มี override
        if (!$cfg['enabled'] && !$request->has('xray') && !$request->headers->has('X-Xray-Threshold')) {
            return $next($request);
        }

        // ignore health/preflight เพื่อลดนอยส์
        if ($request->isMethod('OPTIONS') || $this->isHealthPath($request->getPathInfo(), $cfg['health_paths'])) {
            return $next($request);
        }

        // sampling (อนุญาต override ด้วย query/header หรือ route param)
        if ($cfg['sample'] < 100) {
            $force = $request->boolean('xray', false) || $request->headers->has('X-Xray-Threshold') || $routeThreshold !== null;
            if (!$force && random_int(1, 100) > $cfg['sample']) {
                return $next($request);
            }
        }

        // ---------- Start ----------
        $t0 = hrtime(true);

        // เปิด query log เฉพาะรีเควสนี้ (ปลอดภัยกับ Octane)
        $conn = \DB::connection();
        $conn->enableQueryLog();

        // ถ้ามี App\Support\Xray (จาก ServiceProvider ที่คุณลงไว้) ให้ activate เพื่อเก็บ Redis/HTTP events
        $xray = app()->bound(\App\Support\Xray::class) ? app(\App\Support\Xray::class) : null;
        if ($xray) {
            $xray->activate(); // จะเก็บ redis/http จาก listeners ที่ลงใน XrayServiceProvider
            $xray->reset();
        }

        // request id (แนบไว้ช่วยตามรอย)
        $reqId = $request->headers->get('X-Request-Id') ?: Str::uuid()->toString();

        try {
            $response = $next($request);
        } finally {
            // ---------- Stop & Collect ----------
            $total  = (int) round((hrtime(true) - $t0) / 1e6); // ms

            $queries = $conn->getQueryLog();
            $sqlMs   = (int) round(array_sum(array_column($queries, 'time')));
            $sqlCnt  = count($queries);

            $redisMs = 0; $redisCnt = 0;
            $httpMs  = 0; $httpCnt  = 0;
            $httpList = [];

            if ($xray) {
                $redisMs  = (int) round(array_sum(array_column($xray->redis, 'time')));
                $redisCnt = count($xray->redis);

                $httpMs   = (int) array_sum(array_column($xray->http, 'ms'));
                $httpCnt  = count($xray->http);
                $httpList = $xray->http;

                $xray->deactivate(); // สำคัญมากสำหรับ Octane
            }

            $conn->flushQueryLog();
            $conn->disableQueryLog();

            $appMs  = max(0, $total - ($sqlMs + $redisMs + $httpMs));

            // ---------- Decide to log ----------
            $hit =
                $total  >= $cfg['threshold_ms'] ||
                $sqlMs  >= $cfg['sql_threshold_ms'] ||
                $redisMs>= $cfg['redis_threshold_ms'] ||
                $httpMs >= $cfg['http_threshold_ms'] ||
                $request->boolean('xray', false); // force via query

            if ($hit) {
                // Top SQL (sanitize)
                $topSql = collect($queries)
                    ->sortByDesc(fn($q) => (float)($q['time'] ?? 0))
                    ->take($cfg['top_sql'])
                    ->map(function ($q) {
                        $sql = (string) ($q['query'] ?? $q['sql'] ?? '');
                        $sql = trim(preg_replace('/\s+/', ' ', $sql));
                        return [
                            'ms'        => (int) round((float)($q['time'] ?? 0)),
                            'sql'       => Str::limit($sql, 500),
                            'bindings'  => isset($q['bindings']) ? count($q['bindings']) : null,
                        ];
                    })->values()->all();

                $repeatedSql = $this->summarizeRepeatedQueries($queries, $cfg['top_repeated_sql']);
                $repeatedSqlCount = (int) array_sum(array_column($repeatedSql, 'duplicate_count'));
                $repeatedSqlMs = (int) round(array_sum(array_column($repeatedSql, 'total_ms')));

                $route   = optional($request->route())->getName();
                $action  = optional($request->route())->getActionName();

                Log::channel($cfg['channel'])->info('SLOW', [
                    'req_id'    => $reqId,
                    'total_ms'  => $total,
                    'sql_ms'    => $sqlMs,   'sql_count'   => $sqlCnt,  'top_sql' => $topSql,
                    'repeated_sql_count' => $repeatedSqlCount,
                    'repeated_sql_ms'    => $repeatedSqlMs,
                    'top_repeated_sql'   => $repeatedSql,
                    'redis_ms'  => $redisMs, 'redis_count' => $redisCnt,
                    'http_ms'   => $httpMs,  'http_count'  => $httpCnt, 'http'    => $httpList,
                    'app_ms'    => $appMs,
                    'method'    => $request->method(),
                    'uri'       => $request->getRequestUri(),
                    'route'     => $route,
                    'action'    => $action,
                    'status'    => optional($response)->getStatusCode(),
                    'ip'        => $request->ip(),
                    'user_id'   => optional(auth()->user())->id,
                    'mem_mb'    => round(memory_get_peak_usage(true) / 1048576, 1),
                    'env'       => app()->environment(),
                ]);
            }

            // ---------- Optional response header ----------
            if ($cfg['expose_header']) {
                $response->headers->set('X-Request-Id', $reqId);
                $response->headers->set(
                    'X-Xray',
                    "total={$total};sql={$sqlMs};redis={$redisMs};http={$httpMs};app={$appMs}"
                );
            }
        }

        return $response;
    }

    private function isHealthPath(string $path, array $patterns): bool
    {
        foreach ($patterns as $p) {
            if (Str::is(ltrim($p, '/'), ltrim($path, '/'))) {
                return true;
            }
        }
        return false;
    }

    /**
     * สรุป SQL ที่ถูกยิงซ้ำใน request เดียว (fingerprint ตาม SQL text)
     *
     * @param array<int, array<string, mixed>> $queries
     * @return array<int, array<string, int|string>>
     */
    private function summarizeRepeatedQueries(array $queries, int $top): array
    {
        $bucket = [];

        foreach ($queries as $query) {
            $rawSql = (string) ($query['query'] ?? $query['sql'] ?? '');
            if ($rawSql === '') {
                continue;
            }

            $sql = trim((string) preg_replace('/\s+/', ' ', $rawSql));
            $ms = (float) ($query['time'] ?? 0);
            $fingerprint = md5($sql);

            if (! isset($bucket[$fingerprint])) {
                $bucket[$fingerprint] = [
                    'sql' => $sql,
                    'count' => 0,
                    'total_ms' => 0.0,
                    'max_ms' => 0.0,
                ];
            }

            $bucket[$fingerprint]['count']++;
            $bucket[$fingerprint]['total_ms'] += $ms;
            $bucket[$fingerprint]['max_ms'] = max($bucket[$fingerprint]['max_ms'], $ms);
        }

        return collect($bucket)
            ->filter(fn ($item) => (int) ($item['count'] ?? 0) > 1)
            ->sort(function ($a, $b) {
                $aTotal = (float) ($a['total_ms'] ?? 0);
                $bTotal = (float) ($b['total_ms'] ?? 0);
                if ($aTotal === $bTotal) {
                    return (int) ($b['count'] ?? 0) <=> (int) ($a['count'] ?? 0);
                }

                return $bTotal <=> $aTotal;
            })
            ->take(max(1, $top))
            ->map(function ($item) {
                $count = (int) ($item['count'] ?? 0);

                return [
                    'count' => $count,
                    'duplicate_count' => max(0, $count - 1),
                    'total_ms' => (int) round((float) ($item['total_ms'] ?? 0)),
                    'max_ms' => (int) round((float) ($item['max_ms'] ?? 0)),
                    'sql' => Str::limit((string) ($item['sql'] ?? ''), 500),
                ];
            })
            ->values()
            ->all();
    }
}
