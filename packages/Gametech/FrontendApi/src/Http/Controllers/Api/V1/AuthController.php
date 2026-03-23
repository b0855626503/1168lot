<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\FrontendApi\Services\FrontendTokenService;
use Gametech\Member\Models\MemberProxy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends BaseController
{
    private FrontendTokenService $tokenService;

    public function __construct(FrontendTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    public function register(Request $request)
    {
        return $this->registerFallback($request);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_name' => 'required',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('ข้อมูลเข้าสู่ระบบไม่ครบถ้วน', 422);
        }

        $username = Str::of((string) $request->input('user_name'))
            ->replaceMatches('/[^0-9]++/', '')
            ->trim()
            ->__toString();
        $password = (string) $request->input('password');

        $member = app('Gametech\Member\Repositories\MemberRepository')
            ->findOneByField('user_name', $username);

        if (! $member) {
            return $this->sendError('ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง', 401);
        }

        if (empty($member->password) && ! empty($member->user_pass)) {
            app('Gametech\Member\Repositories\MemberRepository')->update([
                'password' => Hash::make((string) $member->user_pass),
            ], (int) $member->code);
            $member = app('Gametech\Member\Repositories\MemberRepository')->find((int) $member->code);
        }

        $validPassword = false;
        if (! empty($member->password) && Hash::check($password, (string) $member->password)) {
            $validPassword = true;
        } elseif ((string) $member->user_pass === $password) {
            $validPassword = true;
        }

        if (! $validPassword) {
            return $this->sendError('ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง', 401);
        }

        if ((string) $member->enable !== 'Y') {
            return $this->sendError('บัญชีผู้ใช้ถูกระงับการใช้งาน', 403);
        }

        $token = $this->tokenService->issue($member);

        return $this->sendResponseNew([
            'access_token' => $token['token'],
            'token_type' => $token['token_type'],
            'expires_at' => $token['expires_at'],
            'expires_in' => $token['expires_in'],
            'member' => [
                'code' => (int) $member->code,
                'user_name' => (string) $member->user_name,
                'name' => (string) $member->name,
                'confirm' => (string) $member->confirm,
            ],
        ], 'เข้าสู่ระบบสำเร็จ');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->tokenService->blacklist($this->tokenPayload($request));

        return $this->sendSuccess('ออกจากระบบสำเร็จ');
    }

    private function registerFallback(Request $request): JsonResponse
    {
        $config = core()->getConfigData();

        $data = $request->all();
        $data['user_name'] = Str::of((string) ($data['user_name'] ?? ''))
            ->replaceMatches('/[^0-9]++/', '')
            ->trim()
            ->__toString();
        $data['tel'] = $data['user_name'];
        $data['wallet_id'] = $data['user_name'];
        $data['acc_no'] = Str::of((string) ($data['acc_no'] ?? ''))
            ->replaceMatches('/[^0-9]++/', '')
            ->trim()
            ->__toString();

        $bankCode = (int) ($data['bank'] ?? 0);

        $validator = Validator::make($data, [
            'acc_no' => [
                'required',
                'digits_between:1,20',
                Rule::unique('members', 'acc_no')->where(function ($query) use ($bankCode) {
                    return $query->where('bank_code', $bankCode);
                }),
            ],
            'firstname' => 'required|alpha',
            'lastname' => 'required|alpha',
            'password' => 'required|min:6|max:10',
            'password_confirm' => 'min:6|same:password',
            'user_name' => 'required|numeric|unique:members,user_name',
            'wallet_id' => 'required|numeric|unique:members,wallet_id',
            'tel' => 'required|numeric|unique:members,tel',
            'bank' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->sendError('ข้อมูลสมัครสมาชิกไม่ถูกต้อง', 422);
        }

        $username = (string) $data['user_name'];
        $name = trim(strip_tags((string) $data['firstname'])) . ' ' . trim(strip_tags((string) $data['lastname']));
        $pass = (string) $data['password'];
        $verify = ((string) ($config->verify_open ?? 'N') === 'Y') ? 'N' : 'Y';
        $freecredit = ((string) ($config->freecredit_all ?? 'N') === 'Y') ? 'Y' : 'N';
        $today = now()->toDateString();
        $accNo = (string) $data['acc_no'];
        $accCheck = ($bankCode === 4) ? substr($accNo, -4) : substr($accNo, -6);
        $accBay = substr($accNo, -7);

        $member = null;
        try {
            $member = MemberProxy::withoutEvents(function () use ($bankCode, $name, $data, $username, $pass, $accNo, $accCheck, $accBay, $verify, $freecredit, $today, $request) {
                return app('Gametech\Member\Repositories\MemberRepository')->create([
                    'refer_code' => 0,
                    'upline_code' => 0,
                    'bank_code' => $bankCode,
                    'name' => $name,
                    'firstname' => trim(strip_tags((string) $data['firstname'])),
                    'lastname' => trim(strip_tags((string) $data['lastname'])),
                    'user_name' => $username,
                    'user_pass' => $pass,
                    'password' => Hash::make($pass),
                    'acc_no' => $accNo,
                    'acc_check' => $accCheck,
                    'acc_bay' => $accBay,
                    'acc_kbank' => '',
                    'tel' => $username,
                    'wallet_id' => $username,
                    'lineid' => '',
                    'confirm' => $verify,
                    'freecredit' => $freecredit,
                    'check_status' => 'N',
                    'promotion' => 'N',
                    'user_create' => $name,
                    'user_update' => $name,
                    'lastlogin' => now(),
                    'date_regis' => $today,
                    'birth_day' => $today,
                    'session_limit' => null,
                    'payment_limit' => null,
                    'payment_delay' => null,
                    'remark' => '',
                    'gender' => 'M',
                    'otp' => '',
                    'ip' => $request->ip(),
                    'balance' => 0,
                    'balance_free' => 0,
                    'credit' => 0,
                    'point_deposit' => 0,
                    'diamond' => 0,
                    'enable' => 'Y',
                ]);
            });

            $game = app('Gametech\Game\Repositories\GameRepository')
                ->findOneWhere(['enable' => 'Y', 'status_open' => 'Y', 'id' => 'seamless']);
            if (! $game) {
                $game = app('Gametech\Game\Repositories\GameRepository')
                    ->findOneWhere(['enable' => 'Y', 'status_open' => 'Y']);
            }

            if ($game) {
                app('Gametech\Game\Repositories\GameUserRepository')->updateOrCreate(
                    ['member_code' => (int) $member->code, 'game_code' => (int) $game->code],
                    [
                        'user_name' => $username,
                        'user_pass' => $pass,
                        'balance' => 0,
                        'enable' => 'Y',
                        'user_create' => $name,
                        'user_update' => $name,
                        'date_create' => now(),
                        'date_update' => now(),
                        'bill_code' => 0,
                        'pro_code' => 0,
                        'amount' => 0,
                        'bonus' => 0,
                        'turnpro' => 0,
                        'amount_balance' => 0,
                        'withdraw_limit' => 0,
                        'withdraw_limit_rate' => 0,
                        'withdraw_limit_amount' => 0,
                    ]
                );
            }

            try {
                Event::dispatch('member.created.after', [$member]);
            } catch (\Throwable $e) {
                report($e);
            }
        } catch (\Throwable $e) {
            if ($member && isset($member->code)) {
                try {
                    app('Gametech\Member\Repositories\MemberRepository')->delete((int) $member->code);
                } catch (\Throwable $rollbackError) {
                    report($rollbackError);
                }
            }

            return $this->sendError('ไม่สามารถสมัครสมาชิกได้ในขณะนี้', 422);
        }

        return $this->sendSuccess('สมัครสมาชิกสำเร็จ');
    }
}
