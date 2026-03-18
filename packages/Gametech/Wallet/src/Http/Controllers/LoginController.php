<?php

namespace Gametech\Wallet\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Gametech\Core\Repositories\SlideRepository;
use Gametech\Member\Models\MemberProxy;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LoginController extends AppBaseController
{
    use AuthenticatesUsers;

    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected $redirectTo = RouteServiceProvider::HOME;

    protected $slideRepository;

    /**
     * Create a new Repository instance.
     *
     * @return void
     */
    public function __construct(
        SlideRepository $slideRepo
    ) {

        $this->middleware('guest')->except('logout');
        $this->middleware('customer')->only('logout');

        $this->_config = request('_config');

        $this->slideRepository = $slideRepo;

    }

    /**
     * Display the resource.
     *
     * @return View
     */
    public function show(Request $request)
    {
        $config = core()->getConfigData();

        if (Auth::guard('customer')->check()) {
            $user = Auth::guard('customer')->user();
            if ($user->confirm == 'Y') {
                return redirect()->route('customer.home.index');
            } else {
                if ($config->verify_sms == 'Y') {
                    return redirect()->route('customer.verify.index');
                } else {
                    session()->flash('success', 'ขณะนี้ข้อมูลการสมัครของท่าน อยู่ในกระบวนการตรวจสอบโดยทีมงาน เมื่อทีมงานดพเนินการเสร็จ ท่านสมาชิกจะสามารถเข้าสู่ระบบของเวบไซต์ได้');
                    $this->logout($request);
                }
            }

        } else {

            $games = [];


            $gameTypes = app('Gametech\Game\Repositories\GameTypeRepository')->findWhere(['enable' => 'Y', 'status_open' => 'Y']);
            $slides = $this->slideRepository->findWhere(['enable' => 'Y']);
//            $games = $this->getProviders();

            $gameTypes->map(function ($item) {
                $item->icon = Storage::url('icon_cat/'.$item->icon);
                return $item;
            });

            return view($this->_config['view'], compact('games', 'slides','gameTypes'));
        }
    }

    protected function guard()
    {
        return Auth::guard('customer');
    }

    public function logout(Request $request)
    {
        $user = Auth::guard('customer')->user();

        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerate();

        app('Gametech\Member\Repositories\MemberRepository')->update(['session_id' => ''], $user->code);

        Event::dispatch('customer.logout.after', $user);

        if ($response = $this->loggedOut($request)) {
            return $response;
        }

        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect('/');
    }

    protected function loggedOut(Request $request): RedirectResponse
    {

        return redirect()->route($this->_config['redirect']);

    }

    public function store($id, Request $request)
    {
        if (! is_numeric($id)) {
            $id = null;
            $contributor = null;
        } else {
            $data = app('Gametech\Member\Repositories\MemberRepository')->find($id);
            $contributor = $data->name;
        }
        $banks = app('Gametech\Payment\Repositories\BankRepository')->findWhere(['enable' => 'Y', 'show_regis' => 'Y', ['code', '<>', 0]]);
        $refers = app('Gametech\Core\Repositories\ReferRepository')->findWhere(['enable' => 'Y', ['code', '<>', 0]]);

        return view($this->_config['view'], compact('banks', 'refers', 'contributor'))->with('id', $id);
    }

    //    public function show(Request $request)
    //    {
    //        $config = core()->getConfigData();
    //
    //        if (Auth::guard('customer')->check()) {
    //            $user = Auth::guard('customer')->user();
    //            if ($user->confirm == 'Y') {
    //                return redirect()->route('customer.home.index');
    //            } else {
    //                if ($config->verify_sms == 'Y') {
    //                    return redirect()->route('customer.verify.index');
    //                } else {
    //                    session()->flash('success', 'ขณะนี้ข้อมูลการสมัครของท่าน อยู่ในกระบวนการตรวจสอบโดยทีมงาน เมื่อทีมงานดพเนินการเสร็จ ท่านสมาชิกจะสามารถเข้าสู่ระบบของเวบไซต์ได้');
    //                    $this->logout($request);
    //                }
    //            }
    //
    //
    //        } else {
    //
    //            $slides = $this->slideRepository->findWhere(['enable' => 'Y']);
    //            return view($this->_config['view'], compact('slides'));
    //        }
    //    }

    public function register(Request $request): RedirectResponse
    {
        $otp = '';
        $config = core()->getConfigData();

        $datenow = now()->toDateTimeString();
        $today = now()->toDateString();
        $ip = $request->ip();
        //        $data = $request->input();

        $data = $request->all();

        //        $data['user_name'] = Str::of($data['user_name'])->replaceMatches('/[^0-9]++/', '')->trim()->__toString();
        $username = strip_tags($data['user_name']);
        $tel = $username;
        $data['tel'] = $tel;

        $acc_no = Str::of($data['acc_no'])->replaceMatches('/[^0-9]++/', '')->trim()->__toString();
        $data['acc_no'] = $acc_no;
        $bank_code = $data['bank'];
        $data['wallet_id'] = strip_tags($data['tel']);

        $lineid = trim(strip_tags($data['lineid']));

        $wallet_id = trim($data['wallet_id']);

        if ($config->freecredit_all === 'Y') {
            $freecredit = 'Y';
        } else {
            $freecredit = 'N';
        }

        if ($config->verify_open === 'Y') {
            $verify = 'N';
            if ($config->verify_sms === 'Y') {
                $otp = rand(100001, 999999);
            }

        } else {
            $verify = 'Y';
        }

        $validator = Validator::make($data, [
            'acc_no' => [
                'required',
                'digits_between:1,14',
                Rule::unique('members', 'acc_no')->where(function ($query) use ($bank_code) {
                    return $query->where('bank_code', $bank_code);
                }),
            ],
            //            'wallet_id' => [
            //                'required',
            //                Rule::unique('members', 'wallet_id')->where(function ($query) use ($wallet_id) {
            //                    return $query->where('wallet_id', $wallet_id);
            //                })
            //            ],
            'firstname' => 'required|alpha',
            'lastname' => 'required|alpha',
            'password' => 'required|min:4|max:10',
            'password_confirm' => 'min:4|same:password',
            'user_name' => 'required|numeric|unique:members,user_name',
            'wallet_id' => 'required|numeric|unique:members,wallet_id',
            'tel' => 'required|numeric|unique:members,tel',
            'bank' => 'required|numeric',
            'refer' => 'required|numeric',
            //            'g-recaptcha-response' => 'required'
        ]);

        //        dd($validator);
        if ($validator->fails()) {
            session()->flash('error', Lang::get('app.register.fail'));

            return redirect()->back()->withErrors($validator)->withInput();

        }

        //        $request->validate([
        //            'acc_no' => 'required|digits_between:1,10|unique:members,acc_no',
        //            'firstname' => 'required|string',
        //            'lastname'  => 'required|string',
        //            'password' => 'required|min:6',
        //            'password_confirm' => 'min:6|same:password',
        //            'tel'   => 'required|numeric|unique:members,user_name',
        //            'bank'   => 'required|numeric',
        //            'lineid'   => 'required|string',
        //            'refer'   => 'required|numeric',
        //            'g-recaptcha-response' => 'required'
        //        ]);

        Event::dispatch('customer.register.before', $data);

        if (! isset($data['upline'])) {
            $upline = 0;
        } else {
            $upline = $data['upline'];
            unset($data['upline']);
        }

        $refer = $data['refer'];
        unset($data['refer']);

        $pass = $data['password'];
        //        $pass_confirm = $data['password_confirm'];
        //        unset($data['password_confirm']);
        unset($data['password']);
        $data['firstname'] = strip_tags($data['firstname']);
        $data['lastname'] = strip_tags($data['lastname']);
        $name = $data['firstname'].' '.$data['lastname'];
        if (isset($data['promotion'])) {
            $pro = $data['promotion'];
        } else {
            $pro = 'N';
        }

        //        $param = [
        //            'secret' => config('capcha.secret'),
        //            'response' => $data['g-recaptcha-response']
        //        ];
        //
        //
        //        $captcha_verify_url = "https://www.google.com/recaptcha/api/siteverify";
        //
        //        $response = Http::asForm()->post($captcha_verify_url, $param);
        //
        //
        //        if ($response->failed()) {
        //
        //            session()->flash('error', 'พบข้อผิดพลาดในการตรวจสอบ Captcha');
        //            return redirect()->back();
        //
        //        } elseif ($response->successful()) {
        //            $response = $response->json();
        //
        //            if ($response['success'] !== true) {
        //                session()->flash('error', 'คุณป้อน Captcha ไม่ถูกต้อง');
        //                return redirect()->back();
        //            }
        //        }
        unset($data['g-recaptcha-response']);

        unset($data['bank']);
        if ($bank_code == 4) {
            $acc_check = substr($acc_no, -4);
        } else {
            $acc_check = substr($acc_no, -6);
        }
        $acc_bay = substr($acc_no, -7);

        $data = array_merge($data, [
            'password' => Hash::make($pass),
            'refer_code' => $refer,
            'upline_code' => $upline,
            'user_name' => $username,
            'user_pass' => $pass,
            'wallet_id' => $wallet_id,
            'tel' => $tel,
            'lineid' => $lineid,
            'acc_no' => $acc_no,
            'acc_check' => $acc_check,
            'acc_bay' => $acc_bay,
            'acc_kbank' => '',
            'bank_code' => $bank_code,
            'confirm' => $verify,
            'freecredit' => $freecredit,
            'check_status' => 'N',
            'promotion' => $pro,
            'name' => $name,
            'user_create' => $name,
            'user_update' => $name,
            'lastlogin' => $datenow,
            'date_regis' => $today,
            'birth_day' => $today,
            'session_limit' => null,
            'payment_limit' => null,
            'payment_delay' => null,
            'remark' => '',
            'gender' => 'M',
            'otp' => $otp,
            'ip' => $ip,
        ]);

        $response = app('Gametech\Member\Repositories\MemberRepository')->create($data);

        if (! $response->code) {
            session()->flash('error', Lang::get('app.register.fail2'));

            return redirect()->back();
        }

        if ($config->verify_open == 'N') {

            if ($config->seamless == 'Y') {

                $game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y']);
                $member = app('Gametech\Member\Repositories\MemberRepository')->find($response->code);
                $res = app('Gametech\Game\Repositories\GameUserRepository')->addGameUser($game->code, $member->code, ['username' => $username, 'name' => $name, 'user_create' => $name]);
                if ($res['success'] === true) {
                    session()->flash('success', Lang::get('app.register.success'));

                    if ($this->attemptLogin($request)) {
                        return $this->sendLoginResponse($request);
                    }

                    return $this->sendFailedLoginResponse($request);
                    //                    Auth::guard('customer')->login($response);
                    //                    return redirect()->intended(route($this->_config['redirect']));
                } else {
                    app('Gametech\Member\Repositories\MemberRepository')->delete($response->code);
                    session()->flash('error', $res['msg']);

                    return redirect()->back();
                }

            } else {

                if ($config->multigame_open === 'N') {
                    $game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y']);
                    $member = app('Gametech\Member\Repositories\MemberRepository')->find($response->code);
                    $res = app('Gametech\Game\Repositories\GameUserRepository')->addGameUser($game->code, $member->code, $member);

                    if ($res['success'] === true) {
                        session()->flash('success', 'สมัครสมาชิกสำเร็จแล้ว ยินดีต้อนรับเข้าสู่ระบบ');
                        Auth::guard('customer')->login($response);

                        return redirect()->intended(route($this->_config['redirect']));
                    } else {
                        app('Gametech\Member\Repositories\MemberRepository')->delete($response->code);
                        session()->flash('error', $res['msg']);

                        return redirect()->back();
                    }
                } else {
                    session()->flash('success', 'สมัครสมาชิกสำเร็จแล้ว ยินดีต้อนรับเข้าสู่ระบบ');
                    Auth::guard('customer')->login($response);

                    return redirect()->intended(route($this->_config['redirect']));

                }
            }
        } else {

            session()->flash('success', 'ขณะนี้ข้อมูลการสมัครของท่าน อยู่ในกระบวนการตรวจสอบโดยทีมงาน เมื่อทีมงานดำเนินการเสร็จ ท่านสมาชิกจะสามารถเข้าสู่ระบบของเวบไซต์ได้');
            if ($config->verify_sms === 'Y') {
                return redirect()->route($this->_config['redirect'])->withInput(['user_name' => $username, 'password' => $pass]);
            } else {
                return redirect()->back();
            }

        }

    }

    protected function sendFailedLoginResponse(Request $request)
    {
        //        dd($request);
        $username = $request->input('user_name');
        $password = $request->input('password');
    
        Event::dispatch('customer.login.fail', $username.'|'.$password);

        session()->flash('error', Lang::get('app.login.fail'));

        return redirect()->back();

    }

    public function login(LoginRequest  $request)
    {

        $this->validateLogin($request);

        $username = $request->input('user_name');
        //        $password = $request->input('password');
        //        dd($request);

        //        $param = (object)[
        //          'user_name' => $username,
        //          'user_pass' =>  $password
        //        ];

        //        $param->user_name = $username;
        //        $param->user_pass = $password;

        $chk = app('Gametech\Member\Repositories\MemberRepository')->findOneByField($this->username(), $username);

        if (is_null($chk)) {
            return $this->sendFailedLoginResponse($request);
        }

        if (is_null($chk->password)) {
            $dataadd['password'] = Hash::make($chk->user_pass);
            app('Gametech\Member\Repositories\MemberRepository')->update($dataadd, $chk->code);
        }

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    public function username(): string
    {
        return 'user_name';
    }

    public function register_api(Request $request)
    {
        $otp = '';
        $config = core()->getConfigData();

        $datenow = now()->toDateTimeString();
        $today = now()->toDateString();
        $ip = $request->ip();
        //        $data = $request->input();

        $data = $request->all();

        $data['user_name'] = Str::of($data['user_name'])->replaceMatches('/[^0-9]++/', '')->trim()->__toString();
        $username = strip_tags($data['user_name']);
        $tel = $username;
        $data['tel'] = $tel;

        $acc_no = Str::of($data['acc_no'])->replaceMatches('/[^0-9]++/', '')->trim()->__toString();
        $data['acc_no'] = $acc_no;
        $bank_code = $data['bank'];
        $data['wallet_id'] = strip_tags($data['tel']);

        $lineid = '';

        $wallet_id = trim($data['wallet_id']);

        if ($config->freecredit_all === 'Y') {
            $freecredit = 'Y';
        } else {
            $freecredit = 'N';
        }

        if ($config->verify_open === 'Y') {
            $verify = 'N';
            if ($config->verify_sms === 'Y') {
                $otp = rand(100001, 999999);
            }

        } else {
            $verify = 'Y';
        }

        $validator = Validator::make($data, [
            'acc_no' => [
                'required',
                'digits_between:1,20',
                Rule::unique('members', 'acc_no')->where(function ($query) use ($bank_code) {
                    return $query->where('bank_code', $bank_code);
                }),
            ],
            //            'wallet_id' => [
            //                'required',
            //                Rule::unique('members', 'wallet_id')->where(function ($query) use ($wallet_id) {
            //                    return $query->where('wallet_id', $wallet_id);
            //                })
            //            ],
            'firstname' => 'required|alpha',
            'lastname' => 'required|alpha',
            'password' => 'required|min:6|max:10',
            'password_confirm' => 'min:6|same:password',
            'user_name' => 'required|numeric|unique:members,user_name',
            'wallet_id' => 'required|numeric|unique:members,wallet_id',
            'tel' => 'required|numeric|unique:members,tel',
            'bank' => 'required|numeric',
            //            'g-recaptcha-response' => 'required'
        ]);

        //        dd($validator);
        if ($validator->fails()) {
            //            session()->flash('error', Lang::get('app.register.fail'));
            return $this->sendError(Lang::get('app.register.fail'), 200);

        }

        //        $request->validate([
        //            'acc_no' => 'required|digits_between:1,10|unique:members,acc_no',
        //            'firstname' => 'required|string',
        //            'lastname'  => 'required|string',
        //            'password' => 'required|min:6',
        //            'password_confirm' => 'min:6|same:password',
        //            'tel'   => 'required|numeric|unique:members,user_name',
        //            'bank'   => 'required|numeric',
        //            'lineid'   => 'required|string',
        //            'refer'   => 'required|numeric',
        //            'g-recaptcha-response' => 'required'
        //        ]);

        Event::dispatch('customer.register.before', $data);

        if (! isset($data['upline'])) {
            $upline = 0;
        } else {
            $upline = $data['upline'];
            unset($data['upline']);
        }

        //        $refer = $data['refer'];
        $refer = 0;
        unset($data['refer']);

        $pass = $data['password'];
        //        $pass_confirm = $data['password_confirm'];
        //        unset($data['password_confirm']);
        unset($data['password']);
        $data['firstname'] = strip_tags($data['firstname']);
        $data['lastname'] = strip_tags($data['lastname']);
        $name = $data['firstname'].' '.$data['lastname'];
        if (isset($data['promotion'])) {
            $pro = $data['promotion'];
        } else {
            $pro = 'N';
        }

        //        $param = [
        //            'secret' => config('capcha.secret'),
        //            'response' => $data['g-recaptcha-response']
        //        ];
        //
        //
        //        $captcha_verify_url = "https://www.google.com/recaptcha/api/siteverify";
        //
        //        $response = Http::asForm()->post($captcha_verify_url, $param);
        //
        //
        //        if ($response->failed()) {
        //
        //            session()->flash('error', 'พบข้อผิดพลาดในการตรวจสอบ Captcha');
        //            return redirect()->back();
        //
        //        } elseif ($response->successful()) {
        //            $response = $response->json();
        //
        //            if ($response['success'] !== true) {
        //                session()->flash('error', 'คุณป้อน Captcha ไม่ถูกต้อง');
        //                return redirect()->back();
        //            }
        //        }
        unset($data['g-recaptcha-response']);

        unset($data['bank']);
        if ($bank_code == 4) {
            $acc_check = substr($acc_no, -4);
        } else {
            $acc_check = substr($acc_no, -6);
        }
        $acc_bay = substr($acc_no, -7);

        $data = array_merge($data, [
            'password' => Hash::make($pass),
            'refer_code' => $refer,
            'upline_code' => $upline,
            'user_name' => $username,
            'user_pass' => $pass,
            'wallet_id' => $wallet_id,
            'tel' => $tel,
            'lineid' => $lineid,
            'acc_no' => $acc_no,
            'acc_check' => $acc_check,
            'acc_bay' => $acc_bay,
            'acc_kbank' => '',
            'bank_code' => $bank_code,
            'confirm' => $verify,
            'freecredit' => $freecredit,
            'check_status' => 'N',
            'promotion' => $pro,
            'name' => $name,
            'user_create' => $name,
            'user_update' => $name,
            'lastlogin' => $datenow,
            'date_regis' => $today,
            'birth_day' => $today,
            'session_limit' => null,
            'payment_limit' => null,
            'payment_delay' => null,
            'remark' => '',
            'gender' => 'M',
            'otp' => $otp,
            'ip' => $ip,
        ]);

        $response = app('Gametech\Member\Repositories\MemberRepository')->create($data);

        if (! $response->code) {
            //            session()->flash('error', Lang::get('app.register.fail2'));
            return $this->sendError(Lang::get('app.register.fail2'), 200);
            //            return redirect()->back();
        }

        if ($config->verify_open == 'N') {

            if ($config->seamless == 'Y') {

                $game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y']);
                $member = app('Gametech\Member\Repositories\MemberRepository')->find($response->code);
                $res = app('Gametech\Game\Repositories\GameUserRepository')->addGameUser($game->code, $member->code, ['username' => $username, 'name' => $name, 'user_create' => $name]);
                if ($res['success'] === true) {
                    //                    session()->flash('success', Lang::get('app.register.success'));
                    Auth::guard('customer')->login($response);

                    return $this->sendSuccess(Lang::get('app.register.success'));

                    //                    if ($this->attemptLogin($request)) {
                    //                        return $this->sendLoginResponse($request);
                    //                    }
                    //
                    //                    return $this->sendFailedLoginResponse($request);
                    //                    Auth::guard('customer')->login($response);
                    //                    return redirect()->intended(route($this->_config['redirect']));
                } else {
                    app('Gametech\Member\Repositories\MemberRepository')->delete($response->code);

                    //                    session()->flash('error', $res['msg']);
                    return $this->sendError($res['msg'], 200);
                    //                    return redirect()->back();
                }

            } else {

                if ($config->multigame_open === 'N') {
                    $game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y']);
                    $member = app('Gametech\Member\Repositories\MemberRepository')->find($response->code);
                    $res = app('Gametech\Game\Repositories\GameUserRepository')->addGameUser($game->code, $member->code, $member);

                    if ($res['success'] === true) {
                        session()->flash('success', 'สมัครสมาชิกสำเร็จแล้ว ยินดีต้อนรับเข้าสู่ระบบ');
                        Auth::guard('customer')->login($response);

                        return redirect()->intended(route($this->_config['redirect']));
                    } else {
                        app('Gametech\Member\Repositories\MemberRepository')->delete($response->code);
                        session()->flash('error', $res['msg']);

                        return redirect()->back();
                    }
                } else {
                    session()->flash('success', 'สมัครสมาชิกสำเร็จแล้ว ยินดีต้อนรับเข้าสู่ระบบ');
                    Auth::guard('customer')->login($response);

                    return redirect()->intended(route($this->_config['redirect']));

                }
            }
        } else {

            session()->flash('success', 'ขณะนี้ข้อมูลการสมัครของท่าน อยู่ในกระบวนการตรวจสอบโดยทีมงาน เมื่อทีมงานดำเนินการเสร็จ ท่านสมาชิกจะสามารถเข้าสู่ระบบของเวบไซต์ได้');
            if ($config->verify_sms === 'Y') {
                return redirect()->route($this->_config['redirect'])->withInput(['user_name' => $username, 'password' => $pass]);
            } else {
                return redirect()->back();
            }

        }

    }

    public function register_11(Request $request): RedirectResponse
    {
        $otp = '';
        $config = core()->getConfigData();

        $datenow = now()->toDateTimeString();
        $today = now()->toDateString();
        $ip = $request->ip();
        //        $data = $request->input();

        $data = $request->all();
        $tel = Str::of($data['tel'])->replaceMatches('/[^0-9]++/', '')->trim()->__toString();

        $data['tel'] = $tel;

        $data['user_name'] = strtolower($data['user_name']);
        $username = $data['user_name'];
        $acc_no = Str::of($data['acc_no'])->replaceMatches('/[^0-9]++/', '')->trim()->__toString();
        $data['acc_no'] = $acc_no;
        $bank_code = $data['bank'];
        if ($data['wallet_id'] == '') {
            $data['wallet_id'] = $data['tel'];
        }

        $lineid = trim($data['lineid']);

        $wallet_id = trim($data['wallet_id']);

        if ($config->freecredit_all === 'Y') {
            $freecredit = 'Y';
        } else {
            $freecredit = 'N';
        }

        if ($config->verify_open === 'Y') {
            $verify = 'N';
            if ($config->verify_sms === 'Y') {
                $otp = rand(100001, 999999);
            }

        } else {
            $verify = 'Y';
        }

        $validator = Validator::make($data, [
            'acc_no' => [
                'required',
                'digits_between:1,20',
                Rule::unique('members', 'acc_no')->where(function ($query) use ($bank_code) {
                    return $query->where('bank_code', $bank_code);
                }),
            ],
            //            'wallet_id' => [
            //                'required',
            //                Rule::unique('members', 'wallet_id')->where(function ($query) use ($wallet_id) {
            //                    return $query->where('wallet_id', $wallet_id);
            //                })
            //            ],
            'firstname' => 'required|alpha',
            'lastname' => 'required|alpha',
            'password' => 'required|min:6',
            'password_confirm' => 'min:6|same:password',
            'wallet_id' => 'required|numeric|unique:members,wallet_id',
            'tel' => 'required|numeric|unique:members,tel',
            'user_name' => 'required|alpha_num|different:tel|unique:members,user_name|max:10|regex:/^[a-z][a-z0-9]*$/',
            'bank' => 'required|numeric',
            'refer' => 'required|numeric',
            //            'g-recaptcha-response' => 'required'
        ]);

        //        dd($validator);
        if ($validator->fails()) {
            session()->flash('error', Lang::get('app.register.fail'));

            return redirect()->back()->withErrors($validator)->withInput();

        }

        //        $request->validate([
        //            'acc_no' => 'required|digits_between:1,10|unique:members,acc_no',
        //            'firstname' => 'required|string',
        //            'lastname'  => 'required|string',
        //            'password' => 'required|min:6',
        //            'password_confirm' => 'min:6|same:password',
        //            'tel'   => 'required|numeric|unique:members,user_name',
        //            'bank'   => 'required|numeric',
        //            'lineid'   => 'required|string',
        //            'refer'   => 'required|numeric',
        //            'g-recaptcha-response' => 'required'
        //        ]);

        Event::dispatch('customer.register.before', $data);

        if (! isset($data['upline'])) {
            $upline = 0;
        } else {
            $upline = $data['upline'];
            unset($data['upline']);
        }

        $refer = $data['refer'];
        unset($data['refer']);

        $pass = $data['password'];
        $pass_confirm = $data['password_confirm'];
        unset($data['password_confirm']);
        unset($data['password']);

        $name = $data['firstname'].' '.$data['lastname'];
        if (isset($data['promotion'])) {
            $pro = $data['promotion'];
        } else {
            $pro = 'N';
        }

        //        $param = [
        //            'secret' => config('capcha.secret'),
        //            'response' => $data['g-recaptcha-response']
        //        ];
        //
        //
        //        $captcha_verify_url = "https://www.google.com/recaptcha/api/siteverify";
        //
        //        $response = Http::asForm()->post($captcha_verify_url, $param);
        //
        //
        //        if ($response->failed()) {
        //
        //            session()->flash('error', 'พบข้อผิดพลาดในการตรวจสอบ Captcha');
        //            return redirect()->back();
        //
        //        } elseif ($response->successful()) {
        //            $response = $response->json();
        //
        //            if ($response['success'] !== true) {
        //                session()->flash('error', 'คุณป้อน Captcha ไม่ถูกต้อง');
        //                return redirect()->back();
        //            }
        //        }
        unset($data['g-recaptcha-response']);

        unset($data['bank']);
        if ($bank_code == 4) {
            $acc_check = substr($acc_no, -4);
        } else {
            $acc_check = substr($acc_no, -6);
        }
        $acc_bay = substr($acc_no, -7);

        $data = array_merge($data, [
            'password' => Hash::make($pass),
            'refer_code' => $refer,
            'upline_code' => $upline,
            'user_name' => $username,
            'user_pass' => $pass,
            'wallet_id' => $wallet_id,
            'tel' => $tel,
            'lineid' => $lineid,
            'acc_no' => $acc_no,
            'acc_check' => $acc_check,
            'acc_bay' => $acc_bay,
            'acc_kbank' => '',
            'bank_code' => $bank_code,
            'confirm' => $verify,
            'freecredit' => $freecredit,
            'check_status' => 'N',
            'promotion' => $pro,
            'name' => $name,
            'user_create' => $name,
            'user_update' => $name,
            'lastlogin' => $datenow,
            'date_regis' => $today,
            'birth_day' => $today,
            'session_limit' => null,
            'payment_limit' => null,
            'payment_delay' => null,
            'remark' => '',
            'gender' => 'M',
            'otp' => $otp,
            'ip' => $ip,
        ]);

        $response = app('Gametech\Member\Repositories\MemberRepository')->create($data);

        if (! $response->code) {
            session()->flash('error', Lang::get('app.register.fail2'));

            return redirect()->back();
        }

        if ($config->verify_open == 'N') {

            if ($config->seamless == 'Y') {

                $game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y']);
                $member = app('Gametech\Member\Repositories\MemberRepository')->find($response->code);
                $res = app('Gametech\Game\Repositories\GameUserRepository')->addGameUser($game->code, $member->code, ['username' => $username, 'name' => $name, 'user_create' => $name]);
                if ($res['success'] === true) {
                    session()->flash('success', Lang::get('app.register.success'));

                    if ($this->attemptLogin($request)) {
                        return $this->sendLoginResponse($request);
                    }

                    return $this->sendFailedLoginResponse($request);
                    //                    Auth::guard('customer')->login($response);
                    //                    return redirect()->intended(route($this->_config['redirect']));
                } else {
                    app('Gametech\Member\Repositories\MemberRepository')->delete($response->code);
                    session()->flash('error', $res['msg']);

                    return redirect()->back();
                }

            } else {

                if ($config->multigame_open === 'N') {
                    $game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y']);
                    $member = app('Gametech\Member\Repositories\MemberRepository')->find($response->code);
                    $res = app('Gametech\Game\Repositories\GameUserRepository')->addGameUser($game->code, $member->code, $member);

                    if ($res['success'] === true) {
                        session()->flash('success', 'สมัครสมาชิกสำเร็จแล้ว ยินดีต้อนรับเข้าสู่ระบบ');
                        Auth::guard('customer')->login($response);

                        return redirect()->intended(route($this->_config['redirect']));
                    } else {
                        app('Gametech\Member\Repositories\MemberRepository')->delete($response->code);
                        session()->flash('error', $res['msg']);

                        return redirect()->back();
                    }
                } else {
                    session()->flash('success', 'สมัครสมาชิกสำเร็จแล้ว ยินดีต้อนรับเข้าสู่ระบบ');
                    Auth::guard('customer')->login($response);

                    return redirect()->intended(route($this->_config['redirect']));

                }
            }
        } else {

            session()->flash('success', 'ขณะนี้ข้อมูลการสมัครของท่าน อยู่ในกระบวนการตรวจสอบโดยทีมงาน เมื่อทีมงานดำเนินการเสร็จ ท่านสมาชิกจะสามารถเข้าสู่ระบบของเวบไซต์ได้');
            if ($config->verify_sms === 'Y') {
                return redirect()->route($this->_config['redirect'])->withInput(['user_name' => $username, 'password' => $pass]);
            } else {
                return redirect()->back();
            }

        }

    }

    public function register_(Request $request): RedirectResponse
    {
        $otp = '';
        $config = core()->getConfigData();

        $datenow = now()->toDateTimeString();
        $today = now()->toDateString();
        $ip = $request->ip();
        //        $data = $request->input();

        $data = $request->all();
        $tel = Str::of($data['tel'])->replaceMatches('/[^0-9]++/', '')->trim()->__toString();

        $data['tel'] = $tel;

        $data['user_name'] = strtolower($data['user_name']);
        $username = $data['user_name'];
        $acc_no = Str::of($data['acc_no'])->replaceMatches('/[^0-9]++/', '')->trim()->__toString();
        $data['acc_no'] = $acc_no;
        $bank_code = $data['bank'];
        if ($data['wallet_id'] == '') {
            $data['wallet_id'] = $data['tel'];
        }

        $wallet_id = trim($data['wallet_id']);
        $lineid = trim($data['lineid']);

        if ($config->freecredit_all === 'Y') {
            $freecredit = 'Y';
        } else {
            $freecredit = 'N';
        }

        if ($config->verify_open === 'Y') {
            $verify = 'N';
            if ($config->verify_sms === 'Y') {
                $otp = rand(100001, 999999);
            }

        } else {
            $verify = 'Y';
        }

        $validator = Validator::make($data, [
            'acc_no' => [
                'required',
                'digits_between:1,14',
                Rule::unique('members', 'acc_no')->where(function ($query) use ($bank_code) {
                    return $query->where('bank_code', $bank_code);
                }),
            ],
            'wallet_id' => [
                'required',
                Rule::unique('members', 'wallet_id')->where(function ($query) use ($wallet_id) {
                    return $query->where('wallet_id', $wallet_id);
                }),
            ],
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'password' => 'required|min:6',
            'password_confirm' => 'min:6|same:password',
            'tel' => 'required|numeric|unique:members,tel',
            'user_name' => 'required|string|different:tel|unique:members,user_name|max:10|regex:/^[a-z][a-z0-9]*$/',
            'bank' => 'required|numeric',
            //            'lineid' => 'required|alpha_dash',
            'refer' => 'required|numeric',
            'g-recaptcha-response' => 'required',
        ]);

        if ($validator->fails()) {
            session()->flash('error', 'โปรดตรวจสอบข้อมูลให้ถูกต้องก่อนกด สมัครสมาชิก');

            return redirect()->back()->withErrors($validator)->withInput();

        }

        //        $request->validate([
        //            'acc_no' => 'required|digits_between:1,10|unique:members,acc_no',
        //            'firstname' => 'required|string',
        //            'lastname'  => 'required|string',
        //            'password' => 'required|min:6',
        //            'password_confirm' => 'min:6|same:password',
        //            'tel'   => 'required|numeric|unique:members,user_name',
        //            'bank'   => 'required|numeric',
        //            'lineid'   => 'required|string',
        //            'refer'   => 'required|numeric',
        //            'g-recaptcha-response' => 'required'
        //        ]);

        Event::dispatch('customer.register.before', $data);

        if (! isset($data['upline'])) {
            $upline = 0;
        } else {
            $upline = $data['upline'];
            unset($data['upline']);
        }

        $refer = $data['refer'];
        unset($data['refer']);

        $pass = $data['password'];
        $pass_confirm = $data['password_confirm'];
        unset($data['password_confirm']);
        unset($data['password']);

        $name = $data['firstname'].' '.$data['lastname'];
        if (isset($data['promotion'])) {
            $pro = $data['promotion'];
        } else {
            $pro = 'N';
        }

        //        $param = [
        //            'secret' => config('capcha.secret'),
        //            'response' => $data['g-recaptcha-response']
        //        ];
        //
        //
        //        $captcha_verify_url = "https://www.google.com/recaptcha/api/siteverify";
        //
        //        $response = Http::asForm()->post($captcha_verify_url, $param);
        //
        //
        //        if ($response->failed()) {
        //
        //            session()->flash('error', 'พบข้อผิดพลาดในการตรวจสอบ Captcha');
        //            return redirect()->back();
        //
        //        } elseif ($response->successful()) {
        //            $response = $response->json();
        //
        //            if ($response['success'] != true) {
        //                session()->flash('error', 'คุณป้อน Captcha ไม่ถูกต้อง');
        //                return redirect()->back();
        //            }
        //        }
        unset($data['g-recaptcha-response']);

        unset($data['bank']);
        if ($bank_code == 4) {
            $acc_check = substr($acc_no, -4);
        } else {
            $acc_check = substr($acc_no, -6);
        }
        $acc_bay = substr($acc_no, -7);

        $data = array_merge($data, [
            'password' => Hash::make($pass),
            'refer_code' => $refer,
            'upline_code' => $upline,
            'user_name' => $username,
            'user_pass' => $pass,
            'wallet_id' => $wallet_id,
            'tel' => $tel,
            'lineid' => $lineid,
            'acc_no' => $acc_no,
            'acc_check' => $acc_check,
            'acc_bay' => $acc_bay,
            'acc_kbank' => '',
            'bank_code' => $bank_code,
            'confirm' => $verify,
            'freecredit' => $freecredit,
            'check_status' => 'N',
            'promotion' => $pro,
            'name' => $name,
            'user_create' => $name,
            'user_update' => $name,
            'lastlogin' => $datenow,
            'date_regis' => $today,
            'birth_day' => $today,
            'session_limit' => null,
            'payment_limit' => null,
            'payment_delay' => null,
            'remark' => '',
            'gender' => 'M',
            'otp' => $otp,
            'ip' => $ip,
        ]);

        $response = app('Gametech\Member\Repositories\MemberRepository')->create($data);

        if (! $response->code) {
            session()->flash('error', 'พบข้อผิดพลาด ไม่สามารถบันทึกบ้อมูลได้');

            return redirect()->back();
        }

        if ($config->verify_open == 'N') {

            if ($config->seamless == 'Y') {

                $game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y']);
                $member = app('Gametech\Member\Repositories\MemberRepository')->find($response->code);
                $res = app('Gametech\Game\Repositories\GameUserRepository')->addGameUser($game->code, $member->code, ['username' => $username, 'product_id' => 'PGSOFT', 'user_create' => $username]);
                if ($res['success'] == true) {
                    session()->flash('success', 'สมัครสมาชิกสำเร็จแล้ว ยินดีต้อนรับเข้าสู่ระบบ');

                    if ($this->attemptLogin($request)) {
                        return $this->sendLoginResponse($request);
                    }

                    return $this->sendFailedLoginResponse($request);
                    //                    Auth::guard('customer')->login($response);
                    //                    return redirect()->intended(route($this->_config['redirect']));
                } else {
                    app('Gametech\Member\Repositories\MemberRepository')->delete($response->code);
                    session()->flash('error', $res['msg']);

                    return redirect()->back();
                }

            } else {

                if ($config->multigame_open == 'N') {
                    $game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y']);
                    $member = app('Gametech\Member\Repositories\MemberRepository')->find($response->code);
                    $members = collect($member)->toArray();
                    $members['user_pass'] = $pass;
                    $res = app('Gametech\Game\Repositories\GameUserRepository')->addGameUser($game->code, $member->code, $members);

                    if ($res['success'] === true) {
                        session()->flash('success', 'สมัครสมาชิกสำเร็จแล้ว ยินดีต้อนรับเข้าสู่ระบบ');
                        Auth::guard('customer')->login($response);

                        return redirect()->intended(route($this->_config['redirect']));
                    } else {
                        app('Gametech\Member\Repositories\MemberRepository')->delete($response->code);
                        session()->flash('error', $res['msg']);

                        return redirect()->back();
                    }
                } else {
                    session()->flash('success', 'สมัครสมาชิกสำเร็จแล้ว ยินดีต้อนรับเข้าสู่ระบบ');
                    Auth::guard('customer')->login($response);

                    return redirect()->intended(route($this->_config['redirect']));

                }
            }
        } else {

            session()->flash('success', 'ขณะนี้ข้อมูลการสมัครของท่าน อยู่ในกระบวนการตรวจสอบโดยทีมงาน เมื่อทีมงานดำเนินการเสร็จ ท่านสมาชิกจะสามารถเข้าสู่ระบบของเวบไซต์ได้');
            if ($config->verify_sms === 'Y') {
                return redirect()->route($this->_config['redirect'])->withInput(['user_name' => $username, 'password' => $pass]);
            } else {
                return redirect()->back();
            }

        }

    }

    public function download()
    {
        $games = $this->loadGame();

        return view($this->_config['view'], compact('games'));
    }

    public function loadGame(): array
    {
        $responses = [];

        $results = collect(app('Gametech\Game\Repositories\GameRepository')->getGameUserById($this->id(), false)->toArray());

        foreach ($results as $i => $result) {
            $responses[strtolower($result['game_type'])][$i] = $result;
            $responses[strtolower($result['game_type'])][$i]['image'] = Storage::url('game_img/'.$result['filepic']);
        }

        return $responses;

    }

    public function checkAcc(Request $request)
    {
        $bankid = $request->input('bankid');
        $accno = $request->input('accno');
        $bank_code = 1;
        $firstname = '';
        $lastname = '';

        $data['acc_no'] = $accno;

        switch ($bankid) {
            case '2':
                $bank_code = 1;
                break;
            case '1':
                $bank_code = 2;
                break;
            case '3':
                $bank_code = 3;
                break;
            case '10':
                $bank_code = 4;
                break;
            case '4':
                $bank_code = 5;
                break;
            case '11':
                $bank_code = 6;
                break;
            case '15':
                $bank_code = 7;
                break;
            case '6':
                $bank_code = 8;
                break;
            case '7':
                $bank_code = 9;
                break;
            case '9':
                $bank_code = 10;
                break;
            case '12':
                $bank_code = 11;
                break;
            case '13':
                $bank_code = 13;
                break;
            case '14':
                $bank_code = 14;
                break;
            case '5':
                $bank_code = 15;
                break;
            case '8':
                $bank_code = 16;
                break;
            case '17':
                $bank_code = 17;
                break;

            default:
                $ret['status'] = 0;

                return $this->sendResponseNew($ret, 'Complete');
        }

        $param = [
            'destinationbankid' => $bank_code,
            'destinationaccnumber' => $accno,
        ];

        $response = Http::asForm()->post('https://ks.pg-game888.com/web/api_ckaccname/sbo', $param);
        if ($response->successful()) {
            $response = $response->json();
            if ($response['status'] == 1000) {

                $validator = Validator::make($data, [
                    'acc_no' => [
                        'required',
                        'digits_between:1,15',
                        Rule::unique('members', 'acc_no')->where(function ($query) use ($bankid) {
                            return $query->where('bank_code', $bankid);
                        }),
                    ],
                ]);

                $name = $response['name'];
                $name = explode(' ', $name);
                if (count($name) == 2) {

                    //                    $firstname = Str::of($name[0])->after('นาย');
                    $firstname = Str::of($name[0])->after('นาย')->after('นาง')->after('นายสาว')->after('น.ส.')->__toString();
                    $lastname = $name[1];
                } elseif (count($name) == 3) {
                    $firstname = $name[1];
                    $lastname = $name[2];
                } elseif (count($name) == 4) {
                    $firstname = $name[1];
                    $lastname = $name[3];
                }

                $response['firstname'] = $firstname;
                $response['lastname'] = $lastname;

                if ($validator->fails()) {
                    $response['status'] = 0;

                    return $this->sendResponseNew($response, 'มีเลขที่บัญชีนี้ในระบบแล้ว');
                }

                return $this->sendResponseNew($response, 'Complete');
            } else {
                $ret['status'] = 0;

                return $this->sendResponseNew($ret, 'ไม่พบข้อมูลของเลขที่บัญชีนี้');
            }
        }
    }

    public function lang($lang)
    {
        App::setLocale(Session::get('lang'));
        Session::put('lang', $lang);

        return redirect()->intended(route($this->_config['redirect']));
    }

    public function loadlang($locale)
    {
        // อิงภาษา “ที่รองรับ” จาก config/languages.php
        $available = array_keys(Config::get('languages.available', []));
        if (!in_array($locale, $available, true)) {
            $locale = config('app.fallback_locale'); // fallback ถ้าไม่แมตช์
        }

        // โหลด array แปลจาก resources/lang/<locale>/app.php
        $arr = [];
        $phpPath  = resource_path("lang/{$locale}/app.php");
        $jsonPath = resource_path("lang/{$locale}.json"); // เผื่อคุณใช้ Laravel JSON lang

        if (file_exists($phpPath)) {
            $arr = require $phpPath;               // <-- ได้ array คีย์ตรง เช่น ['home'=>['lastupdate'=>...], ...]
        } elseif (file_exists($jsonPath)) {
            $arr = json_decode(file_get_contents($jsonPath), true) ?: [];
        }

        // อย่า wrap เป็น $strings['app'] = ...; เราต้องการรูทตรงสำหรับ trans('home.xxx')
        $flags = JSON_UNESCAPED_UNICODE | (config('app.debug') ? JSON_PRETTY_PRINT : 0);
        $contents = 'window.i18n = ' . json_encode($arr, $flags) . ';';

        $response = Response::make($contents, 200);
        $lastModified = file_exists($phpPath) ? filemtime($phpPath) : (file_exists($jsonPath) ? filemtime($jsonPath) : time());

        return $response
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=300') // ปรับได้ตามต้องการ
            ->header('Last-Modified', gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
    }

    // ใน LoginController
    public function langJson($locale)
    {
        $available = array_keys(Config::get('languages.available', []));
        if (!in_array($locale, $available, true)) {
            $locale = config('app.fallback_locale');
        }

        $phpPath  = resource_path("lang/{$locale}/app.php");
        $jsonPath = resource_path("lang/{$locale}.json");

        if (file_exists($phpPath)) {
            return response()->json(require $phpPath);
        }

        if (file_exists($jsonPath)) {
            return response()->json(json_decode(file_get_contents($jsonPath), true) ?: []);
        }

        return response()->json([], 404);
    }


    public function step01(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'user_name' => 'required|numeric|unique:members,user_name',
        ]);

        if ($validator->fails()) {
            //            session()->flash('error', 'โปรดตรวจสอบข้อมูลให้ถูกต้องก่อนกด สมัครสมาชิก');
            return $this->sendError('เบอร์ที่สมัคร ไม่ถูกต้อง หรือ มีในระบบแล้ว', 200);

        }

        return $this->sendSuccess('pass');
    }

    public function step02(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'password' => 'required|min:4',
            'password_confirm' => 'min:4|same:password',
        ]);

        if ($validator->fails()) {
            //            session()->flash('error', 'โปรดตรวจสอบข้อมูลให้ถูกต้องก่อนกด สมัครสมาชิก');
            return $this->sendError('รหัส ไม่ถูกต้อง โปรดตรวจสอบ', 200);

        }

        return $this->sendSuccess('pass');
    }

    public function checkPhone(Request $request)
    {
        $phone = $request->input('username');

        $data['user_name'] = $phone;
        $data['tel'] = $phone;
        $validator = Validator::make($data, [
            'user_name' => 'required|numeric|unique:members,user_name',
            'tel' => 'required|numeric|unique:members,tel',
        ]);

        if ($validator->fails()) {
            return response()->json(['exists' => true]);
        }



        return response()->json(['exists' => false]);
    }

    public function checkBank(Request $request)
    {
        $bank = $request->input('bank');
        $acc = $request->input('acc_no');
//        $exists = MemberProxy::where('bank_code', $bank)->where('acc_no', $acc)->exists();

//        return response()->json(['valid' => $exists]);

        $data['acc_no'] = $acc;
        $validator = Validator::make($data, [
            'acc_no' => [
                'required',
                'digits_between:1,14',
                Rule::unique('members', 'acc_no')->where(function ($query) use ($bank) {
                    return $query->where('bank_code', $bank);
                }),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['valid' => true]);
        }



        return response()->json(['valid' => false]);
    }

    protected function authenticated(Request $request, $user)
    {
//        $config = core()->getConfigData();

        if ($request->filled('password')) {
            try {
                Auth::guard('customer')->logoutOtherDevices($request->input('password'));
            } catch (\Throwable $e) {
                // บันทึก log แบบเงียบ ไม่ให้ขัด flow
                Log::warning('logoutOtherDevices failed', ['uid' => $user->id, 'err' => $e->getMessage()]);
            }
        }

        Event::dispatch('customer.login.after', $user);

//        if ($config->verify_open === 'Y') {
//
//            if ($config->verify_sms === 'Y') {
//                return redirect()->route('customer.verify.index');
//            } else {
//                session()->flash('success', 'ขณะนี้ข้อมูลการสมัครของท่าน อยู่ในกระบวนการตรวจสอบโดยทีมงาน เมื่อทีมงานดพเนินการเสร็จ ท่านสมาชิกจะสามารถเข้าสู่ระบบของเวบไซต์ได้');
//
//                $this->logout($request);
//            }
//
//        } else {

        app('Gametech\Member\Repositories\MemberRepository')->update(['session_id' => request()->session()->getId()], $user->code);

        return redirect()->intended(
            route($this->_config['redirect'], absolute: false) ?? '/member'
        );
//            return redirect()->intended(route($this->_config['redirect']));
//            return redirect()->intended('/member');
//        }

    }


    public function cats($id, Request $request)
    {
        $type = ['slot' => __('app.home.cat_slot'), 'casino' => __('app.home.cat_casino'), 'sport' => __('app.home.cat_sport'), 'lotto' => __('app.home.cat_lotto'), 'keno' => __('app.home.cat_keno') , 'card' => __('app.home.cat_card'), 'cock' => __('app.home.cat_cock'), 'poker' => __('app.home.cat_poker')];

        $name = $type[$id];
        $type = app('Gametech\Game\Repositories\GameTypeRepository')->findOneByField('id', Str::upper($id));
        $games = app('Gametech\Game\Repositories\GameSeamlessRepository')->orderBy('sort')->findWhere(['game_type' => strtoupper($id), 'status_open' => 'Y', 'enable' => 'Y']);
        $games = collect($games)->map(function ($items) {
            $items['filepic'] = Storage::url('game_img/' . $items->filepic . '?v=' . date('Ym'));
            return (object)$items;

        });

        $gameTypes = app('Gametech\Game\Repositories\GameTypeRepository')->findWhere(['enable' => 'Y', 'status_open' => 'Y']);
        $gameTypes->map(function ($item) {
            $item->icon = Storage::url('icon_cat/'.$item->icon);
            return $item;
        });

        return view($this->_config['view'], compact('games', 'name', 'id', 'type','gameTypes'));
    }

    public function getProviders($type = null)
    {

        $game = core()->getGame();
        $response = app('Gametech\Game\Repositories\GameUserRepository')
            ->providerListSinglePublic($game->id);

        if ($response['success'] === true) {
            $grouped = [];

            foreach ($response['provider'] as $item) {

                if(is_null($item['position']))continue;
                // --- บังคับ/ปรับประเภทตาม prefix และ normalize type ---
                $prefix = strtoupper($item['prefix'] ?? '');
                $types  = $item['gameType'] ?? null;

                // 1) บังคับตาม prefix
                if ($prefix === 'KM') {
                    $types = 'card';
                } elseif (in_array($prefix, ['KP', 'MPOKER'], true)) {
                    $types = 'poker';
                }

                // 2) normalize ชื่อ type เดิม
                if ($types === 'fishing or table_game') {
                    $types = 'fish';
                }

                // ตัดเคสว่างจริง ๆ หลัง override/normalize แล้ว
                if (empty($types)) {
                    continue;
                }

                // อัปเดตกลับเข้า item เพื่อให้ downstream ใช้ type เดียวกัน
                $item['gameType'] = $types;

                // จัดกลุ่ม
                $grouped[$types][] = $item;
            }

            // ระบุ $type → ส่งเฉพาะ group นั้น (หลัง sort)
            if ($type) {
                $target = $grouped[$type] ?? [];
                $sorted = $this->sortProvidersByRules($type, $target);
                $result = [
                    $type => $this->transformProviders($sorted),
                ];
            } else {
                // ไม่ระบุ type → ส่งทุก group แยก key (หลัง sort ราย group)
                $result = [];
                foreach ($grouped as $groupType => $items) {
                    $sorted = $this->sortProvidersByRules($groupType, $items);
                    $result[$groupType] = $this->transformProviders($sorted);
                }
            }

            return $result;
        }

        return null;
    }

    /**
     * กฎการจัดเรียง:
     * - ถ้า group = slot → lobbyId 31 มาก่อนเสมอ
     * - ที่เหลือเรียงตาม position (ASC), ถ้าเท่ากันผูกด้วย lobbyId (ASC)
     * - หากไม่มี position → ดันไปท้ายสุด
     */
    private function sortProvidersByRules(string $groupType, array $items): array
    {
        usort($items, function ($a, $b) use ($groupType) {
            $isSlot = strtolower($groupType) === 'slot';

            $aLobby = (int)($a['lobbyId'] ?? 0);
            $bLobby = (int)($b['lobbyId'] ?? 0);

            if ($isSlot) {
                if ($aLobby === 31 && $bLobby !== 31) return -1;
                if ($bLobby === 31 && $aLobby !== 31) return 1;
            }

            $pa = $a['position'] ?? PHP_INT_MAX;
            $pb = $b['position'] ?? PHP_INT_MAX;

            // กันกรณี position เป็น string ตัวเลข
            if (is_string($pa) && ctype_digit($pa)) $pa = (int)$pa;
            if (is_string($pb) && ctype_digit($pb)) $pb = (int)$pb;

            if ($pa === $pb) {
                return $aLobby <=> $bLobby;
            }
            return $pa <=> $pb;
        });

        return $items;
    }


    private function transformProviders(array $items): array
    {
        return array_map(function ($item) {
            return [
                'provider' => $item['lobbyId'],
                'providerTier' => 'vvip',
                'providerName' => $item['lobbyName'],
                'providerType' => $item['gameType'],
                'logoURL' => 'https://frontgame.sgp1.digitaloceanspaces.com/2022theme/provider/' . strtolower($item['prefix']) . '.jpg',
                'logoTransparentURL' => 'https://frontgame.sgp1.digitaloceanspaces.com/2022theme/provider/' . strtolower($item['prefix']) . '.jpg',
                'status' => $item['maintainance'] === false ? 'ACTIVE' : 'INACTIVE',
                'detailStatus' => 'Y',
                'gameList' => $item['gameList'],
                'maintainance' => $item['maintainance'],
                'prefix' => $item['prefix'],
            ];
        }, $items);
    }





}
