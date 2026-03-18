<?php

namespace Gametech\LogUser\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser;

class LogActivityUser
{
    use ActivityLoggerUser;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param null $description
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $description = null)
    {
        if (config('LaravelLoggerUser.loggerMiddlewareEnabled') && $this->shouldLog($request)) {
            ActivityLoggerUser::activity($description);
        }

        return $next($request);
    }

    /**
     * Determine if the request has a URI that should log.
     *
     * @param Request $request
     *
     * @return bool
     */
    protected function shouldLog(Request $request): bool
    {
        foreach (config('LaravelLoggerUser.loggerMiddlewareExcept', []) as $except) {
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
