<?php

namespace App\Http\Middleware;


use Closure;
use Gametech\Member\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class LastSeenUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $guard
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $guard = 'customer')
    {
        if (Auth::guard($guard)->check()) {
            $expireTime = now()->addMinute(10); // keep online for 1 min
            Cache::put('is_online'.Auth::guard($guard)->user()->code, true, $expireTime);

            //Last Seen
            Member::where('code', Auth::guard($guard)->user()->code)->update(['last_seen' => now()]);
        }
        return $next($request);
    }
}
