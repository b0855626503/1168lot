<?php

namespace Gametech\LogAdmin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Gametech\LogAdmin\Http\Traits\ActivityLogger;

class LogActivity
{
    use ActivityLogger;

    /**
     * Handle an incoming request.
     *
     * @param Request  $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $description = null)
    {
        if (config('LaravelLogger.loggerMiddlewareEnabled') && $this->shouldLogger($request)) {
            ActivityLogger::activitie($description);
        }

        return $next($request);
    }

    /**
     * Determine if the request has a URI that should log.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    protected function shouldLogger($request)
    {
        foreach (config('LaravelLogger.loggerMiddlewareExcept', []) as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return false;
            }
        }

        return true;
    }
}
