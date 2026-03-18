<?php

namespace Gametech\Core\Listeners;

use Exception;
use Gametech\Member\Repositories\MemberLogRepository;
use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class Customer
{
    private $memberRepository;

    private $memberLogRepository;

    public function __construct(
        MemberRepository $memberRepository,
        MemberLogRepository $memberLogRepository
    )
    {
        $this->memberRepository = $memberRepository;
        $this->memberLogRepository = $memberLogRepository;
    }

    public function fail($user)
    {
        try {
            // --- Normalize payload to ($username, $password) ---
            $username = null;
            $password = null;

            if ($user instanceof Failed) {
                // Laravel auth failed event
                $username = Arr::get($user->credentials, 'username', Arr::get($user->credentials, 'email'));
                $password = Arr::get($user->credentials, 'password');
            } elseif (is_string($user)) {
                // Legacy: "username|password"
                $parts = explode('|', $user, 2);
                $username = $parts[0] ?? null;
                $password = $parts[1] ?? null;
            } elseif (is_array($user)) {
                // Fallback: array payload
                $username = $user['username'] ?? ($user['email'] ?? null);
                $password = $user['password'] ?? null;
            } else {
                // Unsupported payload type → log and stop
                Log::warning('Login fail event with unsupported payload type', [
                    'type' => is_object($user) ? get_class($user) : gettype($user),
                ]);
                return;
            }

            // --- Lookup user record (may be null) ---
            $username_real = '';
            $password_real = '';

            $chk = null;
            if ($username) {
                $chk = DB::table('members')->where('user_name', $username)->first();
            }

            if ($chk) {
                $username_real = $chk->user_name ?? '';
                // Keep your original semantics: store hashed-from-db into password_real
                $password_real = $chk->user_pass ?? ($chk->password ?? '');
            }

            // --- Build summary without touching null fields ---
            if (!$chk) {
                $summary = 'USER NOT FOUND';
            } else {
                $hash = $chk->password ?? ($chk->user_pass ?? null);
                if ($hash && $password !== null && $password !== '') {
                    $summary = Hash::check($password, $hash) ? 'PASSWORD MATCH' : 'PASSWORD NOT MATCH';
                } else {
                    $summary = 'PASSWORD UNKNOWN';
                }
            }

            $this->memberLogRepository->create([
                'member_code'   => 0,
                'mode'          => 'LOGIN',
                'menu'          => 'login',
                'record'        => 0,
                'remark'        => 'Login FAIL',
                'item_before'   => is_string($user) ? $user : json_encode($user),
                'item'          => is_string($user) ? $user : json_encode($user),
                'username'      => $username,
                'password'      => $password,       // ⚠️ เก็บเป็น plain ตามของเดิม (แนะนำลบ/มาสก์ในอนาคต)
                'username_real' => $username_real,
                'password_real' => $password_real,  // มักเป็น hash จาก DB
                'summary'       => $summary,
                'ip'            => request()->ip(),
                'user_create'   => ''
            ]);

        } catch (Exception $e) {
            report($e);
        }
    }

    public function login($user)
    {
        try {

            $this->memberRepository->update(['lastlogin' => now()], $user->code);

            // (logging disabled as in your original)

        } catch (Exception $e) {
            report($e);
        }

    }

    public function logout($user)
    {
        try {
            // (logging disabled as in your original)
        } catch (Exception $e) {
            report($e);
        }

    }

    public function register($user)
    {
        try {

            $this->memberLogRepository->create([
                'member_code' => $user->code,
                'mode' => 'REGISTER',
                'menu' => 'register',
                'record' => $user->code,
                'remark' => 'Register success',
                'item_before' => '',
                'item' => serialize($user),
                'ip' => request()->ip(),
                'user_create' => $user->name
            ]);

        } catch (Exception $e) {
            report($e);
        }

    }

    public function memberEvent($event){
        $user = Auth::guard('customer')->user();
        try {

            $this->memberLogRepository->create([
                'member_code' => $user->code,
                'mode' => 'EVENT',
                'menu' => 'member',
                'record' => $user->code,
                'remark' => $event,
                'item_before' => '',
                'item' => '',
                'ip' => request()->ip(),
                'user_create' => $user->name
            ]);

        } catch (Exception $e) {
            report($e);
        }
    }
}
