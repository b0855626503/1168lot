<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\FrontendApi\Exceptions\RegisterFailureException;
use Gametech\FrontendApi\Services\FrontendTokenService;
use Gametech\Member\Models\MemberProxy;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

    public function registerBanks(): JsonResponse
    {
        try {
            $banks = app('Gametech\Payment\Repositories\BankRepository')
                ->findWhere(['enable' => 'Y', 'show_regis' => 'Y', ['code', '<>', 0]]);

            $items = collect($banks)->map(function ($bank): array {
                $bankImage = $this->resolveBankImage((string) ($bank->filepic ?? ''));

                return [
                    'code' => (int) ($bank->code ?? 0),
                    'name' => (string) ($bank->name_th ?? $bank->name_en ?? $bank->name ?? ''),
                    'name_th' => (string) ($bank->name_th ?? ''),
                    'name_en' => (string) ($bank->name_en ?? ''),
                    'shortcode' => (string) ($bank->shortcode ?? ''),
                    'image' => $bankImage['path'],
                    'image_url' => $bankImage['url'],
                ];
            })->values()->all();

            return $this->sendResponse([
                'banks' => $items,
            ], 'ดึงรายการธนาคารสำหรับสมัครสมาชิกสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายการธนาคารสำหรับสมัครสมาชิกได้ในขณะนี้', 422);
        }
    }

    /**
     * @return array{path:string,url:string}
     */
    private function resolveBankImage(string $filepic): array
    {
        $filepic = trim($filepic);
        if ($filepic === '') {
            return ['path' => '', 'url' => ''];
        }

        if (Str::startsWith($filepic, ['http://', 'https://'])) {
            $url = $this->appendMediaCacheBust($filepic);
            return ['path' => $url, 'url' => $url];
        }

        if (Str::startsWith($filepic, '/')) {
            $path = $this->appendMediaCacheBust($filepic);
            return ['path' => $path, 'url' => url($path)];
        }

        return $this->storageMediaUrls('bank_img/' . ltrim($filepic, '/'));
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
        Log::error('frontend_api_register.entry', [
            'path' => (string) $request->path(),
            'seamless_config' => (string) ($config->seamless ?? 'N'),
            'has_name' => $request->filled('name'),
            'has_user_name' => $request->filled('user_name'),
            'has_bank' => $request->filled('bank'),
            'has_acc_no' => $request->filled('acc_no'),
            'has_refer' => $request->filled('refer'),
            'has_referral_code' => $request->filled('referral_code'),
            'has_marketing' => $request->filled('marketing'),
            'all' => $request->all(),
        ]);

        $data = $this->normalizeRegisterPayload((array) $request->all());

        $data['user_name'] = Str::of((string) ($data['user_name'] ?? ''))
            ->replaceMatches('/[^0-9]++/', '')
            ->trim()
            ->__toString();
        $data['tel'] = Str::of((string) ($data['tel'] ?? $data['user_name']))
            ->replaceMatches('/[^0-9]++/', '')
            ->trim()
            ->__toString();
        $data['wallet_id'] = Str::of((string) ($data['wallet_id'] ?? $data['user_name']))
            ->replaceMatches('/[^0-9]++/', '')
            ->trim()
            ->__toString();
        $data['acc_no'] = Str::of((string) ($data['acc_no'] ?? ''))
            ->replaceMatches('/[^0-9]++/', '')
            ->trim()
            ->__toString();

        $bankCode = (int) ($data['bank'] ?? 0);
        $lineId = trim(strip_tags((string) ($data['lineid'] ?? '')));
        $username = (string) $data['user_name'];
        $tel = (string) $data['tel'];
        $walletId = (string) $data['wallet_id'];
        $accNo = (string) $data['acc_no'];

        $validator = Validator::make($data, [
            'acc_no' => [
                'bail',
                'required',
                'digits_between:1,14',
                Rule::unique('members', 'acc_no')->where(function ($query) use ($bankCode) {
                    return $query->where('bank_code', $bankCode);
                }),
                function ($attribute, $value, $fail): void {
                    if (DB::table('banks_account')->where('acc_no', (string) $value)->exists()) {
                        $fail('เลขที่บัญชีนี้ถูกใช้งานแล้วในระบบบัญชีธนาคารภายใน');
                    }
                },
            ],
            'firstname' => ['bail', 'required', 'regex:/^[\pL\pM\s\-]+$/u'],
            'lastname' => ['bail', 'required', 'regex:/^[\pL\pM\s\-]+$/u'],
            'password' => 'bail|required|string|min:6|max:10',
            'password_confirm' => 'nullable|string|same:password',
            'user_name' => [
                'bail',
                'required',
                'regex:/^\d+$/',
                'unique:members,user_name',
                function ($attribute, $value, $fail): void {
                    if (DB::table('banks_account')->where('acc_no', (string) $value)->exists()) {
                        $fail('หมายเลขนี้ถูกใช้เป็นเลขบัญชีธนาคารภายในแล้ว');
                    }
                },
            ],
            'wallet_id' => 'bail|required|regex:/^\d+$/|unique:members,wallet_id',
            'tel' => [
                'bail',
                'required',
                'regex:/^0\d{9}$/',
                'unique:members,tel',
                function ($attribute, $value, $fail): void {
                    if (DB::table('banks_account')->where('acc_no', (string) $value)->exists()) {
                        $fail('หมายเลขนี้ถูกใช้เป็นเลขบัญชีธนาคารภายในแล้ว');
                    }
                },
            ],
            'bank' => 'bail|required|integer',
            'refer' => 'bail|required|integer',
            'marketing' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $name = trim(strip_tags((string) $data['firstname'])) . ' ' . trim(strip_tags((string) $data['lastname']));
        $pass = (string) $data['password'];
        $verify = ((string) ($config->verify_open ?? 'N') === 'Y') ? 'N' : 'Y';
        $freecredit = ((string) ($config->freecredit_all ?? 'N') === 'Y') ? 'Y' : 'N';
        $today = now()->toDateString();
        $accCheck = ($bankCode === 4) ? substr($accNo, -4) : substr($accNo, -6);
        $accBay = substr($accNo, -7);
        $referCode = (int) $data['refer'];
        $uplineCode = (int) ($data['upline'] ?? 0);
        $promotion = (string) ($data['promotion'] ?? 'N');
        $inputReferralCode = $this->normalizeReferralCodeInput((string) ($data['referral_code'] ?? ''));
        if ($inputReferralCode !== '') {
            $uplineByReferral = DB::table('members')
                ->where('referral_code', $inputReferralCode)
                ->value('code');

            if (is_numeric($uplineByReferral)) {
                $uplineCode = (int) $uplineByReferral;
            }
        }
        $teamId = null;
        $campaignId = null;

        if (! empty($data['marketing'])) {
            $marketing = app('Gametech\Marketing\Repositories\RegistrationLinkRepository')
                ->findOneWhere(['code' => (string) $data['marketing']]);
            if ($marketing) {
                $teamId = $marketing->team_id;
                $campaignId = $marketing->campaign_id;
            }
        }

        Event::dispatch('customer.register.before', $data);

        $member = null;
        try {
            $member = $this->createRegisterMember(function () use (
                $bankCode,
                $name,
                $data,
                $username,
                $pass,
                $accNo,
                $accCheck,
                $accBay,
                $verify,
                $freecredit,
                $today,
                $request,
                $referCode,
                $uplineCode,
                $walletId,
                $tel,
                $lineId,
                $promotion,
                $teamId,
                $campaignId
            ) {
                return MemberProxy::withoutEvents(function () use (
                    $bankCode,
                    $name,
                    $data,
                    $username,
                    $pass,
                    $accNo,
                    $accCheck,
                    $accBay,
                    $verify,
                    $freecredit,
                    $today,
                    $request,
                    $referCode,
                    $uplineCode,
                    $walletId,
                    $tel,
                    $lineId,
                    $promotion,
                    $teamId,
                    $campaignId
                ) {
                    $referralCode = $this->generateUniqueReferralCode();
                    $memberPayload = [
                        'refer_code' => $referCode,
                        'upline_code' => $uplineCode,
                        'referral_code' => $referralCode,
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
                        'tel' => $tel,
                        'wallet_id' => $walletId,
                        'lineid' => $lineId,
                        'confirm' => $verify,
                        'freecredit' => $freecredit,
                        'check_status' => 'N',
                        'promotion' => $promotion,
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
                        'team_id' => $teamId,
                        'campaign_id' => $campaignId,
                    ];

                    return app('Gametech\Member\Repositories\MemberRepository')->create($memberPayload);
                });
            });

            $this->provisionRegisterGameAccount((string) ($config->seamless ?? 'N'), $member, $username, $pass, $name);

            try {
                Event::dispatch('member.created.after', [$member]);
            } catch (\Throwable $e) {
                report($e);
            }
        } catch (RegisterFailureException $e) {
            Log::error('frontend_api_register.failure', [
                'user_name' => $username ?? null,
                'error_code' => $e->errorCodeValue(),
                'message' => $e->userMessage(),
                'details' => $e->details(),
                'previous_class' => $e->getPrevious() ? get_class($e->getPrevious()) : null,
                'previous_message' => $e->getPrevious()?->getMessage(),
            ]);

            $this->cleanupRegisterMember($member);

            return $this->registerFailureResponse($e->userMessage(), $e->errorCodeValue(), $e->details());
        } catch (\Throwable $e) {
            Log::error('frontend_api_register.exception', [
                'user_name' => $username ?? null,
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
            ]);

            $this->cleanupRegisterMember($member);

            return $this->registerFailureResponse(
                'ไม่สามารถสมัครสมาชิกได้ในขณะนี้',
                'REGISTER_UNKNOWN_FAILURE',
                ['stage' => 'unknown']
            );
        }

        return $this->sendSuccess('สมัครสมาชิกสำเร็จ');
    }

    private function createRegisterMember(callable $creator)
    {
        try {
            return $creator();
        } catch (\Throwable $e) {
            throw new RegisterFailureException(
                'ไม่สามารถสร้างข้อมูลสมาชิกได้ในขณะนี้',
                'REGISTER_MEMBER_CREATE_FAILED',
                ['stage' => 'member_create'],
                $e
            );
        }
    }

    private function provisionRegisterGameAccount(string $seamlessConfig, $member, string $username, string $pass, string $name): void
    {
        $gameRepo = app('Gametech\Game\Repositories\GameRepository');
        $gameUserRepo = app('Gametech\Game\Repositories\GameUserRepository');

        if ($seamlessConfig === 'Y') {
            Log::error('frontend_api_register.seamless_branch_entered', [
                'member_code' => (int) ($member->code ?? 0),
                'user_name' => $username,
                'seamless_config' => $seamlessConfig,
            ]);

            $game = $gameRepo->findOneWhere([
                'enable' => 'Y',
                'status_open' => 'Y',
                'id' => 'seamless',
            ]);

            Log::error('frontend_api_register.seamless_game_lookup', [
                'member_code' => (int) ($member->code ?? 0),
                'user_name' => $username,
                'game_found' => (bool) $game,
                'game_code' => $game ? (int) $game->code : null,
                'game_id' => $game ? (string) $game->id : null,
            ]);

            if (! $game) {
                return;
            }

            $result = $gameUserRepo->addGameUser((int) $game->code, (int) $member->code, [
                'username' => $username,
                'password' => $pass,
                'name' => $name,
                'user_create' => $name,
            ]);

            $resultMessage = $this->normalizeRegisterFailureMessage($result['msg'] ?? null);

            Log::error('frontend_api_register.seamless_add_game_user_result', [
                'member_code' => (int) ($member->code ?? 0),
                'user_name' => $username,
                'game_code' => (int) $game->code,
                'success' => (bool) ($result['success'] ?? false),
                'message' => $resultMessage,
            ]);

            if (($result['success'] ?? false) === true) {
                return;
            }

            $isUnauthorizedIp = $this->isRegisterInvalidIpFailure($resultMessage);
            if ($isUnauthorizedIp) {
                Log::error('frontend_api_register.seamless_fallback_local_game_user', [
                    'member_code' => (int) ($member->code ?? 0),
                    'user_name' => $username,
                    'game_code' => (int) $game->code,
                    'reason' => 'seamless_401_invalid_ip',
                ]);

                try {
                    $gameUserRepo->updateOrCreate(
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
                } catch (\Throwable $e) {
                    throw new RegisterFailureException(
                        'ไม่สามารถสร้างบัญชีเกมสำรองได้ในขณะนี้',
                        'REGISTER_GAME_ACCOUNT_FALLBACK_FAILED',
                        ['stage' => 'game_account_fallback', 'reason' => 'invalid_ip_fallback_failed'],
                        $e
                    );
                }

                Log::error('frontend_api_register.seamless_fallback_local_game_user_success', [
                    'member_code' => (int) ($member->code ?? 0),
                    'user_name' => $username,
                    'game_code' => (int) $game->code,
                ]);

                return;
            }

            throw $this->buildGameAccountFailureException($resultMessage);
        }

        $game = $gameRepo->findOneWhere(['enable' => 'Y', 'status_open' => 'Y']);
        if (! $game) {
            return;
        }

        try {
            $gameUserRepo->updateOrCreate(
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
        } catch (\Throwable $e) {
            throw new RegisterFailureException(
                'ไม่สามารถสร้างบัญชีเกมได้ในขณะนี้',
                'REGISTER_GAME_ACCOUNT_CREATE_FAILED',
                ['stage' => 'game_account_create', 'reason' => 'local_game_account_create_failed'],
                $e
            );
        }
    }

    private function buildGameAccountFailureException(string $resultMessage): RegisterFailureException
    {
        $normalized = Str::lower($resultMessage);
        $details = ['stage' => 'game_account_create'];

        if ($resultMessage !== '') {
            $details['upstream_message'] = $resultMessage;
        }

        if (
            str_contains($normalized, 'เชื่อมต่อไม่ได้')
            || str_contains($normalized, 'connect')
            || str_contains($normalized, 'timeout')
            || str_contains($normalized, 'timed out')
        ) {
            $details['reason'] = 'connect_failed';

            return new RegisterFailureException(
                'ไม่สามารถเชื่อมต่อระบบเกมเพื่อสร้างบัญชีได้ในขณะนี้',
                'REGISTER_GAME_ACCOUNT_CONNECT_FAILED',
                $details
            );
        }

        if (
            str_contains($normalized, 'unauthorized')
            || str_contains($normalized, 'statuscode":401')
            || str_contains($normalized, '401')
        ) {
            $details['reason'] = 'unauthorized';

            return new RegisterFailureException(
                'ระบบเกมปฏิเสธการสร้างบัญชีสมาชิก',
                'REGISTER_GAME_ACCOUNT_UNAUTHORIZED',
                $details
            );
        }

        $details['reason'] = 'create_failed';

        return new RegisterFailureException(
            $resultMessage !== ''
                ? 'สร้างบัญชีเกมไม่สำเร็จ: ' . $resultMessage
                : 'ไม่สามารถสร้างบัญชีเกมได้ในขณะนี้',
            'REGISTER_GAME_ACCOUNT_CREATE_FAILED',
            $details
        );
    }

    private function isRegisterInvalidIpFailure(string $resultMessage): bool
    {
        $normalized = Str::lower($resultMessage);

        return str_contains($normalized, 'statuscode":401')
            && str_contains($normalized, 'invalid ip');
    }

    private function normalizeRegisterFailureMessage($message): string
    {
        if (is_array($message) || is_object($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }

        $normalized = trim(strip_tags((string) $message));
        if ($normalized === '') {
            return '';
        }

        $decoded = json_decode($normalized, true);
        if (is_array($decoded)) {
            foreach (['message', 'msg', 'error'] as $key) {
                if (! empty($decoded[$key]) && is_string($decoded[$key])) {
                    $normalized = trim(strip_tags((string) $decoded[$key]));
                    break;
                }
            }
        }

        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? '';

        return trim(Str::limit($normalized, 160, '...'));
    }

    private function cleanupRegisterMember($member): void
    {
        if (! $member || ! isset($member->code)) {
            return;
        }

        try {
            app('Gametech\Member\Repositories\MemberRepository')->delete((int) $member->code);
        } catch (\Throwable $rollbackError) {
            report($rollbackError);
        }
    }

    /**
     * @param array<string, mixed> $details
     */
    private function registerFailureResponse(string $message, string $errorCode, array $details = [], int $status = 422): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
        ];

        if (! empty($details)) {
            $payload['details'] = $details;
        }

        return response()->json($payload, $status);
    }

    private function validationErrorResponse(ValidatorContract $validator): JsonResponse
    {
        $errors = $validator->errors()->toArray();
        $failedRules = $validator->failed();
        $duplicateFields = [];
        $details = [];

        foreach ($errors as $field => $messages) {
            $fieldFailedRules = array_keys((array) ($failedRules[$field] ?? []));
            $isDuplicate = in_array('Unique', $fieldFailedRules, true);

            if (! $isDuplicate) {
                foreach ($messages as $message) {
                    $text = mb_strtolower((string) $message);
                    if (str_contains($text, 'taken') || str_contains($text, 'ถูกใช้งานแล้ว') || str_contains($text, 'ถูกใช้')) {
                        $isDuplicate = true;
                        break;
                    }
                }
            }

            if ($isDuplicate) {
                $duplicateFields[] = (string) $field;
            }

            $details[$field] = [
                'messages' => array_values((array) $messages),
                'failed_rules' => $fieldFailedRules,
                'is_duplicate' => $isDuplicate,
            ];
        }

        return response()->json([
            'success' => false,
            'message' => 'ข้อมูลสมัครสมาชิกไม่ถูกต้อง',
            'errors' => $errors,
            'error_fields' => array_values(array_keys($errors)),
            'duplicate_fields' => array_values(array_unique($duplicateFields)),
            'details' => $details,
        ], 422);
    }

    private function normalizeRegisterPayload(array $data): array
    {
        $username = (string) ($data['user_name'] ?? $data['phone'] ?? $data['tel'] ?? $data['username'] ?? '');
        $phone = (string) ($data['phone'] ?? $data['tel'] ?? $username);
        $fullName = trim((string) ($data['name'] ?? ''));

        if (! isset($data['firstname']) && $fullName !== '') {
            [$firstName, $lastName] = $this->splitFullName($fullName);
            $data['firstname'] = $firstName;
            $data['lastname'] = $lastName;
        }

        if (! isset($data['lastname'])) {
            $data['lastname'] = (string) ($data['firstname'] ?? '-');
        }

        $data['user_name'] = $username;
        $data['tel'] = $phone;
        $data['wallet_id'] = (string) ($data['wallet_id'] ?? $username);
        $data['password_confirm'] = (string) ($data['password_confirm'] ?? $data['password_confirmation'] ?? '');
        $data['bank'] = $data['bank'] ?? null;
        $data['acc_no'] = (string) ($data['acc_no'] ?? $data['account_no'] ?? '');
        $data['refer'] = $data['refer'] ?? ($data['refer_code'] ?? null);
        $data['referral_code'] = (string) (
            $data['referral_code']
            ?? $data['invite_code']
            ?? $data['recommend_code']
            ?? ''
        );

        return $data;
    }

    private function normalizeReferralCodeInput(string $code): string
    {
        $normalized = strtoupper(trim($code));
        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized) ?? '';
        $normalized = str_replace('O', '0', $normalized);

        return strlen($normalized) === 8 ? $normalized : '';
    }

    private function generateUniqueReferralCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ0123456789';
        $maxAttempts = 50;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $exists = DB::table('members')
                ->where('referral_code', $code)
                ->exists();

            if (! $exists) {
                return $code;
            }
        }

        throw new \RuntimeException('ไม่สามารถสร้างรหัสแนะนำได้');
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitFullName(string $fullName): array
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $fullName) ?? '');
        if ($normalized === '') {
            return ['-', '-'];
        }

        $parts = explode(' ', $normalized);
        $firstName = (string) ($parts[0] ?? '-');
        $lastName = trim(implode(' ', array_slice($parts, 1)));

        if ($lastName === '') {
            $lastName = $firstName;
        }

        return [$firstName, $lastName];
    }
}
