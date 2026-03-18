<?php

namespace Gametech\Game\Repositories\Games;

use Carbon\Carbon;
use Gametech\Core\Eloquent\Repository;
use Gametech\Member\Models\MemberProxy;
use Illuminate\Container\Container as App;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class KickoffRepository extends Repository
{
    protected $responses;

    protected $method;

    protected $debug;

    protected $url;

    protected $agent;

    protected $agentPass;

    protected $passkey;

    protected $secretkey;

    protected $login;

    protected $auth;

    protected $token;

    protected $api_domain;

    protected $api_lobby;

    protected $username;

    protected $password;

    public function __construct($method, $debug, App $app)
    {
        $game = 'kickoff';

        $this->method = $method;
        $this->debug = $debug;
        $this->url = config($this->method.'.'.$game.'.apiurl');
        $this->agent = config($this->method.'.'.$game.'.agent');
        $this->agentPass = config($this->method.'.'.$game.'.agent_pass');
        $this->login = config($this->method.'.'.$game.'.login');
        $this->passkey = config($this->method.'.'.$game.'.passkey');
        $this->secretkey = config($this->method.'.'.$game.'.secretkey');

        $this->api_domain = config($this->method.'.'.$game.'.api_domain');
        $this->api_lobby = config($this->method.'.'.$game.'.api_lobby');

        $this->username = config($this->method.'.'.$game.'.username');
        $this->password = config($this->method.'.'.$game.'.password');

        $this->responses = [];

        parent::__construct($app);
    }

    /* ============================================================
     * Helpers: message/success normalization
     * ============================================================ */

    private function pickMessage(?array $payload): string
    {
        if (! is_array($payload)) {
            return '';
        }
        foreach (['message', 'msg', 'error.message', 'error', 'detail', 'result.message', 'data.message'] as $path) {
            $val = Arr::get($payload, $path);
            if (is_string($val) && $val !== '') {
                return $val;
            }
        }
        $errors = Arr::get($payload, 'errors');
        if (is_array($errors) && ! empty($errors)) {
            foreach ($errors as $item) {
                if (is_string($item) && $item !== '') {
                    return $item;
                }
                if (is_array($item)) {
                    $first = reset($item);
                    if (is_string($first) && $first !== '') {
                        return $first;
                    }
                }
            }
        }

        return '';
    }

    private function computeSuccess(?array $payload, ?int $httpStatus = null): bool
    {
        if (! is_array($payload)) {
            return false;
        }

        $explicit = Arr::get($payload, 'success');
        if (is_bool($explicit)) {
            return $explicit;
        }

        $statusStr = strtoupper((string) Arr::get($payload, 'status', ''));
        $messageStr = strtoupper((string) $this->pickMessage($payload));
        if ($statusStr === 'SUCCESS' || $messageStr === 'SUCCESS') {
            return true;
        }

        $statusCode = Arr::get($payload, 'statusCode');
        if (is_numeric($statusCode) && (int) $statusCode === 200) {
            return true;
        }

        if (is_int($httpStatus) && $httpStatus >= 200 && $httpStatus < 300) {
            return true;
        }

        return false;
    }

    private function normalizeHttpResponse($response): array
    {
        if ($response === false) {
            return ['http_ok' => false, 'status' => 0, 'json' => null, 'body' => null];
        }
        $status = method_exists($response, 'status') ? $response->status() : null;
        $json = method_exists($response, 'json') ? $response->json() : null;
        $body = method_exists($response, 'body') ? $response->body() : null;

        return [
            'http_ok' => method_exists($response, 'successful') ? $response->successful() : false,
            'status' => $status,
            'json' => is_array($json) ? $json : (is_string($body) ? json_decode($body, true) : null),
            'body' => $body,
        ];
    }

    private function extractStatusStr(array $norm): string
    {
        return strtoupper((string) Arr::get($norm, 'json.status', Arr::get($norm, 'status', '')));
    }

    private function sanitizeToken(?string $t): ?string
    {
        if (! is_string($t)) {
            return null;
        }
        $t = preg_replace('/^Bearer\s+/i', '', trim($t));

        return $t !== '' ? $t : null;
    }

    private function isJwtLike(?string $t): bool
    {
        return is_string($t) && substr_count($t, '.') === 2;
    }

    private function isUnauthorized(array $norm): bool
    {
        $status = (int) ($norm['status'] ?? 0);
        if (in_array($status, [401, 403], true)) {
            return true;
        }

        $msg = strtoupper($this->pickMessage($norm['json'] ?? []));

        return Str::contains($msg, ['UNAUTHORIZED', 'TOKEN', 'JWT', 'EXPIRED', 'SIGNATURE', 'BEARER', 'INVALID', 'NOT AUTHORIZED']);
    }

    private function isTokenIssue(array $norm): bool
    {
        $http = (int) ($norm['status'] ?? 0);
        if (in_array($http, [401, 403], true)) {
            return true;
        }

        $status = $this->extractStatusStr($norm); // e.g. ERROR
        $msg = strtoupper($this->pickMessage($norm['json'] ?? []));
        $code = strtoupper((string) Arr::get($norm, 'json.code', ''));

        $needles = ['UNAUTHORIZED', 'NOT AUTHORIZED', 'TOKEN', 'JWT', 'EXPIRED', 'SIGNATURE', 'BEARER', 'INVALID'];

        return $status === 'ERROR'
            || Str::contains($msg, $needles)
            || Str::contains($code, $needles);
    }

    private function invalidateSystemToken(): void
    {
        DB::table('games')->where('id', 'kickoff')->update([
            'token' => null,
            'token_expired' => now()->subMinute()->toDateTimeString(),
            'date_update' => now()->toDateTimeString(),
        ]);
    }

    /* ============================================================
     * Debug
     * ============================================================ */
    public function Debug($response, $custom = false)
    {
        if (! $custom && is_object($response)) {
            $return['body'] = $response->body();
            $return['json'] = $response->json();
            $return['successful'] = $response->successful();
            $return['failed'] = $response->failed();
            $return['clientError'] = $response->clientError();
            $return['serverError'] = $response->serverError();
        } else {
            $return['body'] = is_string($response) ? $response : json_encode($response);
            $return['json'] = $response;
            $return['successful'] = 1;
            $return['failed'] = 1;
            $return['clientError'] = 1;
            $return['serverError'] = 1;
        }
        $this->responses[] = $return;
    }

    /* ============================================================
     * System/Auth token (table: games.id='kickoff', date_update)
     * ============================================================ */

    private function getSystemToken(): ?array
    {
        $row = DB::table('games')->where('id', 'kickoff')->first();
        if (! $row) {
            return null;
        }
        $token = $row->token ?? null;
        $expired = $row->token_expired ?? null;

        return $token ? ['token' => $token, 'expired_at' => $expired] : null;
    }

    private function saveSystemToken(string $token, ?Carbon $expiredAt): void
    {
        DB::table('games')->updateOrInsert(
            ['id' => 'kickoff'],
            [
                'token' => $token,
                'token_expired' => $expiredAt ? $expiredAt->toDateTimeString() : null,
                'date_update' => now()->toDateTimeString(),
            ]
        );
    }

    private function ensureMemberLobbyTokenByUsername(string $username): ?string
    {
        $member = \Gametech\Member\Models\MemberProxy::where('user_name', $username)->orWhere('game_user', $username)->first();
        if (! $member) {
            \Log::warning('ensureMemberLobbyTokenByUsername: member not found', ['username' => $username]);

            return null;
        }

        // token ปัจจุบันจากตาราง members
        $token = $this->sanitizeToken($member->session_page ?? null);
        $expired = $member->session_limit ? Carbon::parse($member->session_limit) : null;

        $needRefresh = ! $this->isJwtLike($token) || ($expired && $expired->isPast());
        if (! $needRefresh) {
            return $token; // ใช้ต่อได้
        }

        // re-auth ด้วยรหัสของ user (จะบันทึก session_page/session_limit ให้)
        $auth = $this->SigninNew($username);

        // โหลดค่าใหม่จาก DB
        $member->refresh();
        $token = $this->sanitizeToken($member->session_page ?? null);

        return $this->isJwtLike($token) ? $token : null;
    }

    private function ensureSystemToken(bool $force = false): ?string
    {
        if (! $force) {
            $cached = $this->getSystemToken();
            if ($cached) {
                $tok = $this->sanitizeToken($cached['token']);
                $exp = $cached['expired_at'] ? Carbon::parse($cached['expired_at']) : null;
                if ($this->isJwtLike($tok) && (! $exp || $exp->isFuture())) {
                    return $tok;
                }
            }
        }

        // ยิง auth ใหม่ (จะ save เข้า games ผ่าน GameCurlAuth)
        $param = [
            'username' => $this->username,
            'password' => $this->password,
            'agentName' => $this->login,
        ];
        $resp = $this->GameCurlAuth($param, $this->url.'auth/signin');

        if (($resp['success'] ?? false) !== true) {
            return null;
        }

        $token = $this->sanitizeToken(Arr::get($resp, 'result.accessToken'));
        if (! $this->isJwtLike($token)) {
            return null;
        }

        return $token; // GameCurlAuth ได้บันทึกแล้ว
    }

    /* ============================================================
     * Lobby token (per member: members.session_page/session_limit)
     *  - member primary key = code
     * ============================================================ */

    private function saveMemberLobbyToken($member, string $token, ?Carbon $expiredAt): bool
    {
        // เขียนผ่าน Eloquent ให้เคารพ connection/table/primary key (code)
        try {
            $member->forceFill([
                'session_page' => $token,
                'session_limit' => $expiredAt ? $expiredAt->toDateTimeString() : null,
            ]);
            $member->save(); // ถ้าโมเดลปิด timestamps ก็ไม่แตะ updated_at

            return true;
        } catch (\Throwable $e) {
            // fallback ด้วย Query Builder เคารพ PK = code
            try {
                $conn = DB::connection($member->getConnectionName());
                $table = $member->getTable();
                $pkName = method_exists($member, 'getKeyName') ? $member->getKeyName() : 'code';
                $pk = $member->getKey() ?? $member->code;

                $conn->table($table)
                    ->where($pkName, $pk)
                    ->update([
                        'session_page' => $token,
                        'session_limit' => $expiredAt ? $expiredAt->toDateTimeString() : null,
                    ]);

                return true;
            } catch (\Throwable $ex) {
                \Log::warning('saveMemberLobbyToken failed', [
                    'err' => $ex->getMessage(),
                    'table' => method_exists($member, 'getTable') ? $member->getTable() : null,
                ]);

                return false;
            }
        }
    }

    private function ensureMemberLobbyToken(): ?string
    {
        if (! auth()->guard('customer')->check()) {
            return null;
        }
        $member = auth()->guard('customer')->user();

        $token = $this->sanitizeToken($member->session_page ?? null);
        $expired = $member->session_limit ? Carbon::parse($member->session_limit) : null;

        $needRefresh = ! $this->isJwtLike($token) || ($expired && $expired->isPast());
        if (! $needRefresh) {
            return $token;
        }

        // Signin ใหม่และบันทึก
        $this->Signin();

        // โหลดใหม่จาก DB
        $member->refresh();
        $token = $this->sanitizeToken($member->session_page ?? null);

        return $this->isJwtLike($token) ? $token : null;
    }

    /* ============================================================
     * Auth (base) — คงสัญญาเดิม แต่ดึงจาก games ก่อน
     * ============================================================ */

    public function Auth()
    {
        $token = $this->ensureSystemToken();
        if ($token) {
            return [
                'success' => true,
                'msg' => 'SUCCESS',
                'result' => ['accessToken' => $token],
            ];
        }

        // สำรอง หากไม่สำเร็จ
        $param = [
            'username' => $this->username,
            'password' => $this->password,
            'agentName' => $this->login,
        ];

        return $this->GameCurlAuth($param, $this->url.'auth/signin');
    }

    public function GameCurlAuthNew($param, $action)
    {
        $response = rescue(function () use ($param, $action) {
            return Http::timeout(30)->withOptions(['debug' => false])->asJson()->post($action, $param);
        }, function () {
            return false;
        }, true);

        if ($response === false) {
            return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
        }

        $norm = $this->normalizeHttpResponse($response);
        $json = $norm['json'] ?? [];
        $msg = $this->pickMessage($json);
        $ok = $this->computeSuccess($json, $norm['status']);
        if (! $ok && strtoupper($msg) === 'SUCCESS') {
            $ok = true;
        }

        $json['success'] = $ok;
        $json['msg'] = $msg ?: ($ok ? 'SUCCESS' : 'FAILED');

        return $json;
    }

    public function GameCurlAuth($param, $action)
    {
        $response = rescue(function () use ($param, $action) {
            return Http::timeout(15)->asJson()->post($action, $param);
        }, function () {
            return false;
        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }

        if ($response === false) {
            return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
        }

        $norm = $this->normalizeHttpResponse($response);
        $json = $norm['json'] ?? [];
        $msg = $this->pickMessage($json);

        $statusStr = strtoupper((string) Arr::get($json, 'status', ''));
        $ok = $this->computeSuccess($json, $norm['status']);
        if (! $ok && $statusStr === 'SUCCESS') {
            $ok = true;
        }

        // ถ้าได้ token ใหม่ → บันทึกเข้า games (id='kickoff', date_update)
        if ($ok) {
            $token = $this->sanitizeToken(Arr::get($json, 'result.accessToken'));
            if ($this->isJwtLike($token)) {
                $expiresIn = Arr::get($json, 'result.expiresIn');
                $expiredAt = is_numeric($expiresIn) ? now()->addSeconds((int) $expiresIn - 60)
                    : now()->addMinutes((int) env('KICKOFF_AGENT_TOKEN_TTL_MIN', 1440));
                $this->saveSystemToken($token, $expiredAt);
            }
        }

        $json['success'] = $ok;
        $json['msg'] = $msg ?: ($ok ? 'SUCCESS' : 'FAILED');

        return $json;
    }

    /* ============================================================
     * Generic GameCurl (Bearer: System token, auto re-auth)
     * ============================================================ */

    public function GameCurl($param, $action)
    {
        $doRequest = function (string $token) use ($param, $action) {
            $header = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$token];

            return Http::timeout(15)->withHeaders($header)->post($action, $param);
        };

        $token = $this->ensureSystemToken();
        if (! $this->isJwtLike($token)) {
            $token = $this->ensureSystemToken(true);
            if (! $token) {
                return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
            }
        }

        $response = rescue(fn () => $doRequest($token), fn () => false, true);
        if ($this->debug) {
            $this->Debug($response);
        }
        if ($response === false) {
            return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
        }

        $norm = $this->normalizeHttpResponse($response);

        // token ใช้ไม่ได้ (แม้ HTTP 200 และ status=ERROR) → invalidate + force re-auth + retry
        if ($this->isUnauthorized($norm) || $this->isTokenIssue($norm)) {
            $this->invalidateSystemToken();
            $token = $this->ensureSystemToken(true);
            if (! $token) {
                return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
            }
            $response = rescue(fn () => $doRequest($token), fn () => false, true);
            $norm = $this->normalizeHttpResponse($response);
        }

        $json = $norm['json'] ?? [];
        $msg = $this->pickMessage($json);
        $ok = $this->computeSuccess($json, $norm['status']);
        if (! $ok && strtoupper((string) Arr::get($json, 'status', '')) === 'SUCCESS') {
            $ok = true;
        }

        $json['msg'] = $msg ?: ($ok ? 'SUCCESS' : 'FAILED');
        $json['success'] = $ok;

        return $json;
    }

    public function GameCurlGet($param, $action)
    {
        $doRequest = function (string $token) use ($param, $action) {
            $header = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$token];

            return Http::timeout(15)->withHeaders($header)->get($action, $param);
        };

        $token = $this->ensureSystemToken();
        if (! $this->isJwtLike($token)) {
            $token = $this->ensureSystemToken(true);
            if (! $token) {
                return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
            }
        }

        $response = rescue(fn () => $doRequest($token), fn () => false, true);
        if ($this->debug) {
            $this->Debug($response);
        }
        if ($response === false) {
            return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
        }

        $norm = $this->normalizeHttpResponse($response);

        if ($this->isUnauthorized($norm) || $this->isTokenIssue($norm)) {
            $this->invalidateSystemToken();
            $token = $this->ensureSystemToken(true);
            if (! $token) {
                return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
            }
            $response = rescue(fn () => $doRequest($token), fn () => false, true);
            $norm = $this->normalizeHttpResponse($response);
        }

        $json = $norm['json'] ?? [];
        $msg = $this->pickMessage($json);
        $ok = $this->computeSuccess($json, $norm['status']);
        if (! $ok && strtoupper((string) Arr::get($json, 'status', '')) === 'SUCCESS') {
            $ok = true;
        }

        $json['msg'] = $msg ?: ($ok ? 'SUCCESS' : 'FAILED');
        $json['success'] = $ok;

        return $json;
    }

    /* ============================================================
     * Lobby flows (Bearer: Member token, auto re-signin)
     * ============================================================ */

    public function GameCurlLobby($param, $action)
    {
        $doRequest = function (string $token) use ($param, $action) {
            $header = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$token];

            return Http::timeout(15)->withHeaders($header)->asJson()->post($action, $param);
        };

        $token = $this->ensureMemberLobbyToken();
        if (! $token) {
            return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
        }

        $response = rescue(fn () => $doRequest($token), fn () => false, true);
        if ($response === false) {
            return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
        }

        $norm = $this->normalizeHttpResponse($response);

        if ($this->isUnauthorized($norm) || $this->isTokenIssue($norm)) {
            // re-signin + retry
            $this->Signin();
            $member = auth()->guard('customer')->user();
            $member?->refresh();
            $token = $this->sanitizeToken($member->session_page ?? null);
            if (! $this->isJwtLike($token)) {
                return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
            }

            $response = rescue(fn () => $doRequest($token), fn () => false, true);
            $norm = $this->normalizeHttpResponse($response);
        }

        $json = $norm['json'] ?? [];
        $msg = $this->pickMessage($json);
        $ok = $this->computeSuccess($json, $norm['status']);
        if (! $ok && strtoupper($msg) === 'SUCCESS') {
            $ok = true;
        }

        $json['msg'] = $msg ?: ($ok ? 'SUCCESS' : 'FAILED');
        $json['success'] = $ok;

        return $json;
    }

    public function GameCurlGetLobby($param, $action)
    {
        $doRequest = function (string $token) use ($action) {
            $header = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$token];

            return Http::timeout(15)->withHeaders($header)->get($action);
        };

        $token = $this->ensureMemberLobbyToken();
        if (! $token) {
            return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
        }

        $response = rescue(fn () => $doRequest($token), fn () => false, true);
        if ($response === false) {
            return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
        }

        $norm = $this->normalizeHttpResponse($response);

        if ($this->isUnauthorized($norm) || $this->isTokenIssue($norm)) {
            $this->Signin();
            $member = auth()->guard('customer')->user();
            $member?->refresh();
            $token = $this->sanitizeToken($member->session_page ?? null);
            if (! $this->isJwtLike($token)) {
                return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
            }

            $response = rescue(fn () => $doRequest($token), fn () => false, true);
            $norm = $this->normalizeHttpResponse($response);
        }

        $json = $norm['json'] ?? [];
        $msg = $this->pickMessage($json);
        $ok = $this->computeSuccess($json, $norm['status']);
        if (! $ok && strtoupper($msg) === 'SUCCESS') {
            $ok = true;
        }

        $json['msg'] = $msg ?: ($ok ? 'SUCCESS' : 'FAILED');
        $json['success'] = $ok;

        return $json;
    }

    public function GameCurlGetLobbyPublic($param, $action)
    {
        $response = rescue(function () use ($action) {
            return Http::timeout(15)->get($action);
        }, function () {
            return false;
        }, true);

        if ($response === false) {
            return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
        }

        $norm = $this->normalizeHttpResponse($response);
        $json = $norm['json'] ?? [];
        $msg = $this->pickMessage($json);
        $ok = $this->computeSuccess($json, $norm['status']);
        if (! $ok && strtoupper($msg) === 'SUCCESS') {
            $ok = true;
        }

        $json['msg'] = $msg ?: ($ok ? 'SUCCESS' : 'FAILED');
        $json['success'] = $ok;

        return $json;
    }

    /* ============================================================
     * Public actions
     * ============================================================ */

    public function addGameAccount($data): array
    {
        // รับได้ทั้ง array/stdClass/Eloquent → แปลงเป็น array ก่อน
        $payload = $this->normalizePayload($data);

        // ให้ newUser จัดการสมัคร “ครั้งเดียว” และส่งผลกลับ
        $result = $this->newUser($payload);

        // ถ้า newUser สมัครสำเร็จแล้ว จะมี created=true และ remote ผลลัพธ์ฝั่งเกมมาให้
        if (($result['success'] ?? false) === true && ($result['created'] ?? false) === true) {
            return $result['remote'] ?? $result;
        }

        // เผื่อกรณีพิเศษที่ newUser หา username ให้ได้ แต่ยังไม่ได้ยิงสมัคร (ปัจจุบันเราให้สมัครใน newUser อยู่แล้ว)
        if (($result['success'] ?? false) === true && isset($result['account'])) {
            return $this->addUser($result['account'], $payload);
        }

        return $result;
    }

    public function newUser(array $data): array
    {
        $return = ['success' => true];

        // ----- prefix: เอาอักษร/ตัวเลขจาก upline (ไม่กรองให้เหลือแต่ตัวเลข) -----
        $prefix = (string) ($this->login ?? '');
//        $prefix = preg_replace('/[^a-zA-Z0-9]/', '', $prefixRaw); // เก็บเฉพาะ a-zA-Z0-9
        //        $prefix    = mb_substr($prefix, 0, 5);
        //        if (mb_strlen($prefix) < 5) {
        //            $prefix = str_pad($prefix, 5, 'x', STR_PAD_RIGHT); // pad ด้วยตัวอักษร ไม่ใช่ศูนย์
        //        }

        // ----- รอบแรกใช้เบอร์จริง (ต้องเป็น 10 หลัก) -----
        $tel = isset($data['tel']) ? (string) $data['tel'] : '';
        $tel = preg_replace('/\D/', '', $tel);
        if (mb_strlen($tel) !== 10) {
            return ['success' => false, 'message' => 'เบอร์โทรไม่ถูกต้อง (ต้องเป็นเลข 10 หลัก)'];
        }

        $maxAttempts = 20;
        $username = null;
        $remoteSaved = null;

        for ($i = 0; $i < $maxAttempts; $i++) {
            // รอบแรกใช้เบอร์จริง, ถัดไปสุ่มเฉพาะ "suffix 10 หลัก" ที่ไม่ดูเหมือนเบอร์โทร
            $suffix = ($i === 0)
                ? $tel
                : $this->generatePhoneSuffix(10, [
                    'prefer_prefix' => '5',    // เริ่มด้วย 5 → ไม่เหมือนเบอร์โทรไทย
                    'avoid_existing' => true,   // กันชนกับ members.tel
                    'avoid_games_user' => true,   // กันกรณี user_name บางระบบเก็บเป็นเบอร์ตรง ๆ
                    'max_try' => 100,
                ]);

            // ถ้าเป็นรอบสุ่ม (i>0) และดันไปชนเบอร์จริงในระบบ → ข้ามทันที
            if ($i > 0 && DB::table('members')->where('tel', $suffix)->exists()) {
                continue;
            }

            $candidate = $prefix.$suffix; // รวม 15 ตัว (5 + 10)

            // กันชนฝั่งฐานเราเองก่อน
            if (DB::table('games_user')->where('user_name', $candidate)->exists()) {
                continue;
            }

            // ยิงสมัครฝั่งเกม (สมัครจริง “ครั้งเดียว” ใน loop นี้)
            try {
                $remote = $this->addUser($candidate, $data);
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => $e->getMessage() ?: 'สมัครผู้ใช้ฝั่งเกมล้มเหลว'];
            }

            // ตีความผลลัพธ์ฝั่งเกม
            $remoteSuccess = ($remote['success'] ?? false) === true
                || strtoupper((string) ($remote['status'] ?? '')) === 'SUCCESS';

            if ($remoteSuccess) {
                $username = $candidate;
                $remoteSaved = $remote;  // เก็บไว้ส่งกลับให้ชั้นบนใช้ต่อได้เลย (มี user/pass)
                break;
            }

            $msg = strtolower((string) ($remote['msg'] ?? $remote['message'] ?? ''));
            if ($msg !== '' && preg_match('/already\s*exist/i', $msg)) {
                // ฝั่งเกมแจ้งว่าชื่อนี้ถูกใช้แล้ว -> วนสุ่ม suffix ต่อ
                continue;
            }

            // ความล้มเหลวแบบอื่น ให้หยุดและส่งต่อข้อความ
            return ['success' => false, 'message' => ($remote['msg'] ?? $remote['message'] ?? 'สมัครผู้ใช้ฝั่งเกมล้มเหลว')];
        }

        if (! $username) {
            return ['success' => false, 'message' => 'ไม่สามารถสร้างบัญชีใหม่ที่ไม่ซ้ำได้ในขณะนี้'];
        }

        // ส่งกลับให้ชั้นบน “ไม่ต้องยิงสมัครซ้ำ”
        return [
            'success' => true,
            'created' => true,          // บอกว่าได้สมัครจริงแล้ว
            'account' => $username,     // ชื่อผู้ใช้เต็ม (prefix+suffix)
            'remote' => $remoteSaved,  // payload ผลลัพธ์จากค่าย (มี user_name/user_pass)
        ];
    }

    private function normalizePayload($data): array
    {
        if (is_array($data)) {
            return $data;
        }

        if (is_object($data)) {
            if (method_exists($data, 'toArray')) {
                return $data->toArray();
            }
            if ($data instanceof \stdClass) {
                return (array) $data;
            }
        }

        return (array) $data;
    }

    /**
     * สร้าง suffix N หลัก สำหรับต่อท้าย prefix (เช่น 10)
     * - ค่าเริ่มต้นจะเริ่มด้วย $prefer_prefix (เช่น '5') เพื่อไม่ให้ดูเหมือนเบอร์จริง
     * - เลือกหลีกเลี่ยงชนกับ members.tel และ/หรือ games_user.user_name ได้
     */
    private function generatePhoneSuffix(int $length = 10, array $opts = []): string
    {
        $length = max(1, $length);

        $preferPrefix = $opts['prefer_prefix'] ?? '5';
        $avoidExisting = $opts['avoid_existing'] ?? true;
        $avoidGamesUser = $opts['avoid_games_user'] ?? true;
        $maxTry = $opts['max_try'] ?? 50;

        $preferIsRandom = is_null($preferPrefix);

        for ($attempt = 0; $attempt < $maxTry; $attempt++) {
            // หลักแรก
            $first = $preferIsRandom
                ? (string) random_int(1, 9)
                : substr((string) $preferPrefix, 0, 1);

            // เติมที่เหลือเป็นก้อนละ 9 หลัก เพื่อเลี่ยง overflow
            $result = $first;
            while (strlen($result) < $length) {
                $chunk = (string) random_int(0, 999_999_999);
                $result .= str_pad($chunk, 9, '0', STR_PAD_LEFT);
            }
            $suffix = substr($result, 0, $length);

            // กันไม่ให้ไปตรงกับเบอร์จริงในระบบ
            if ($avoidExisting && DB::table('members')->where('tel', $suffix)->exists()) {
                continue;
            }

            // กัน edge case บางระบบใช้เบอร์เป็น user_name ตรง ๆ
            if ($avoidGamesUser && DB::table('games_user')->where('user_name', $suffix)->exists()) {
                continue;
            }

            return $suffix;
        }

        // fallback กันลูปยาวเกิน
        $fallback = ($preferIsRandom ? (string) random_int(1, 9) : substr((string) $preferPrefix, 0, 1))
            .substr((string) round(microtime(true) * 1000).(string) random_int(0, 9999), 0, $length - 1);

        return str_pad(substr($fallback, 0, $length), $length, '5', STR_PAD_RIGHT);
    }

    /**
     * อ่านค่าง่าย ๆ จาก array/obj รองรับ key แบบซ้อนด้วย dot notation
     */
    protected function payload_get($data, string $key, $default = null)
    {
        // ถ้าเป็น array แล้ว ใช้ data_get ได้เลย
        if (is_array($data)) {
            return data_get($data, $key, $default);
        }

        // ถ้าเป็น object ใช้ data_get ได้เช่นกัน
        if (is_object($data)) {
            return data_get($data, $key, $default);
        }

        return $default;
    }

    /**
     * ทำความสะอาดเบอร์โทร เอาเฉพาะ 0-9
     */
    protected function onlyDigits(string $value): string
    {
        return preg_replace('/\D/', '', $value);
    }

    /**
     * สุ่มตัวเลข N หลัก (เผื่อคุณมีของเดิมชื่อเดียวกัน)
     */
    protected function generateNDigits(int $n): string
    {
        $min = (int) str_pad('1', $n, '0');          // 100... (n หลัก)
        $max = (int) str_pad('', $n, '9');          // 999... (n หลัก)

        return (string) random_int($min, $max);
    }

    public function addUser($username, $data): array
    {
        $return['success'] = false;

        $user_pass = 'Aa'.rand(100000, 999999);

        $param = [
            'upLine' => $this->login,
            'username' => $username,
            'password' => $user_pass,
            'firstName' => $data['firstname'],
            'lastName' => $data['lastname'],
            'tel' => $data['tel'],
            'lineId' => '',
            'bankId' => 0,
            'accNo' => $data['acc_no'],
            'accName' => $data['name'],
            'credit' => 0,
        ];

        $response = $this->GameCurl($param, $this->url.'users/register');

        $path = storage_path('logs/seamless/kickoff_register_'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r($param, true), FILE_APPEND);
        file_put_contents($path, print_r($response, true), FILE_APPEND);

        if (($response['success'] ?? false) === true) {
            $return['success'] = true;
            $return['user_name'] = $username;
            $return['user_pass'] = $user_pass;
        } else {
            $return['msg'] = $response['msg'] ?? 'FAILED';
            $return['success'] = false;
        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $return;
    }

    public function changePass($data): array
    {
        $return['success'] = false;

        $signin = $data['user_name'].$data['user_pass'].'KoB#APiGat3.WAy!';
        $url = $this->api_domain.'reset_password.php';

        $param = [
            'username' => $data['user_name'],
            'password' => $data['user_pass'],
            's' => md5($signin),
        ];

        $response = Http::timeout(15)
            ->withHeaders(['Cookie' => '__cfduid=d79a4ff5003793fa92a06467b25be2ee91618735441'])
            ->asForm()->post($url, $param);

        $return = $response->json();

        if ($response->successful()) {
            $code = Arr::get($return, 'code');
            if ($code === 'success') {
                $return['msg'] = 'เปลี่ยนรหัสผ่าน เรียบร้อย';
                $return['success'] = true;
            } else {
                $return['success'] = false;
                $return['msg'] = 'ไม่สามารถเปลี่ยนรหัส ได้ในขณะนี้';
            }
        } else {
            $return['success'] = false;
            $return['msg'] = 'ไม่สามารถเปลี่ยนรหัส ได้ในขณะนี้';
        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $return;
    }

    public function viewBalance($username): array
    {
        $return = ['success' => false, 'score' => 0];

        $param = ['username' => $username];
        $response = $this->GameCurlGet($param, $this->url.'users/info');
        $path = storage_path('logs/seamless/balance'.now()->format('Y_m_d').'.log');
        $param['date'] = now()->toDateTimeString();
        $param['response'] = $response;
        file_put_contents($path, print_r($param, true), FILE_APPEND);

        if (($response['success'] ?? false) === true) {
            $return['msg'] = 'Complete';
            $return['connect'] = true;
            $return['success'] = true;
            $score = Arr::get($response, 'result.credit', 0);
            $return['score'] = is_numeric($score) ? $score : 0;
        } else {
            $return['msg'] = $response['msg'] ?? 'FAILED';
            $return['success'] = false;
            $return['connect'] = true;
        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $return;
    }

    public function deposit($username, $amount): array
    {
        $return['success'] = false;

        $score = $amount;

        if ($score < 0) {
            $return['msg'] = 'เกิดข้อผิดพลาด จำนวนยอดเงินไม่ถูกต้อง';
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } elseif (empty($username)) {
            $return['msg'] = 'เกิดข้อผิดพลาด ไม่พบข้อมูลรหัสสมาชิก';
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } else {
            $transID = 'DP'.date('YmdHis');
            $param = [
                'username' => $username,
                'type' => 'deposit',
                'amount' => $score,
            ];

            $balance = $this->viewBalance($username);
            $response = $this->GameCurl($param, $this->url.'users/finance');

            $path = storage_path('logs/seamless/deposit'.now()->format('Y_m_d').'.log');
            $param['date'] = now()->toDateTimeString();
            $param['response'] = $response;
            file_put_contents($path, print_r($param, true), FILE_APPEND);

            if (($response['success'] ?? false) === true) {
                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $balance['score'] + $score;
                $return['before'] = $balance['score'];
                $return['msg'] = 'Complete';
            } else {
                $return['success'] = false;
                $return['msg'] = $response['msg'] ?? 'FAILED';
            }
        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $return;
    }

    public function withdraw($username, $amount): array
    {
        $return['success'] = false;

        $score = $amount;

        if ($score < 1) {
            $return['msg'] = 'เกิดข้อผิดพลาด จำนวนยอดเงินไม่ถูกต้อง';
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } elseif (empty($username)) {
            $return['msg'] = 'เกิดข้อผิดพลาด ไม่พบข้อมูลรหัสสมาชิก';
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } else {
            $transID = 'WD'.date('YmdHis');
            $param = [
                'username' => $username,
                'type' => 'withdraw',
                'amount' => $score,
            ];

            $balance = $this->viewBalance($username);
            $response = $this->GameCurl($param, $this->url.'users/finance');

            if (($response['success'] ?? false) === true) {
                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $balance['score'] - $score;
                $return['before'] = $balance['score'];
                $return['msg'] = 'Complete';
            } else {
                $return['success'] = false;
                $return['msg'] = $response['msg'] ?? 'FAILED';
            }
        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $return;
    }

    public function gameList($product_id, $provider)
    {
        $host = config('app.user_domain_url');
        $cacheKey = "private:{$provider}:gamelist:{$host}";
        $ttlMinutes = (int) env('PROVIDER_PUBLIC_CACHE_MINUTES', 5);

        if (request()->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        if ($cached = Cache::get($cacheKey)) {
            $cached['cache'] = true;

            return $cached;
        }

        $return = ['success' => false, 'cache' => false];
        $param = ['lobbyId' => (int) $provider];

        $response = $this->GameCurlLobby($param, $this->api_lobby.'v1/lobby/game-list');

        if (($response['success'] ?? false) === true) {

            $games = $response['result'] ?? [];

            // 👉 สร้าง map: gameCode => ข้อมูลเกม
            $mapByCode = collect($games)->mapWithKeys(function ($g) {
                // gameCode จาก API เป็น string อยู่แล้ว → cast เป็น string ทับอีกทีให้ชัวร์
                $code = (string) ($g['gameCode'] ?? '');

                // กันเคสไม่มี gameCode
                if ($code === '') {
                    return [];
                }

                return [
                    $code => [
                        'name' => $g['gameName'] ?? null,
                        'image' => $g['gameImage'] ?? null,
                        'image_500' => $g['gameImage1'] ?? null,
                        'image_alt' => $g['gameImage2'] ?? null,
                        'type' => $g['gameType'] ?? null,
                    ],
                ];
            })->toArray();

            $return['success'] = true;
            $return['msg'] = $response['msg'] ?? null;
            $return['games'] = $games;
            $return['mapByCode'] = $mapByCode;
            $return['cache'] = false;

            Cache::put($cacheKey, $return, now()->addMinutes($ttlMinutes));
        } else {
            $return['msg'] = $response['msg'] ?? 'FAILED';
            $return['games'] = [];
            $return['mapByCode'] = [];
        }

        return $return;
    }

    public function Signin()
    {
        if (! auth()->guard('customer')->check()) {
            return ['success' => false, 'msg' => 'not logged in'];
        }

        $member = auth()->guard('customer')->user();
        $gameUser = $member->gameUser;

        $param = [
            'username' => $gameUser->user_name,
            'password' => $gameUser->user_pass,
        ];

        $resp = $this->GameCurlAuthNew($param, $this->api_lobby.'v1/auth/signin');

        if (($resp['success'] ?? false) === true) {
            $token = $this->sanitizeToken(Arr::get($resp, 'result.bearerToken'));
            if ($this->isJwtLike($token)) {
                $expiresIn = Arr::get($resp, 'result.expiresIn');
                $expiredAt = is_numeric($expiresIn) ? now()->addSeconds((int) $expiresIn - 60)
                    : now()->addMinutes((int) env('KICKOFF_LOBBY_TOKEN_TTL_MIN', 30));
                $this->saveMemberLobbyToken($member, $token, $expiredAt);
            }
        }

        return $resp;
    }

    public function providerList($data)
    {
        $host = config('app.user_domain_url');
        $cacheKey = "private:lobby:providers:{$host}";
        $ttlMinutes = (int) env('PROVIDER_PUBLIC_CACHE_MINUTES', 5);

        if (request()->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        if ($cached = Cache::get($cacheKey)) {
            $cached['cache'] = true;

            return $cached;
        }

        $return = ['success' => false, 'cache' => false];
        $param = ['username' => $data['username'], 'password' => $data['password']];

        $response = $this->GameCurlGetLobby($param, $this->api_lobby.'v1/lobby');

        if (($response['success'] ?? false) === true) {

            // 👉 MAP รหัส provider → ชื่อ provider ก่อน cache
            $providers = $response['result'] ?? [];

            // 👉 สร้าง map จาก lobbyId → ข้อมูลที่สนใจ
            $mapById = collect($providers)->mapWithKeys(function ($p) {
                return [
                    $p['lobbyId'] => [
                        'name' => $p['lobbyName'],
                        'prefix' => $p['prefix'],
                        'type' => $p['gameType'],
                        'code' => $p['gameTypeCode'],
                    ],
                ];
            })->toArray();

            $return['success'] = true;
            $return['msg'] = $response['message'] ?? null;
            $return['provider'] = $providers;
            $return['mapById'] = $mapById; // 👈 เก็บไว้ใน cache ด้วย

            Cache::put($cacheKey, $return, now()->addMinutes($ttlMinutes));
        } else {
            $return['msg'] = $response['msg'] ?? 'FAILED';
            $return['provider'] = [];
            $return['mapById'] = [];
        }

        return $return;
    }

    public function providerListPublic()
    {
        $host = config('app.user_domain_url');
        $cacheKey = "public:lobby:providers:{$host}";
        $ttlMinutes = (int) env('PROVIDER_PUBLIC_CACHE_MINUTES', 5);

        if (request()->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        if ($cached = Cache::get($cacheKey)) {
            $cached['cache'] = true;

            return $cached;
        }

        $return = ['success' => false, 'cache' => false];
        $response = $this->GameCurlGetLobbyPublic([], $this->api_lobby.'v1/public/lobby');

        if (($response['success'] ?? false) === true) {
            $providers = $response['result'] ?? [];

            // 👉 สร้าง map จาก lobbyId → ข้อมูลที่สนใจ
            $mapById = collect($providers)->mapWithKeys(function ($p) {
                return [
                    $p['lobbyId'] => [
                        'name' => $p['lobbyName'],
                        'prefix' => $p['prefix'],
                        'type' => $p['gameType'],
                        'code' => $p['gameTypeCode'],
                    ],
                ];
            })->toArray();

            $return['success'] = true;
            $return['msg'] = $response['message'] ?? null;
            $return['provider'] = $providers;
            $return['mapById'] = $mapById; // 👈 เก็บไว้ใน cache ด้วย
            Cache::put($cacheKey, $return, now()->addMinutes($ttlMinutes));
        } else {
            $return['msg'] = $response['msg'] ?? 'FAILED';
            $return['provider'] = [];
        }

        return $return;
    }

    public function login($data)
    {
        $return['success'] = false;
        $Agent = new Agent;
        $mobile = $Agent->isMobile();

        $param = [
            'lobbyId'  => (int) $data['provider'],
            'gameCode' => $data['gameCode'],
            'mobile'   => $mobile,
            'ip'       => request()->ip(),
            'homePage' => url('/'),
        ];

        $path = storage_path('logs/seamless/login'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r($param, true), FILE_APPEND);

        $response = $this->GameCurlLobby($param, $this->api_lobby.'v1/lobby/launch');

        file_put_contents($path, print_r($response, true), FILE_APPEND);

        if (($response['success'] ?? false) === true) {
            // ใช้ Arr::get ป้องกัน undefined index
            $url = Arr::get($response, 'result');

            if (is_string($url) && $url !== '') {
                $return['success'] = true;
                $return['url'] = $url;
            } else {
                // success จริง แต่ค่ายไม่ส่ง URL มา → ถือว่า error ฝั่งค่าย
                $return['success'] = false;
                $return['msg'] = $response['msg'] ?? 'ไม่พบ URL จากค่ายเกม';
            }
        } else {
            $return['success'] = false;
            $return['msg'] = $response['msg'] ?? 'FAILED';
        }

        $return['api'] = $response;

        return $return;
    }


    public function GetTurn($param, $action = 'users/turn')
    {
        $startdate = $param['startDate'];

        $doRequest = function (string $token) use ($param, $action) {
            $header = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$token];
            $url = $this->url.$action;

            return Http::timeout(15)->withHeaders($header)->get($url, $param);
        };

        $token = $this->ensureSystemToken();
        if (! $token) {
            return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
        }

        $response = rescue(fn () => $doRequest($token), fn () => false, true);
        if ($this->debug) {
            $this->Debug($response);
        }
        if ($response === false) {
            return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
        }

        $norm = $this->normalizeHttpResponse($response);

        if ($this->isUnauthorized($norm) || $this->isTokenIssue($norm)) {
            $this->invalidateSystemToken();
            $token = $this->ensureSystemToken(true);
            if (! $token) {
                return ['success' => false, 'msg' => 'เชื่อมต่อไม่ได้'];
            }
            $response = rescue(fn () => $doRequest($token), fn () => false, true);
            $norm = $this->normalizeHttpResponse($response);
        }

        $json = $norm['json'] ?? [];

        $path = storage_path('logs/seamless/winlose'.$startdate.'-'.time().'.log');
        file_put_contents($path, print_r($json, true), FILE_APPEND);

        $msg = $this->pickMessage($json);
        $ok = $this->computeSuccess($json, $norm['status']);
        if (! $ok && strtoupper((string) Arr::get($json, 'status', '')) === 'SUCCESS') {
            $ok = true;
        }

        $json['msg'] = $msg ?: ($ok ? 'SUCCESS' : 'FAILED');
        $json['success'] = $ok;

        return $json;
    }

    public function SigninNew($username)
    {
        $member = MemberProxy::where('user_name', $username)->orWhere('game_user', $username)->first();
        $gameUser = $member->gameUser;
        $param = [
            'username' => $gameUser->user_name,
            'password' => $gameUser->user_pass,
        ];

        $resp = $this->GameCurlAuthNew($param, $this->api_lobby.'v1/auth/signin');

        if (($resp['success'] ?? false) === true) {
            $token = $this->sanitizeToken(Arr::get($resp, 'result.bearerToken'));
            if ($this->isJwtLike($token)) {
                $expiresIn = Arr::get($resp, 'result.expiresIn');
                $expiredAt = is_numeric($expiresIn) ? now()->addSeconds((int) $expiresIn - 60)
                    : now()->addMinutes((int) env('KICKOFF_LOBBY_TOKEN_TTL_MIN', 30));
                $this->saveMemberLobbyToken($member, $token, $expiredAt);
            }
        }

        return $resp;
    }

    public function GameCurlLobbyNew(string $username, array $param, string $action): array
    {
        $out = ['success' => false, 'msg' => 'UNKNOWN', 'status' => 0, 'result' => null, 'raw' => null];

        $doRequest = function (string $token) use ($param, $action) {
            $header = ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'Authorization' => 'Bearer '.$token];

            return Http::timeout(30)->withHeaders($header)->post($action, $param);
        };

        try {
            $auth = $this->SigninNew($username);
            $token = $this->sanitizeToken(Arr::get($auth, 'result.bearerToken'));
            if (! $this->isJwtLike($token)) {
                $out['msg'] = 'ไม่สามารถยืนยันตัวตน (Signin) ได้';

                return $out;
            }

            $resp = $doRequest($token);
            $out['status'] = $resp->status();
            $out['raw'] = $resp->body();

            $norm = $this->normalizeHttpResponse($resp);

            if ($this->isUnauthorized($norm) || $this->isTokenIssue($norm)) {
                $auth = $this->SigninNew($username);
                $token = $this->sanitizeToken(Arr::get($auth, 'result.bearerToken'));
                if (! $this->isJwtLike($token)) {
                    $out['msg'] = 'ไม่สามารถยืนยันตัวตน (Signin) ได้';

                    return $out;
                }
                $resp = $doRequest($token);
                $norm = $this->normalizeHttpResponse($resp);
                $out['status'] = $resp->status();
                $out['raw'] = $resp->body();
            }

            $json = $norm['json'] ?? null;
            if (($resp->successful() ?? false) && is_array($json)) {
                $msg = $this->pickMessage($json);
                $ok = $this->computeSuccess($json, $resp->status());
                if (! $ok && strtoupper($msg) === 'SUCCESS') {
                    $ok = true;
                }
                if (! $ok && (($json['statusCode'] ?? null) === 200)) {
                    $ok = true;
                }

                $out['msg'] = $msg ?: 'SUCCESS';
                $out['result'] = $json['result'] ?? null;
                $out['success'] = $ok;

                return $out;
            }

            $out['msg'] = method_exists($resp, 'reason') ? ($resp->reason() ?: 'HTTP '.$resp->status()) : 'HTTP';

            return $out;

        } catch (\Throwable $e) {
            $out['msg'] = 'เชื่อมต่อไม่ได้: '.$e->getMessage();
            $out['status'] = 0;
            try {
                $path = storage_path('logs/seamless/kickoff_curl'.now()->format('Y_m_d').'.log');
                file_put_contents($path, print_r(['param' => $param, 'error' => $out['msg']], true), FILE_APPEND);
            } catch (\Throwable $ignore) {
            }

            return $out;
        }
    }

    public function gameLog(array $data): array
    {
        $ret = ['success' => false, 'msg' => '', 'data' => null];

        $username = (string) ($data['username'] ?? '');
        if ($username === '') {
            $ret['msg'] = 'username is required';

            return $ret;
        }

        // ป้องกันค่าขาเข้า
        $param = [
            'pageIndex' => (int) ($data['pageIndex'] ?? 1),
            'rowPerPage' => (int) ($data['rowPerPage'] ?? 50),
            'startDateMilis' => (int) ($data['startDateMilis'] ?? 0),
            'endDateMilis' => (int) ($data['endDateMilis'] ?? 0),
            'gameType' => (int) ($data['gameType'] ?? 0),
        ];

        // 1) ขอ token จากตาราง members ถ้าไม่มี/หมดอายุ -> re-auth ทันที
        $token = $this->ensureMemberLobbyTokenByUsername($username);
        if (! $this->isJwtLike($token)) {
            $ret['msg'] = 'เชื่อมต่อไม่ได้';

            return $ret;
        }

        $action = $this->api_lobby.'v1/user/transaction';
        $doRequest = function (string $token) use ($param, $action) {
            $header = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ];

            return \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders($header)
                ->asJson()
                ->post($action, $param);
        };

        // 2) ยิงครั้งที่ 1
        $response = rescue(fn () => $doRequest($token), fn () => false, true);
        if ($response === false) {
            $ret['msg'] = 'เชื่อมต่อไม่ได้';

            return $ret;
        }

        // --- normalize + วิเคราะห์เคส token ใช้ไม่ได้ ---
        $norm = $this->normalizeHttpResponse($response);
        $json = $norm['json'] ?? [];
        $ok = $this->computeSuccess($json, $norm['status'] ?? null);

        if (! $ok) {
            // 3) ถ้าไม่สำเร็จ และเข้าข่าย token พัง/ไม่ authorize -> re-auth + retry 1 ครั้ง
            if ($this->isUnauthorized($norm) || $this->isTokenIssue($norm)) {
                $this->SigninNew($username); // จะบันทึก session_page ใหม่ให้อัตโนมัติ
                $member = \Gametech\Member\Models\MemberProxy::where('user_name', $username)->orWhere('game_user', $username)->first();
                $member?->refresh();
                $token = $this->sanitizeToken($member->session_page ?? null);

                if ($this->isJwtLike($token)) {
                    $response = rescue(fn () => $doRequest($token), fn () => false, true);
                    if ($response !== false) {
                        $norm = $this->normalizeHttpResponse($response);
                        $json = $norm['json'] ?? [];
                        $ok = $this->computeSuccess($json, $norm['status'] ?? null);
                    }
                }
            }
        }

        // 4) สรุปผลลัพธ์
        $msg = $this->pickMessage($json);
        if (! $ok && strtoupper((string) $msg) === 'SUCCESS') {
            $ok = true;
        }

        $ret['success'] = $ok;
        $ret['msg'] = $msg ?: ($ok ? 'SUCCESS' : 'FAILED');
        $ret['data'] = $json['result'] ?? null;

        return $ret;
    }

    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return \Gametech\Member\Models\Member::class; // หรือ \Gametech\Game\Models\GamesUser::class ถ้ามี
    }
}
