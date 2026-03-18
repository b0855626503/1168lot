<?php

namespace Gametech\Admin\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class Bouncer
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null $guard
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $guard = 'admin')
    {
        if (!Auth::guard($guard)->check()) {
            Auth::guard($guard)->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            (new Authenticator(request()))->logout();
            return redirect()->route('admin.session.index');
//            return redirect()->intended('login');
        } else {
            if (Auth::guard($guard)->user()->enable != 'Y') {
                Auth::guard($guard)->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                (new Authenticator(request()))->logout();
//                session()->flash('warning', 'สมาชิกถูกระงับการใช้งาน โปรดติดต่อทีมงาน');
                return redirect()->route('admin.session.index');
//                return redirect()->intended('login');
            }


//            if (Auth::guard($guard)->user()->login_session != $request->ip()) {
//                Auth::guard($guard)->logout();
//                $request->session()->invalidate();
//                $request->session()->regenerateToken();
//                (new Authenticator(request()))->logout();
//                session()->flash('error', 'ออกจากระบบอัตโนมัติ เนื่องจาก Ip ปัจจุบันของคุณ ไม่ตรงกับตอนเข้าระบบ');
//                return redirect()->route('admin.session.index');
////                return redirect()->intended('login');
//            }

            if (core()->DateDiff(Auth::guard($guard)->user()->lastlogin) > 7) {
                Auth::guard($guard)->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                (new Authenticator(request()))->logout();
                session()->flash('error', 'ออกจากระบบอัตโนมัติ เนื่องจาก Session หมดอายุการใช้งาน');
                return redirect()->route('admin.session.index');
//                return redirect()->intended('login');
            }
        }


        if ($this->isPermissionsEmpty()) {
            auth()->guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            (new Authenticator(request()))->logout();

//            session()->flash('error', __('admin::app.error.403.message'));

            return redirect()->route('admin.session.index');
        }


        return $next($request);
    }

    public function isPermissionsEmpty()
    {
        if (! $role = auth()->guard('admin')->user()->role) {
            abort(401, 'This action is unauthorized.');
        }

        if ($role->permission_type === 'all') {
            return false;
        }

        if (
            $role->permission_type !== 'all'
            && empty($role->permissions)
        ) {
            return true;
        }

        $this->checkIfAuthorized();

        return false;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @return mixed
     */
    public function checkIfAuthorized()
    {
        $acl = app('acl');

        if (! $acl) {
            return;
        }

        if (isset($acl->roles[Route::currentRouteName()])) {
            bouncer()->allow($acl->roles[Route::currentRouteName()]);
        }

    }
}
