<?php

namespace App\Http\Middleware;

use Closure;

class TestDelay
{
    public function handle($request, Closure $next)
    {
        if (config('api.test.allow_delay')) {
            $header = config('api.test.delay_header');
            $query = config('api.test.delay_query');

            $ms = null;

// ให้น้ำหนัก header ก่อน (จำลอง client จริงง่าย)
            if ($request->hasHeader($header)) {
                $val = $request->header($header);
                if (is_numeric($val)) $ms = (int)$val;
            }

// รองลงมา: query param
            if ($ms === null && $request->has($query)) {
                $val = $request->input($query);
                if (is_numeric($val)) $ms = (int)$val;
            }

// ถ้าไม่ระบุ ให้ใช้ค่าดีฟอลต์ (3800ms)
            if ($ms === null) $ms = (int)config('api.test.default_delay_ms', 0);

            if ($ms > 0) {
                usleep($ms * 1000);
            }
        }

        return $next($request);
    }
}
