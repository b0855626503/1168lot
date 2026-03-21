<?php

namespace Gametech\Marketing\Http\Controllers\Customer;

use App\Http\Controllers\AppBaseController;
use App\Providers\RouteServiceProvider;
use Exception;
use Gametech\Marketing\Repositories\MarketingCampaignRepository;
use Gametech\Marketing\Repositories\MarketingTeamRepository;
use Gametech\Marketing\Repositories\RegistrationLinkClickRepository;
use Gametech\Marketing\Repositories\RegistrationLinkRepository;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MarketingController extends AppBaseController
{
    use AuthenticatesUsers;

    protected $_config;

    protected $redirectTo = RouteServiceProvider::HOME;

    protected $marketingCampaignRepository;

    protected $registerLinkRepository;

    protected $marketingTeamRepository;

    protected $registrationLinkClickRepository;

    public function __construct(
        MarketingCampaignRepository     $marketingCampaignRepository,
        MarketingTeamRepository         $marketingTeamRepository,
        RegistrationLinkRepository      $registerLinkRepository,
        RegistrationLinkClickRepository $registrationLinkClickRepository
    )
    {
        $this->_config = request('_config');

        $this->middleware('guest');

        $this->marketingCampaignRepository = $marketingCampaignRepository;

        $this->registerLinkRepository = $registerLinkRepository;

        $this->marketingTeamRepository = $marketingTeamRepository;

        $this->registrationLinkClickRepository = $registrationLinkClickRepository;

    }

    public function store(Request $request, $id = null, $refer = null)
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.home.index'); // หรือหน้าอื่นที่ต้องการ
        }
        // ถ้าไม่มี ID แต่มี session => redirect ไป register/{code}
        if (!$id && session()->has('marketing_id')) {
            $urlRefer = session()->get('marketing_refer') ? '/' . session()->get('marketing_refer') : '';
            return redirect()->route('customer.session.store', ['id' => session('marketing_id'), 'refer' => $urlRefer]);
        }

        // ถ้ายังไม่มีโค้ดหลังจากนั้น = ไม่มี session และไม่มี query
        if (!$id) {
            $banks = app('Gametech\Payment\Repositories\BankRepository')->findWhere([
                'enable' => 'Y',
                'show_regis' => 'Y',
                ['code', '<>', 0],
            ]);

            $refers = app('Gametech\Core\Repositories\ReferRepository')->findWhere([
                'enable' => 'Y',
                ['code', '<>', 0],
            ]);

            // แสดง view
            return view('wallet::customer.marketing.store', compact('banks', 'refers', 'id', 'refer'));
        }

        // ค้นหา register link
        $data = $this->registerLinkRepository->findOneWhere(['code' => $id]);
        if (!$data) {
            return redirect()->route('customer.home.index');
        }

        if ($data->campaign_id != null && $data->load('campaign')->campaign->is_ended) {
            return redirect()->route('customer.home.index');
        }

        $userAgent = $request->userAgent();
        if (Str::contains(strtolower($userAgent), ['bot', 'crawl', 'spider'])) {
            return redirect()->route('customer.home.index');
        }

        $ip = $request->ip();
        $redisKey = "reg_click:{$data->id}:{$ip}";
        $ttlSeconds = 30 * 60;

        try {
            if (!Redis::connection('session')->get($redisKey)) {
                $this->registrationLinkClickRepository->create([
                    'registration_link_id' => $data->id,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'referrer' => $request->headers->get('referer'),
                    'created_at' => now(),
                ]);
                Redis::connection('session')->setex($redisKey, $ttlSeconds, now()->toDateTimeString());
            }
        } catch (Exception $e) {
            logger()->warning('Failed to log registration click: ' . $e->getMessage());
        }

        // เก็บโค้ดใน session เผื่อ reload
        session(['marketing_code' => $id]);
        if ($refer) {
            session(['marketing_refer' => $refer]);
        } else {
            session()->forget('marketing_refer');
        }

        // โหลดข้อมูล bank / refer
        $banks = app('Gametech\Payment\Repositories\BankRepository')->findWhere([
            'enable' => 'Y',
            'show_regis' => 'Y',
            ['code', '<>', 0],
        ]);

        $refers = app('Gametech\Core\Repositories\ReferRepository')->findWhere([
            'enable' => 'Y',
            ['code', '<>', 0],
        ]);


        if ($refer) {
            $refer = $refer->code;
        } else {
            $refer = null;
        }


        // แสดง view
        return view('wallet::customer.marketing.store', compact('banks', 'refers', 'id', 'refer'));
    }

    protected function guard()
    {
        return Auth::guard('customer');
    }

    public function storeUser(Request $request, $id = null, $refer = null)
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.home.index'); // หรือหน้าอื่นที่ต้องการ
        }
        // ถ้าไม่มี ID แต่มี session => redirect ไป register/{code}
        if (!$id && session()->has('marketing_id')) {
            $urlRefer = session()->get('marketing_refer') ? '/' . session()->get('marketing_refer') : '';
            return redirect()->route('customer.session.store', ['id' => session('marketing_id'), 'refer' => $urlRefer]);
        }

        // ถ้ายังไม่มีโค้ดหลังจากนั้น = ไม่มี session และไม่มี query
        if (!$id) {
            $banks = app('Gametech\Payment\Repositories\BankRepository')->findWhere([
                'enable' => 'Y',
                'show_regis' => 'Y',
                ['code', '<>', 0],
            ]);

            $refers = app('Gametech\Core\Repositories\ReferRepository')->findWhere([
                'enable' => 'Y',
                ['code', '<>', 0],
            ]);

            // แสดง view
            return view('wallet::customer.marketing.register_username', compact('banks', 'refers', 'id', 'refer'));
        }

        // ค้นหา register link
        $data = $this->registerLinkRepository->findOneWhere(['code' => $id]);
        if (!$data) {
            return redirect()->route('customer.home.index');
        }

        if ($data->campaign_id != null && $data->load('campaign')->campaign->is_ended) {
            return redirect()->route('customer.home.index');
        }

        $userAgent = $request->userAgent();
        if (Str::contains(strtolower($userAgent), ['bot', 'crawl', 'spider'])) {
            return redirect()->route('customer.home.index');
        }

        $ip = $request->ip();
        $redisKey = "reg_click:{$data->id}:{$ip}";
        $ttlSeconds = 30 * 60;

        try {
            if (!Redis::connection('session')->get($redisKey)) {
                $this->registrationLinkClickRepository->create([
                    'registration_link_id' => $data->id,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'referrer' => $request->headers->get('referer'),
                    'created_at' => now(),
                ]);
                Redis::connection('session')->setex($redisKey, $ttlSeconds, now()->toDateTimeString());
            }
        } catch (Exception $e) {
            logger()->warning('Failed to log registration click: ' . $e->getMessage());
        }

        // เก็บโค้ดใน session เผื่อ reload
        session(['marketing_code' => $id]);
        if ($refer) {
            session(['marketing_refer' => $refer]);
        } else {
            session()->forget('marketing_refer');
        }

        // โหลดข้อมูล bank / refer
        $banks = app('Gametech\Payment\Repositories\BankRepository')->findWhere([
            'enable' => 'Y',
            'show_regis' => 'Y',
            ['code', '<>', 0],
        ]);

        $refers = app('Gametech\Core\Repositories\ReferRepository')->findWhere([
            'enable' => 'Y',
            ['code', '<>', 0],
        ]);


        if ($refer) {
            $refer = $refer->code;
        } else {
            $refer = null;
        }


        // แสดง view
        return view('wallet::customer.marketing.register_username', compact('banks', 'refers', 'id', 'refer'));
    }


    public function staff(Request $request, $id = null, $refer = null)
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.home.index'); // หรือหน้าอื่นที่ต้องการ
        }
        // ถ้าไม่มี ID แต่มี session => redirect ไป register/{code}
        if (!$id && session()->has('marketing_id')) {
            $urlRefer = session()->get('marketing_refer') ? '/' . session()->get('marketing_refer') : '';
            return redirect()->route('customer.session.store', ['id' => session('marketing_id'), 'refer' => $urlRefer]);
        }

        // ถ้ายังไม่มีโค้ดหลังจากนั้น = ไม่มี session และไม่มี query
        if (!$id) {
            $banks = app('Gametech\Payment\Repositories\BankRepository')->findWhere([
                'enable' => 'Y',
                'show_regis' => 'Y',
                ['code', '<>', 0],
            ]);

            $refers = app('Gametech\Core\Repositories\ReferRepository')->findWhere([
                'enable' => 'Y',
                ['code', '<>', 0],
            ]);

            // แสดง view
            return view('wallet::customer.marketing.staff', compact('banks', 'refers', 'id', 'refer'));
        }

        // ค้นหา register link
        $data = $this->registerLinkRepository->findOneWhere(['code' => $id]);
        if (!$data) {
            return redirect()->route('customer.home.index');
        }

        if ($data->campaign_id != null && $data->load('campaign')->campaign->is_ended) {
            return redirect()->route('customer.home.index');
        }

        $userAgent = $request->userAgent();
        if (Str::contains(strtolower($userAgent), ['bot', 'crawl', 'spider'])) {
            return redirect()->route('customer.home.index');
        }

        $ip = $request->ip();
        $redisKey = "reg_click:{$data->id}:{$ip}";
        $ttlSeconds = 30 * 60;

        try {
            if (!Redis::connection('session')->get($redisKey)) {
                $this->registrationLinkClickRepository->create([
                    'registration_link_id' => $data->id,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'referrer' => $request->headers->get('referer'),
                    'created_at' => now(),
                ]);
                Redis::connection('session')->setex($redisKey, $ttlSeconds, now()->toDateTimeString());
            }
        } catch (Exception $e) {
            logger()->warning('Failed to log registration click: ' . $e->getMessage());
        }

        // เก็บโค้ดใน session เผื่อ reload
        session(['marketing_code' => $id]);
        if ($refer) {
            session(['marketing_refer' => $refer]);
        } else {
            session()->forget('marketing_refer');
        }

        // โหลดข้อมูล bank / refer
        $banks = app('Gametech\Payment\Repositories\BankRepository')->findWhere([
            'enable' => 'Y',
            'show_regis' => 'Y',
            ['code', '<>', 0],
        ]);

        $refers = app('Gametech\Core\Repositories\ReferRepository')->findWhere([
            'enable' => 'Y',
            ['code', '<>', 0],
        ]);


        if ($refer) {
            $refer = $refer->code;
        } else {
            $refer = null;
        }


        // แสดง view
        return view('wallet::customer.marketing.staff', compact('banks', 'refers', 'id', 'refer'));
    }


    public function register_staff(Request $request)
    {
        $otp = '';
        $config = $this->getCoreConfig();

        $datenow = now()->toDateTimeString();
        $today = now()->toDateString();
        $ip = $request->ip();
        //        $data = $request->input();

        $data = $request->all();

        $data['user_name'] = Str::of($data['user_name'])->replaceMatches('/[^0-9]++/', '')->trim()->__toString();
        $username = strip_tags($data['user_name']);
        $tel = $username;
        $data['tel'] = $tel;

        $fullname = strip_tags($data['name']);
        $firstname = explode(' ', $fullname);
        $data['firstname'] = $firstname[0];
        if (isset($firstname[1])) {
            if (isset($firstname[2])) {
                $data['lastname'] = $firstname[1] . ' ' . $firstname[2];
            } else {
                $data['lastname'] = $firstname[1];
            }

        } else {
            $data['lastname'] = '';
        }


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


        $rules = [
            'acc_no' => [
                'bail', 'required', 'digits_between:1,14',
                Rule::unique('members', 'acc_no')->where(fn($q) => $q->where('bank_code', $bank_code)),
            ],

            // รองรับอักษรไทย + วรรณยุกต์ + เว้นวรรค/ขีดกลาง
            'firstname' => ['bail', 'required', 'regex:/^[\pL\pM\s\-]+$/u'],
            'lastname' => ['bail', 'required', 'regex:/^[\pL\pM\s\-]+$/u'],

            'password' => ['bail', 'required', 'string', 'min:4', 'max:10'],

            // ใช้ regex แทน numeric เพื่อกัน space/สัญลักษณ์ แถมไม่ cast ค่า
            'user_name' => ['bail', 'required', 'regex:/^\d+$/', 'unique:members,user_name'],
            'wallet_id' => ['bail', 'required', 'regex:/^\d+$/', 'unique:members,wallet_id'],

            // เบอร์ไทย 10 หลัก ขึ้นต้น 0 ชัดเจน
            'tel' => ['bail', 'required', 'regex:/^0\d{9}$/', 'unique:members,tel'],

            'bank' => ['bail', 'required', 'integer'],
            'refer' => ['bail', 'required', 'integer'],
        ];

        $messages = [
            'firstname.regex' => 'ชื่อต้องเป็นตัวอักษร (รองรับไทย) และอาจมีช่องว่าง/ขีดกลาง',
            'lastname.regex' => 'นามสกุลต้องเป็นตัวอักษร (รองรับไทย) และอาจมีช่องว่าง/ขีดกลาง',
            'tel.regex' => 'เบอร์โทรต้องขึ้นต้นด้วย 0 และมี 10 หลัก',
            'user_name.regex' => 'บัญชีผู้ใช้ต้องเป็นตัวเลขล้วน',
            'wallet_id.regex' => 'Wallet ID ต้องเป็นตัวเลขล้วน',
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
//            Log::channel('cashback')->info('validate fafil' , [ 'validate' => $validator  , 'data' => $data ]);
//            Log::channel('cashback')->info('validate error', $validator->errors()->toArray());

            session()->flash('error', Lang::get('app.register.fail'));

            return redirect()->back()->withErrors($validator)->withInput();

        }
        Event::dispatch('customer.register.before', $data);

        if (!isset($data['upline'])) {
            $upline = 0;
        } else {
            $upline = $data['upline'];
            unset($data['upline']);
        }

        $team_id = null;
        $campaign_id = null;
        if (isset($data['marketing'])) {
            $marketing = $this->registerLinkRepository->findOneWhere(['code' => $data['marketing']]);
            if ($marketing) {
                $team_id = $marketing->team_id;
                $campaign_id = $marketing->campaign_id;
            }
            unset($data['marketing']);
        }

        $refer = $data['refer'];
        unset($data['refer']);

        $pass = $data['password'];
        //        $pass_confirm = $data['password_confirm'];
        //        unset($data['password_confirm']);
        unset($data['password']);
        $data['firstname'] = strip_tags($data['firstname']);
        $data['lastname'] = strip_tags($data['lastname']);
        $name = $data['firstname'] . ' ' . $data['lastname'];
        if (isset($data['promotion'])) {
            $pro = $data['promotion'];
        } else {
            $pro = 'N';
        }

        unset($data['g-recaptcha-response']);

        unset($data['bank']);
        if ($bank_code == 4) {
            $acc_check = substr($acc_no, -4);
        } else {
            $acc_check = substr($acc_no, -6);
        }
        $acc_bay = substr($acc_no, -7);

        $username = trim($username);
        $pass = trim($pass);

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
            'team_id' => $team_id,
            'campaign_id' => $campaign_id,
            'otp' => $otp,
            'ip' => $ip,
        ]);

        $response = app('Gametech\Marketing\Repositories\MarketingMemberRepository')->create($data);
        Log::channel('cashback')->info('Register', ['data' => $response]);
        if (!$response->code) {

            session()->flash('error', Lang::get('app.register.fail2'));

            return redirect()->back();
        }

        if ($config->verify_open == 'N') {

            if ($config->seamless == 'Y') {

                session()->flash('success', Lang::get('app.register.success'));
                if ($this->attemptLogin($request)) {
                    return $this->sendLoginResponse($request);
                }

                return $this->sendFailedLoginResponse($request);

//					$game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y', 'id' => 'seamless']);
//					$member = app('Gametech\Member\Repositories\MemberRepository')->find($response->code);
//					$res = app('Gametech\Game\Repositories\GameUserRepository')->addGameUser($game->code, $member->code, ['username' => $username, 'password' => $pass, 'name' => $name, 'user_create' => $name]);
//					if ($res['success'] === true) {
//
//						session()->flash('success', Lang::get('app.register.success'));
//						if ($this->attemptLogin($request)) {
//							return $this->sendLoginResponse($request);
//						}
//
//						return $this->sendFailedLoginResponse($request);
//
//					} else {
//						app('Gametech\Member\Repositories\MemberRepository')->delete($response->code);
//
//						if ($request->expectsJson() || $request->ajax()) {
//							return $this->sendError($res['msg'], 200);
//						}
//
//						session()->flash('error', $res['msg']);
//
//						return redirect()->back();
//					}

            } else {

                if ($config->multigame_open === 'N') {
                    $game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y']);
                    $member = app('Gametech\Member\Repositories\MemberRepository')->find($response->code);
                    $res = app('Gametech\Game\Repositories\GameUserRepository')->addGameUser($game->code, $member->code, $member);

                    if ($res['success'] === true) {

                        session()->flash('success', 'สมัครสมาชิกสำเร็จแล้ว สมัครต่อได้เลย ทีมงาน');
                        return redirect()->intended(route($this->_config['redirect']));

                    } else {
                        session()->flash('error', Lang::get('app.register.fail2'));

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


    public function register(Request $request)
    {
        $otp = '';
        $config = $this->getCoreConfig();

        $datenow = now()->toDateTimeString();
        $today = now()->toDateString();
        $ip = $request->ip();

        $data = $request->all();

        // -------------------------
        // Normalize username / tel
        // -------------------------
        $data['user_name'] = Str::of($data['user_name'])
            ->replaceMatches('/[^0-9]++/', '')
            ->trim()
            ->__toString();

        $username = strip_tags($data['user_name']);
        $tel = $username;
        $data['tel'] = $tel;

        // -------------------------
        // Normalize fullname
        // -------------------------
        $fullname = trim(preg_replace('/\s+/u', ' ', strip_tags($data['name'])));
        $firstname = explode(' ', $fullname);

        $data['firstname'] = $firstname[0] ?? '';
        $data['lastname']  = $firstname[1] ?? '';

        if (isset($firstname[2])) {
            $data['lastname'] .= ' ' . $firstname[2];
        }

        // -------------------------
        // Normalize bank info
        // -------------------------
        $acc_no = Str::of($data['acc_no'])
            ->replaceMatches('/[^0-9]++/', '')
            ->trim()
            ->__toString();

        $data['acc_no'] = $acc_no;
        $bank_code = $data['bank'];
        $data['wallet_id'] = strip_tags($data['tel']);

        $lineid = isset($data['lineid'])
            ? trim(strip_tags($data['lineid']))
            : '';

        $wallet_id = trim($data['wallet_id']);

        $freecredit = ($config->freecredit_all === 'Y') ? 'Y' : 'N';

        if ($config->verify_open === 'Y') {
            $verify = 'N';
            if ($config->verify_sms === 'Y') {
                $otp = rand(100001, 999999);
            }
        } else {
            $verify = 'Y';
        }

        // ======================================================
        // VALIDATION RULES
        // ======================================================
        $rules = [
            'acc_no' => [
                'bail', 'required', 'digits_between:1,14',
                Rule::unique('members', 'acc_no')->where(
                    fn($q) => $q->where('bank_code', $bank_code)
                ),
                function ($attribute, $value, $fail) {
                    if (DB::table('banks_account')->where('acc_no', $value)->exists()) {
                        $fail('เลขที่บัญชีนี้ถูกใช้งานแล้วในระบบบัญชีธนาคารภายใน');
                    }
                },
            ],

            'firstname' => [
                'bail', 'required', 'regex:/^[\pL\pM\s\-]+$/u',
                function ($attribute, $value, $fail) use ($data) {

                    $fname = trim(preg_replace('/\s+/u', ' ', $data['firstname']));
                    $lname = trim(preg_replace('/\s+/u', ' ', $data['lastname']));

                    if ($lname === '') {
                        return;
                    }

                    $exists = DB::table('members')
                        ->where('enable', 'Y')
                        ->whereRaw(
                            'TRIM(CONCAT(firstname, " ", lastname)) = ?',
                            [$fname . ' ' . $lname]
                        )
                        ->exists();

                    if ($exists) {
                        $fail('ชื่อ–นามสกุลนี้ถูกใช้งานแล้ว กรุณาติดต่อทีมงาน');
                    }
                }
            ],

            'lastname' => ['bail', 'required', 'regex:/^[\pL\pM\s\-]+$/u'],

            'password' => ['bail', 'required', 'string', 'min:4', 'max:10'],

            'user_name' => [
                'bail', 'required', 'regex:/^\d+$/', 'unique:members,user_name',
                function ($attribute, $value, $fail) {
                    if (DB::table('banks_account')->where('acc_no', $value)->exists()) {
                        $fail('หมายเลขนี้ถูกใช้เป็นเลขบัญชีธนาคารภายในแล้ว');
                    }
                },
            ],

            'wallet_id' => ['bail', 'required', 'regex:/^\d+$/', 'unique:members,wallet_id'],

            'tel' => [
                'bail', 'required', 'regex:/^0\d{9}$/', 'unique:members,tel',
                function ($attribute, $value, $fail) {
                    if (DB::table('banks_account')->where('acc_no', $value)->exists()) {
                        $fail('หมายเลขนี้ถูกใช้เป็นเลขบัญชีธนาคารภายในแล้ว');
                    }
                },
            ],

            'bank'  => ['bail', 'required', 'integer'],
            'refer' => ['bail', 'required', 'integer'],
        ];

        $messages = [
            'firstname.regex' => 'ชื่อต้องเป็นตัวอักษร (รองรับภาษาไทย)',
            'lastname.regex'  => 'นามสกุลต้องเป็นตัวอักษร (รองรับภาษาไทย)',
            'tel.regex'       => 'เบอร์โทรต้องขึ้นต้นด้วย 0 และมี 10 หลัก',
            'user_name.regex' => 'บัญชีผู้ใช้ต้องเป็นตัวเลขล้วน',
            'wallet_id.regex' => 'Wallet ID ต้องเป็นตัวเลขล้วน',
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax()) {
                return $this->sendError(Lang::get('app.register.fail'), 200);
            }

            session()->flash('error', Lang::get('app.register.fail'));
            return redirect()->back()->withErrors($validator)->withInput();
        }
        Event::dispatch('customer.register.before', $data);

        if (!isset($data['upline'])) {
            $upline = 0;
        } else {
            $upline = $data['upline'];
            unset($data['upline']);
        }

        $team_id = null;
        $campaign_id = null;
        if (isset($data['marketing'])) {
            $marketing = $this->registerLinkRepository->findOneWhere(['code' => $data['marketing']]);
            if ($marketing) {
                $team_id = $marketing->team_id;
                $campaign_id = $marketing->campaign_id;
            }
            unset($data['marketing']);
        }

        $refer = $data['refer'];
        unset($data['refer']);

        $pass = $data['password'];
        //        $pass_confirm = $data['password_confirm'];
        //        unset($data['password_confirm']);
        unset($data['password']);
        $data['firstname'] = strip_tags($data['firstname']);
        $data['lastname'] = strip_tags($data['lastname']);
        $name = $data['firstname'] . ' ' . $data['lastname'];
        if (isset($data['promotion'])) {
            $pro = $data['promotion'];
        } else {
            $pro = 'N';
        }

        unset($data['g-recaptcha-response']);

        unset($data['bank']);
        if ($bank_code == 4) {
            $acc_check = substr($acc_no, -4);
        } else {
            $acc_check = substr($acc_no, -6);
        }
        $acc_bay = substr($acc_no, -7);
        $username = trim($username);
        $pass = trim($pass);

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
            'team_id' => $team_id,
            'campaign_id' => $campaign_id,
            'otp' => $otp,
            'ip' => $ip,
        ]);

        $response = app('Gametech\Marketing\Repositories\MarketingMemberRepository')->create($data);
        Log::channel('cashback')->info('Register', ['data' => $response]);
        if (!$response->code) {

            if ($request->expectsJson() || $request->ajax()) {
                return $this->sendError(Lang::get('app.register.fail2'), 200);
            }

            session()->flash('error', Lang::get('app.register.fail2'));

            return redirect()->back();
        }

        if ($config->verify_open == 'N') {

            if ($config->seamless == 'Y') {

//                session()->flash('success', Lang::get('app.register.success'));
//                if ($this->attemptLogin($request)) {
//                    return $this->sendLoginResponse($request);
//                }
//
//                return $this->sendFailedLoginResponse($request);

					$game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y', 'id' => 'seamless']);
					$member = app('Gametech\Member\Repositories\MemberRepository')->find($response->code);
					$res = app('Gametech\Game\Repositories\GameUserRepository')->addGameUser($game->code, $member->code, ['username' => $username, 'password' => $pass, 'name' => $name, 'user_create' => $name]);
					if ($res['success'] === true) {

						session()->flash('success', Lang::get('app.register.success'));
						if ($this->attemptLogin($request)) {
							return $this->sendLoginResponse($request);
						}

						return $this->sendFailedLoginResponse($request);

					} else {
						app('Gametech\Member\Repositories\MemberRepository')->delete($response->code);

						if ($request->expectsJson() || $request->ajax()) {
							return $this->sendError($res['msg'], 200);
						}

						session()->flash('error', $res['msg']);

						return redirect()->back();
					}

            } else {

                if ($config->multigame_open === 'N') {
                    $game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y']);
                    $member = app('Gametech\Member\Repositories\MemberRepository')->find($response->code);
                    $res = app('Gametech\Game\Repositories\GameUserRepository')->addGameUser($game->code, $member->code, $member);

                    if ($res['success'] === true) {

                        if ($request->expectsJson() || $request->ajax()) {
                            return $this->sendSuccess(Lang::get('app.register.success'), 200);
                        }


                        session()->flash('success', Lang::get('app.register.success'));
                        if ($this->attemptLogin($request)) {
                            return $this->sendLoginResponse($request);
                        }

                        return $this->sendFailedLoginResponse($request);

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

        Event::dispatch('customer.login.fail', $username . '|' . $password);
        session()->flash('error', Lang::get('app.login.fail'));

        return redirect()->back();

    }

    public function registerUser(Request $request)
    {
        $otp = '';
        $config = $this->getCoreConfig();

        $datenow = now()->toDateTimeString();
        $today = now()->toDateString();
        $ip = $request->ip();
        //        $data = $request->input();

        $data = $request->all();

        //        $data['user_name'] = Str::of($data['user_name'])->replaceMatches('/[^0-9]++/', '')->trim()->__toString();
        $username = strip_tags(strtolower($data['user_name']));

        $tel = Str::of($data['tel'])->replaceMatches('/[^0-9]++/', '')->trim()->__toString();

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

        //		dd($data);

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
            'lastname' => ['required', 'regex:/^[\pL\pM\s\-]+$/u'],
            'password' => 'required|min:6',
            //            'password_confirm' => 'min:6|same:password',
            'user_name' => 'required|alpha_num|different:tel|unique:members,user_name|max:10|regex:/^[a-z][a-z0-9]*$/',
//            'wallet_id' => 'required|numeric|unique:members,wallet_id',
            'tel' => 'required|numeric|unique:members,tel',
            'bank' => 'required|numeric',
            'refer' => 'required|numeric',
            //            'g-recaptcha-response' => 'required'
        ]);

        //        dd($validator);
        if ($validator->fails()) {
            Log::channel('cashback')->info('Validator', ['data' => $data]);
            if ($request->expectsJson() || $request->ajax()) {
                return $this->sendError(Lang::get('app.register.fail'), 200);
            }

            session()->flash('error', Lang::get('app.register.fail'));

            return redirect()->back()->withErrors($validator)->withInput();

        }

        Event::dispatch('customer.register.before', $data);

        if (!isset($data['upline'])) {
            $upline = 0;
        } else {
            $upline = $data['upline'];
            unset($data['upline']);
        }
        $team_id = null;
        $campaign_id = null;
        if (isset($data['marketing'])) {
            $marketing = $this->registerLinkRepository->findOneWhere(['code' => $data['marketing']]);
            if ($marketing) {
                $team_id = $marketing->team_id;
                $campaign_id = $marketing->campaign_id;
            }
            unset($data['marketing']);
        }

        $refer = $data['refer'];
        unset($data['refer']);

        $pass = $data['password'];
        //        $pass_confirm = $data['password_confirm'];
        //        unset($data['password_confirm']);
        unset($data['password']);
        $data['firstname'] = strip_tags($data['firstname']);
        $data['lastname'] = strip_tags($data['lastname']);
        $name = $data['firstname'] . ' ' . $data['lastname'];
        if (isset($data['promotion'])) {
            $pro = $data['promotion'];
        } else {
            $pro = 'N';
        }

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
            'team_id' => $team_id,
            'campaign_id' => $campaign_id,
            'otp' => $otp,
            'ip' => $ip,
        ]);

        $response = app('Gametech\Marketing\Repositories\MarketingMemberRepository')->create($data);

        if (!$response->code) {

            if ($request->expectsJson() || $request->ajax()) {
                return $this->sendError(Lang::get('app.register.fail2'), 200);
            }
            session()->flash('error', Lang::get('app.register.fail2'));

            return redirect()->back();
        }

        if ($config->verify_open == 'N') {

            if ($config->seamless == 'Y') {

                session()->flash('success', Lang::get('app.register.success'));
                if ($this->attemptLogin($request)) {
                    return $this->sendLoginResponse($request);
                }

                return $this->sendFailedLoginResponse($request);

//					$game = app('Gametech\Game\Repositories\GameRepository')->findOneWhere(['enable' => 'Y', 'status_open' => 'Y', 'id' => 'seamless']);
//					$member = app('Gametech\Member\Repositories\MemberRepository')->find($response->code);
//					$res = app('Gametech\Game\Repositories\GameUserRepository')->addGameUser($game->code, $member->code, ['username' => $username, 'password' => $pass, 'name' => $name, 'user_create' => $name]);
//					if ($res['success'] === true) {
//						session()->flash('success', Lang::get('app.register.success'));
//						if ($this->attemptLogin($request)) {
//							return $this->sendLoginResponse($request);
//						}
//
//						return $this->sendFailedLoginResponse($request);
//
//					} else {
//						app('Gametech\Member\Repositories\MemberRepository')->delete($response->code);
//
//						if ($request->expectsJson() || $request->ajax()) {
//							return $this->sendError($res['msg'], 200);
//						}
//
//						session()->flash('error', $res['msg']);
//
//						return redirect()->back();
//					}

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

    public function checkUser(Request $request)
    {
        $user = $request->input('username');

        $data['user_name'] = $user;

        $validator = Validator::make($data, [
            'user_name' => 'required|alpha_num|unique:members,user_name',
        ]);

        if ($validator->fails()) {
            return response()->json(['exists' => true, 'message' => __('app.register.cannot_use')]);
        }

        return response()->json(['exists' => false, 'message' => __('app.register.can_use')]);
    }

    public function checkPhone(Request $request)
    {
        // เลือก source ตามพฤติกรรมเดิม
        if ($request->has('username') === false) {
            $phone = $request->input('tel');
        } else {
            $phone = $request->input('username');
        }

        $data = [
            'user_name' => $phone,
            'tel' => $phone,
        ];

        $validator = Validator::make($data, [
            'user_name' => [
                'required', 'numeric', 'unique:members,user_name',
                // ✅ กันค่าที่ซ้ำกับ banks_account.acc_no (ถ้าส่ง bank มาก็กรองด้วย)
                function ($attribute, $value, $fail) use ($request) {
                    $exists = DB::table('banks_account')
                        ->where('acc_no', $value)
                        ->when($request->filled('bank'), fn($q) => $q->where('banks', $request->input('bank')))
                        ->exists();

                    if ($exists) {
                        $fail('หมายเลขนี้ถูกใช้เป็นเลขบัญชีธนาคารภายในแล้ว');
                    }
                },
            ],
            'tel' => [
                'required', 'numeric', 'unique:members,tel',
                // ✅ เพิ่มเงื่อนไขเดียวกันที่ฟิลด์ tel ด้วย (เพราะใช้ค่าเดียวกัน)
                function ($attribute, $value, $fail) use ($request) {
                    $exists = DB::table('banks_account')
                        ->where('acc_no', $value)
                        ->when($request->filled('bank'), fn($q) => $q->where('banks', $request->input('bank')))
                        ->exists();

                    if ($exists) {
                        $fail('หมายเลขนี้ถูกใช้เป็นเลขบัญชีธนาคารภายในแล้ว');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            // ตามพฤติกรรมเดิม: ถ้าไม่ผ่าน → exists = true
            return response()->json([
                'exists' => true,
                'message' => __('app.register.cannot_use'),
            ]);
        }

        return response()->json([
            'exists' => false,
            'message' => __('app.register.can_use'),
        ]);
    }


    public function checkBank(Request $request)
    {
        $firstname = '';
        $lastname = '';

        // รับค่าจาก request
        $bank = $request->input('bank');      // รหัสธนาคารที่ฟอร์มส่งมา (ควรตรงกับ members.bank_code / banks_account.banks)
        $acc = $request->input('acc_no');    // เลขบัญชี

        // เตรียมข้อมูลให้ Validator
        $data = ['acc_no' => $acc];

        $validator = Validator::make($data, [
            'acc_no' => [
                'bail', 'required', 'digits_between:1,14',

                // ✅ ตรวจซ้ำในตาราง members โดยจับคู่ bank_code
                Rule::unique('members', 'acc_no')->where(function ($q) use ($bank) {
                    $q->where('bank_code', $bank); // ถ้า bank_code เป็น int ใน DB แนะนำ cast (int)$bank
                }),

                // ✅ ตรวจซ้ำในตาราง banks_account โดยจับคู่ (acc_no + banks)
                function ($attribute, $value, $fail) use ($bank) {
                    $exists = DB::table('banks_account')
                        ->where('acc_no', $value)
                        ->where('banks', $bank) // ถ้า banks เป็น int: ->where('banks', (int)$bank)
                        ->exists();

                    if ($exists) {
                        $fail('เลขที่บัญชีนี้ถูกใช้แล้วในระบบบัญชีธนาคารภายใน (banks_account) สำหรับธนาคารนี้');
                    }
                },
            ],
        ]);

        // หมายเหตุ: โค้ดเดิมของคุณถือว่า "ไม่ผ่าน validation" = ใช้ไม่ได้ → valid=true (ตาม interface เดิม)
        if ($validator->fails()) {
            return response()->json([
                'valid' => true,
                'message' => __('app.register.cannot_use'),
            ]);
        }

        // === ผ่าน validation แล้ว → ไปเช็คชื่อบัญชีจากบริการภายนอก ===
        $postData = [
            'toBankAccNumber' => $acc,
            'toBankAccNameCode' => $this->Banks($bank),
        ];

        $response = Http::withHeaders([
            'x-api-key' => 'af96aa1c-e1f5-4c22-ab96-7f5453704aa9',
        ])->asJson()->post('https://me2me.biz/getname.php', $postData);

        if (!$response->successful()) {
            // เผื่อ API ล่ม/timeout — คงภาษากลับแบบเดิม (ให้ user ลองใหม่)
            return response()->json([
                'valid' => true,
                'message' => __('app.register.wrong'),
            ]);
        }

        $return = $response->json();

        if (!data_get($return, 'status')) {
            // API ตอบว่าค้นหาไม่เจอ/ผิดรูปแบบ
            return response()->json([
                'valid' => true,
                'message' => __('app.register.wrong'),
            ]);
        }

        // ดึงชื่อ-นามสกุลจาก API และ normalize
        $rawFullname = (string)data_get($return, 'data.accountName', '');
        $cleanFullname = $this->cleanInvisibleAndSpaces($rawFullname);
        $fullname = $this->splitNameUniversal($cleanFullname);

        $firstname = $fullname['firstname'] ?? '';
        $lastname = $fullname['lastname'] ?? '';

        // หมายเหตุ: โค้ดเดิมของคุณถือว่า "ผ่านทั้งหมด/ใช้ได้" → valid=false (ตาม interface เดิม)
        return response()->json([
            'valid' => false,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'message' => __('app.register.can_use'),
        ]);
    }

    public function Banks($bankcode)
    {

        switch ($bankcode) {
            case '1':
                $result = 'BBL';
                break;
            case '2':
                $result = 'KBANK';
                break;
            case '3':
                $result = 'KTB';
                break;
            case '4':
                $result = 'SCB';
                break;
            case '5':
                $result = 'GHB';
                break;
            case '6':
                $result = 'KKP';
                break;
            case '7':
                $result = 'CIMB';
                break;
            case '19':
            case '15':
            case '10':
                $result = 'TTB';
                break;
            case '11':
                $result = 'BAY';
                break;
            case '12':
                $result = 'UOB';
                break;
            case '13':
                $result = 'LHB';
                break;
            case '14':
                $result = 'GSB';
                break;
            case '17':
                $result = 'BAAC';
                break;
            default:
                $result = '500';
                break;
        }
        return $result;

    }

    private function cleanInvisibleAndSpaces(string $s): string
    {
        // ลบอักขระรูปแบบ (General Category: Cf) ที่เจอบ่อยแบบเจาะจง
        $s = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{200E}\x{200F}\x{2060}\x{00A0}\x{202F}\x{FEFF}]/u', '', $s);

        // แปลง \r\n, \t ฯลฯ เป็นช่องว่าง แล้วบีบให้เหลือช่องว่างเดียว
        $s = preg_replace('/\s+/u', ' ', $s);

        // ตัดช่องว่างหัวท้าย
        return trim($s);
    }

    public function splitNameUniversal(string $fullName): array
    {
        // ล้าง ZWSP/NBSP/BOM ฯลฯ และ normalize ช่องว่าง
        $fullName = $this->cleanInvisibleAndSpaces($fullName);

        // คำนำหน้าที่พบบ่อย (เพิ่ม/แก้ได้ตามดาต้า)
        $prefixes = [
            // ไทย
            'นาย', 'นางสาว', 'นาง', 'น.ส.', 'น.', 'ดร.', 'ศ.', 'ผศ.', 'รศ.', 'ด.ญ.', 'ด.ช.', 'เด็กชาย.', 'เด็กหญิง.', 'เด็กชาย', 'เด็กหญิง', 'สาว',
            // อังกฤษ
            'Mr.', 'Mrs.', 'Ms.', 'Miss', 'Dr.', 'Prof.', 'Sir', 'Madam', 'MISTER', 'MISS', 'MS', 'MR', 'MRS', 'KHUN'
        ];

        // ตัดคำนำหน้าออก (ไม่สนตัวพิมพ์ใหญ่เล็ก, รองรับ multibyte)
        foreach ($prefixes as $prefix) {
            if (mb_stripos($fullName, $prefix) === 0) {
                $fullName = trim(mb_substr($fullName, mb_strlen($prefix)));
                break;
            }
        }

        // กันกรณีคั่นด้วยหลายช่องว่าง/อักขระเว้นวรรคหลากชนิด
        $parts = preg_split('/\s+/u', $fullName);

        $firstname = $parts[0] ?? '';
        $lastname = count($parts) > 1 ? $parts[count($parts) - 1] : '';

        // ล้างซ้ำอีกรอบให้ชัวร์ (กันเศษอักขระตกค้าง)
        $firstname = $this->cleanInvisibleAndSpaces($firstname);
        $lastname = $this->cleanInvisibleAndSpaces($lastname);

        return [
            'firstname' => $firstname,
            'lastname' => $lastname,
        ];
    }

    public function username(): string
    {
        return 'user_name';
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


    public function show()
    {
//            dd(Auth::guard('customer')->check());
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.home.index');
        }
//            dd('here');
        return view('wallet::customer.subpage.login');
    }
}
