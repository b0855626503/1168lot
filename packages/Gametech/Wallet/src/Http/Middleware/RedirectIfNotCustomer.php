<?php

namespace Gametech\Wallet\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class RedirectIfNotCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null $guard
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $guard = 'customer')
    {
        if (!Auth::guard($guard)->check()) {
            return redirect()->route('customer.session.index');
        } else {
            if (Auth::guard($guard)->user()->enable != 'Y') {
                Auth::guard($guard)->logout();

                session()->flash('warning', 'โปรดติดต่อทีมงาน');

                return redirect()->route('customer.session.index');
            }else if(Auth::guard($guard)->user()->confirm != 'Y'){
                Auth::guard($guard)->logout();

                session()->flash('warning', 'สถานะ User ID อยู่ระหว่างตรวจสอบ โดยทีมงาน');

                return redirect()->route('customer.session.index');
            }
        }



        return $next($request);
    }
}
