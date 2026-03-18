<?php

namespace App\Http\Middleware;

use App\Helpers\TelegramFailedBot;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class StartRequestTimer
{
    public function handle(Request $request, Closure $next)
    {
        $request->attributes->set('start_time', microtime(true));
        return $next($request);
    }
}