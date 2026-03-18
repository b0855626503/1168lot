<?php

namespace Gametech\Admin\Http\Controllers;

use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use PragmaRX\Google2FALaravel\Support\Authenticator;


class LoginController extends AppBaseController
{
    use AuthenticatesUsers;
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected $redirectTo = '/admin';



    /**
     * Create a new Repository instance.
     *
     * @return void
    */
    public function __construct()
    {
//        $this->middleware('guest')->except('logout');
        $this->middleware('admin')->only('logout');


        $this->_config = request('_config');
    }

    public function username(): string
    {
        return 'user_name';
    }

    public function login(Request $request)
    {
        $this->validateLogin($request);

        $username = $request->input('user_name');
        $password = $request->input('password');
//        dd($request);

        $chk = app('Gametech\Admin\Repositories\AdminRepository')->findOneByField($this->username(),$username);

        if(is_null($chk)){
            return $this->sendFailedLoginResponse($request);
        }

//        if(is_null($chk->password)){
//            if($chk->user_pass == MD5($password)){
//                $dataadd['password'] = Hash::make($password);
//                app('Gametech\Admin\Repositories\AdminRepository')->update($dataadd, $chk->code);
//            }
//        }


        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            return $this->sendLoginResponse($request);
        }


        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    protected function attemptLogin(Request $request)
    {

        return $this->guard()->attempt(
            $this->credentials($request), $request->filled('remember')
        );

    }

    protected function authenticated(Request $request, $user)
    {
        $request->session()->regenerate();

        Auth::guard('admin')->logoutOtherDevices(request('password'));

        Event::dispatch('admin.login.after', $user);

        if (!$user->google2fa_secret ||!$user->google2fa_enable) {
            return redirect()->route('admin.2fa.setting');
//            return redirect()->intended('auth');
        }

        return redirect()->intended('dashboard');
//        return redirect()->route('admin.home.index');

    }




    protected function sendFailedLoginResponse(Request $request)
    {
//        dd($request);
        session()->flash('error', 'ไม่สามารถเข้าสู่ระบบได้ โปรดตรวจสอบ');
        return redirect()->back();

    }

    /**
     * Display the resource.
     *
     * @return View
     */
    public function show(\Codedge\Updater\UpdaterManager $updater)
    {

        if (Auth::guard('admin')->check()) {

            return redirect()->route('admin.2fa.setting');

        } else {


            $current = $updater->source()->getVersionInstalled();

            $current = 'ขณะนี้ระบบเป็น v '.$current;

            return view($this->_config['view'])->with('current',$current);
        }
    }

    public function logout(Request $request)
    {
        $user = Auth::guard('admin')->user();

        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        Event::dispatch('admin.logout.after', $user);


        (new Authenticator(request()))->logout();


        if ($response = $this->loggedOut($request)) {
            return $response;
        }

        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect('/');
    }


    protected function loggedOut(Request $request): RedirectResponse
    {

        return redirect()->route('admin.session.index');

    }



    protected function guard()
    {
        return Auth::guard('admin');
    }
}
