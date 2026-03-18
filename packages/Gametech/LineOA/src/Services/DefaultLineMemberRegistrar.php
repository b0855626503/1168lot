<?php

namespace Gametech\LineOA\Services;

use Gametech\Game\Repositories\GameUserRepository;
use Gametech\LineOA\Contracts\LineMemberRegistrar;
use Gametech\LineOA\Contracts\MemberRegistrationResult;
// use Gametech\Marketing\Models\MarketingMember as Member;
use Gametech\Member\Models\Member;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DefaultLineMemberRegistrar implements LineMemberRegistrar
{
    public function __construct(
        protected GameUserRepository $gameUserRepo,
    ) {}

    /**
     * สมัครสมาชิกจริงจากข้อมูลที่ได้จาก LINE
     *
     * data ที่มาจาก flow:
     * - phone        (string)  -> เบอร์โทร
     * - name         (string)  -> ชื่อ
     * - surname      (string)  -> นามสกุล
     * - bank_code    (string)  -> ใช้ค่าเดียวกับ members.bank_code
     * - account_no   (string)  -> ใช้กับ members.acc_no
     */
    public function registerFromLineData(array $data): MemberRegistrationResult
    {
        $mode      = Arr::get($data, 'register_mode', 'phone'); // 'phone' | 'username'
        $phone     = Arr::get($data, 'phone');
        $rawUser   = Arr::get($data, 'username');
        $username  = $rawUser !== null ? strtolower(trim((string) $rawUser)) : null;

        $name      = Arr::get($data, 'name');
        $surname   = Arr::get($data, 'surname');
        $bankCode  = Arr::get($data, 'bank_code');
        $accountNo = Arr::get($data, 'account_no');

        // ==== 1) ตรวจ required ตามโหมด ==== //
        if ($mode === 'phone') {
            if (! $phone || ! $name || ! $surname || ! $bankCode || ! $accountNo) {
                return MemberRegistrationResult::failure('MISSING_REQUIRED_FIELDS');
            }
        } elseif ($mode === 'username') {
            if (! $username || ! $name || ! $surname || ! $bankCode || ! $accountNo) {
                return MemberRegistrationResult::failure('MISSING_REQUIRED_FIELDS');
            }

            // กัน username รูปแบบผิด (เผื่อ layer บนหลุดมา)
            if (! preg_match('/^[a-z0-9]+$/', $username)) {
                return MemberRegistrationResult::failure('INVALID_USERNAME_FORMAT');
            }
        } else {
            // กันเคส mode แปลก
            return MemberRegistrationResult::failure('INVALID_REGISTER_MODE');
        }

        try {
            return DB::transaction(function () use ($mode, $phone, $username, $name, $surname, $bankCode, $accountNo) {

                // ==== 2) กันซ้ำตามโหมด ==== //

                // phone-mode: เบอร์ต้องไม่ซ้ำ และไม่ชนบัญชี
                if ($mode === 'phone') {
                    if ($this->isPhoneExistInMembersOrBankAccount($phone)) {
                        return MemberRegistrationResult::failure('PHONE_ALREADY_EXISTS');
                    }
                }

                // username-mode: username ต้องไม่ซ้ำ
                if ($mode === 'username') {
                    if ($this->isUsernameExistInMembers($username)) {
                        return MemberRegistrationResult::failure('USERNAME_ALREADY_EXISTS');
                    }

                    // ถ้ามีการส่ง phone มาด้วย และอยากกันซ้ำเบอร์ด้วย ก็เช็คได้
                    if ($phone && $this->isPhoneExistInMembersOrBankAccount($phone)) {
                        return MemberRegistrationResult::failure('PHONE_ALREADY_EXISTS');
                    }
                }

                // ไม่ว่าโหมดไหน เลขบัญชีต้องไม่ซ้ำ
                if ($this->isBankAccountExistInMembersOrBankAccount($bankCode, $accountNo)) {
                    return MemberRegistrationResult::failure('BANK_ACCOUNT_ALREADY_EXISTS');
                }

                $config  = core()->getConfigData();
                $today   = now()->toDateString();
                $datenow = now()->toDateTimeString();
                $ip      = request()?->ip() ?? '0.0.0.0';

                // ==== 3) logic acc_check / acc_bay ตามเดิม ==== //
                if ((string) $bankCode === '4') {
                    $acc_check = substr($accountNo, -4);
                } else {
                    $acc_check = substr($accountNo, -6);
                }
                $acc_bay   = substr($accountNo, -7);
                $acc_kbank = '';

                $fullname = trim($name.' '.$surname);

                // ==== 4) ตัดสินใจ username / password / wallet / tel ตามโหมด ==== //

                if ($mode === 'phone') {
                    // ของเดิม: ใช้เบอร์เป็นทุกอย่าง
                    $finalUsername   = $phone;
                    $plainPassword   = $phone;
                    $walletId        = $phone;
                    $tel             = $phone;
                } else {
                    // username-mode
                    $finalUsername   = $username;
                    // policy ง่ายสุด: ถ้ามี phone → ใช้ phone เป็น password, ถ้าไม่มี → ใช้ username
                    $plainPassword   = $phone ?: $username;
                    // walletid ส่วนใหญ่ระบบเดิมใช้เบอร์ → ถ้ามี phone ก็ใช้ phone, ถ้าไม่มีค่อย fallback เป็น username
                    $walletId        = $phone ?: $username;
                    $tel             = $phone ?: '';
                }

                // ==== 5) สร้าง Member ==== //

                $member = Member::create([
                    'user_name'  => $finalUsername,
                    'user_pass'  => $plainPassword,
                    'password'   => Hash::make($plainPassword),

                    'wallet_id'  => $walletId,
                    'tel'        => $tel,

                    'firstname'  => $name,
                    'lastname'   => $surname,
                    'name'       => $fullname,

                    'bank_code'  => $bankCode,
                    'acc_no'     => $accountNo,
                    'acc_check'  => $acc_check,
                    'acc_bay'    => $acc_bay,
                    'acc_kbank'  => $acc_kbank,

                    // ถือว่ายืนยันแล้ว (LINE ไม่ใช้ OTP)
                    'confirm'       => 'Y',

                    'freecredit'    => $config->freecredit_open,
                    'check_status'  => 'N',
                    'promotion'     => 'N',

                    'user_create'   => $fullname,
                    'user_update'   => $fullname,

                    'lastlogin'     => $datenow,
                    'date_regis'    => $today,
                    'birth_day'     => $today,

                    'session_limit' => null,
                    'payment_limit' => null,
                    'payment_delay' => null,
                    'remark'        => '',

                    'gender'        => 'M',
                    // 'team_id'    => null,
                    // 'campaign_id'=> null,

                    'otp'           => '',
                    'ip'            => $ip,
                ]);

                $memberId = $member->code ?? $member->id; // แล้วแต่ model ใช้ pk อะไร

                // ==== 6) seamless / multigame ตามเดิม ==== //

                if ($config->seamless === 'Y') {
                    if ($memberId) {
                        $this->gameUserRepo->addGameUser(1, $memberId, [
                            'username'    => $finalUsername,
                            'password'    => $plainPassword,
                            'name'        => $fullname,
                            'user_create' => $fullname,
                        ]);
                    }
                } else {
                    if ($config->multigame_open === 'N') {
                        $game = core()->getGame();

                        if ($game && $memberId) {
                            $this->gameUserRepo->addGameUser($game->code, $memberId, $member);
                        }
                    }
                }

                $loginUrl = $this->getLoginUrl();

                return MemberRegistrationResult::success(
                    $memberId,
                    $finalUsername,
                    $plainPassword,
                    $loginUrl,
                    null
                );

            });
        } catch (\Throwable $e) {
            report($e);

            return MemberRegistrationResult::failure('REGISTER_EXCEPTION: '.$e->getMessage());
        }
    }

    /**
     * เช็คว่า username นี้ถูกใช้แล้วใน members หรือยัง
     */
    protected function isUsernameExistInMembers(string $username): bool
    {
        $username = strtolower(trim($username));

        if ($username === '') {
            return true;
        }

        return Member::where(function ($q) use ($username) {
            $q->where('user_name', $username);
        })->exists();
    }
    /**
     * ใช้ rules เดียวกับ controller:
     * - tel unique ใน members
     * - tel ห้ามชนกับ banks_account.acc_no
     */
    protected function isPhoneExistInMembersOrBankAccount(string $phone): bool
    {
        // 1) members.tel
        $existsInMember = Member::where(function ($q) use ($phone) {
            $q->where('tel', $phone)
                ->orWhere('user_name', $phone);
        })->exists();

        if ($existsInMember) {
            return true;
        }

        // 2) banks_account.acc_no
        $exists = DB::table('banks_account')
            ->where('acc_no', $phone)
            ->exists();

        return $exists;
    }

    /**
     * ใช้ rules เดียวกับ controller:
     * - acc_no unique ใน members (ตาม bank_code)
     * - acc_no ห้ามชนกับ banks_account.acc_no
     */
    protected function isBankAccountExistInMembersOrBankAccount(?string $bankCode, string $accountNo): bool
    {
        if (! $bankCode) {
            return false;
        }

        // 1) ซ้ำใน members (acc_no + bank_code)
        $dupMember = Member::where('bank_code', $bankCode)
            ->where('acc_no', $accountNo)
            ->exists();

        if ($dupMember) {
            return true;
        }

        // 2) banks_account.acc_no
        $exists = DB::table('banks_account')
            ->where('acc_no', $accountNo)
            ->exists();

        return $exists;
    }

    /**
     * ถ้าภายหลังอยากเปลี่ยน logic user_name/password ตามเว็บหลัก
     * สามารถปรับตรงนี้ และใน registerFromLineData ได้
     */
    protected function generateUsernameAndPassword(string $phone): array
    {
        // ใช้ phone เป็น user_name ตรง ๆ ตาม requirement
        $username = $phone;
        $password = $phone;

        return [$username, $password];
    }

    protected function getLoginUrl(): string
    {
        // ดึง URL เดิม
        if (function_exists('route')) {
            try {
                $url = route('customer.session.index');
            } catch (\Throwable $e) {
                $url = url('/login');
            }
        } else {
            $url = url('/login');
        }

        // parse URL เพื่อตรวจพารามิเตอร์
        $hasQuery = str_contains($url, '?');

        // ถ้ามี ? แล้ว → ต่อด้วย &
        // ถ้าไม่มี → ต่อด้วย ?
        $suffix = $hasQuery ? '&openExternalBrowser=1' : '?openExternalBrowser=1';

        return $url.$suffix;
    }
}
