<?php

namespace Gametech\Payment\Http\Controllers;

use App\Events\RealTimeNewMessage;
use Carbon\Carbon;
use Gametech\Core\Repositories\CheckCaseRepository;
use Gametech\Payment\Repositories\BankAccountRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\BankRepository;
use Gametech\Payment\Libraries\AutoTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutoTransferController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $bankRepository;

    protected $bankAccountRepository;

    protected $bankPaymentRepository;

    public function __construct(CheckCaseRepository $repository, BankAccountRepository $bankAccountRepository, BankPaymentRepository $bankPaymentRepository, BankRepository $bankRepository)
    {
        $this->_config = request('_config');

        $this->repository = $repository;

        $this->bankRepository = $bankRepository;

        $this->bankAccountRepository = $bankAccountRepository;

        $this->bankPaymentRepository = $bankPaymentRepository;
    }

    /**
     * Maintenance check endpoint (check_ma_url)
     * Auto Transfer service expects:
     *  - HTTP status 2xx and JSON: { "is_ready": true } to proceed callbacks
     *
     * NOTE:
     * - ผู้ให้บริการไม่มีการระบุ check_ma_apikey จึงไม่ตรวจ header apikey ที่ endpoint นี้
     * - ใช้ readiness แบบเบาที่สุด: force_maintenance หรือ app down เท่านั้น
     */
    /**
     * Maintenance check endpoint (check_ma_url)
     * Auto Transfer service expects:
     *  - HTTP status 2xx and JSON: { "is_ready": true } to proceed callbacks
     *
     * NOTE:
     * - ผู้ให้บริการไม่มีการระบุ check_ma_apikey จึงไม่ตรวจ header apikey ที่ endpoint นี้
     * - ใช้ readiness แบบเบาที่สุด: force_maintenance หรือ app down เท่านั้น
     *
     * CHANGE:
     * - Log channel autotransfer_check_ma ใช้เฉพาะใน function นี้เท่านั้น
     * - เก็บค่า request + response (best-effort)
     */
    public function check_ma(Request $request)
    {
        // ---- Capture request (best-effort) ----
        $reqContext = [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => [
                // เก็บเฉพาะที่พอ debug ได้ ไม่เสี่ยงข้อมูลลับ
                'user-agent' => (string) $request->header('user-agent', ''),
                'content-type' => (string) $request->header('content-type', ''),
                'accept' => (string) $request->header('accept', ''),
                'x-forwarded-for' => (string) $request->header('x-forwarded-for', ''),
                'cf-connecting-ip' => (string) $request->header('cf-connecting-ip', ''),
            ],
            'query' => (array) $request->query(),
            'body' => (array) $request->all(),
        ];

        Log::channel('autotransfer_check_ma')->info('AutoTransfer check_ma request', $reqContext);

        $force = strtoupper((string) config('autotransfer.force_maintenance', 'N')) === 'Y';
        $down = method_exists(app(), 'isDownForMaintenance') ? app()->isDownForMaintenance() : false;

        $isReady = ! ($force || $down);

        $httpStatus = $isReady ? 200 : 503;

        // ---- Capture response ----
        Log::channel('autotransfer_check_ma')->info('AutoTransfer check_ma response', [
            'is_ready' => $isReady,
            'http_status' => $httpStatus,
            'force_maintenance' => $force ? 'Y' : 'N',
            'app_down' => $down ? 'Y' : 'N',
        ]);

        return response()->json(['is_ready' => $isReady], $httpStatus);
    }

    /**
     * Deposit callback (triggered_url)
     *
     * Requirement:
     * - หา member จาก payer_*:
     *   - payer_bank -> banks.shortcode -> banks.code -> members.bank_code
     *   - payer_account รูปแบบ mask: X1234 / 1234XXXX5678 / 12XXXX3456 (รองรับ)
     *   - payer_name ถ้ามี ให้เทียบกับ members.firstname (ตัดคำนำหน้า) และ "ใช้ชื่อจริงคำแรกเท่านั้น"
     * - เจอ 1 รายการ: autocheck='W', member_topup=member.code
     *   remark_admin = "พบหมายเลขบัญชี {user_name} ({firstname}) รอระบบเติมอัตโนมัติ"
     * - ไม่พบ หรือ >1: autocheck='Y'
     *   remark_admin = "พบหมายเลขบัญชี {n} บัญชี user1 (name1), user2 (name2) ..."
     *
     * CHANGE (ตามที่ขอ):
     * - เช็คจาก “ธนาคาร + เลขบัญชี” ก่อน (ไม่ใช้ชื่อ)
     * - ถ้าไม่เจอ หรือเจอหลายรายการ ค่อยใช้ชื่อมาเป็นตัวช่วยในรอบถัดไป
     */
    public function deposit_callback(Request $request)
    {
        $payload = (array)$request->all();

        Log::channel('autotransfer_deposit_callback')->info('AutoTransfer deposit callback', $payload);

        // Optional inbound apikey
        $requiredKey = (string)config('autotransfer.triggered_apikey', '');
        if ($requiredKey !== '') {
            $inKey = (string)$request->header('apikey', '');
            if (!hash_equals($requiredKey, $inKey)) {
                Log::channel('autotransfer_deposit_callback')->warning('AutoTransfer deposit callback: invalid apikey', ['ip' => $request->ip(),]);

                return response()->json(['status' => 'invalid_apikey'], 401);
            }
        }

        $statusCode = (string)data_get($payload, 'status.code', '');
        $statusType = (string)data_get($payload, 'status.type', '');

        if ($statusCode !== '200.41' || strtolower($statusType) !== 'complete') {
            return response()->json(['status' => 'ignored'], 200);
        }

        $action = (string)data_get($payload, 'data.action', '');
        $action = strtolower($action);

        $allowedActions = ['deposit-auto-complete-transaction', 'deposit-auto-completed-transaction',];

        if ($action !== '' && !in_array($action, $allowedActions, true)) {
            return response()->json(['status' => 'ignored_action'], 200);
        }

        $transactionId = (string)data_get($payload, 'data.transaction_id', '');
        $uniqueHash = (string)data_get($payload, 'data.unique_hash', '');
        $session = (string)data_get($payload, 'data.session', '');

        $verifiedFrom = (string)data_get($payload, 'data.verified_from', '');

        $payerNameRaw = data_get($payload, 'data.payer_name');
        $payerBankRaw = (string)(data_get($payload, 'data.payer_bank_abbr') ?? data_get($payload, 'data.payer_bank') ?? '');
        $payerAccountRaw = (string)data_get($payload, 'data.payer_account', '');

        $payeeBank = (string)(data_get($payload, 'data.payee_bank_abbr') ?? data_get($payload, 'data.payee_bank') ?? '');
        $payeeAccount = (string)data_get($payload, 'data.payee_account', '');

        $amount = (float)data_get($payload, 'data.amount', 0);

        $transferredAtRaw = data_get($payload, 'data.transferred_at');
        $bankTime = null;

        try {
            if ($transferredAtRaw) {
                $bankTime = Carbon::parse($transferredAtRaw)->toDateTimeString();
            }
        } catch (Throwable $e) {
            $bankTime = null;
        }

        if ($transactionId === '') {
            return response()->json(['status' => 'invalid_payload'], 422);
        }

        // idempotency: txid ซ้ำ = ไม่สร้างซ้ำ
        $exists = $this->bankPaymentRepository->findOneWhere(['txid' => $transactionId]);
        if ($exists) {
            return response()->json(['status' => 'duplicate'], 200);
        }

        // ===== 1) หา bank account (payee) ในระบบเรา เพื่อผูก account_code =====
        $bankAccount = $this->bankAccountRepository->getAccountOneNew($payeeBank, $payeeAccount);
        if (!$bankAccount) {
            Log::channel('autotransfer_deposit_callback')->warning('AutoTransfer deposit callback: payee account not found', ['transaction_id' => $transactionId, 'payee_bank' => $payeeBank, 'payee_account' => $payeeAccount, 'unique_hash' => $uniqueHash, 'session' => $session,]);

            return response()->json(['status' => 'payee_account_not_found'], 422);
        }

        $bank = $this->bankRepository->find($bankAccount->banks);
        $bankShort = $bank ? $bank->shortcode : strtoupper($payeeBank);

        // ===== 2) Helper: normalize bank shortcode =====
        $normalizeBankShort = function (string $s): string {
            $s = strtoupper(trim($s));
            // กรณี provider ส่งแบบ SCBA / GSBA
            if (strlen($s) === 4 && substr($s, -1) === 'A') {
                $s = substr($s, 0, 3);
            }
            return $s;
        };

        // ===== 3) Helper: normalize payer name (ตัดคำนำหน้า) =====
        $normalizePayerName = function ($name): string {
            if ($name === null) {
                return '';
            }

            $name = (string)$name;
            $name = trim($name);
            if ($name === '') {
                return '';
            }

            $nameUpper = mb_strtoupper($name, 'UTF-8');

            $prefixes = ['นาย', 'นางสาว', 'นาง', 'คุณ', 'ด.ช.', 'ด.ญ.', 'เด็กชาย', 'เด็กหญิง', 'MR.', 'MR', 'MRS.', 'MRS', 'MS.', 'MS', 'MISS', 'MISTER',];

            foreach ($prefixes as $p) {
                $pUpper = mb_strtoupper($p, 'UTF-8');
                if (mb_substr($nameUpper, 0, mb_strlen($pUpper, 'UTF-8'), 'UTF-8') === $pUpper) {
                    $name = trim(mb_substr($name, mb_strlen($p, 'UTF-8'), null, 'UTF-8'));
                    $nameUpper = mb_strtoupper($name, 'UTF-8');
                }
            }

            $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

            return trim($name);
        };

        // ===== 4) Helper: normalize payer_account to [0-9X] and extract match parts =====
        $extractAccountPattern = function (string $raw): array {
            $raw = strtoupper((string)$raw);

            $filtered = preg_replace('/[^0-9X]/', '', $raw) ?? '';
            $filtered = strtoupper($filtered);

            $digitsOnly = preg_replace('/[^0-9]/', '', $raw) ?? '';

            $hasX = strpos($filtered, 'X') !== false;

            $pattern = ['raw' => $raw, 'filtered' => $filtered, 'digits' => $digitsOnly, 'has_x' => $hasX, 'left2' => '', 'left4' => '', 'right4' => '', 'mode' => '',];

            if ($digitsOnly !== '' && !$hasX) {
                $pattern['mode'] = 'full';
                $pattern['right4'] = strlen($digitsOnly) >= 4 ? substr($digitsOnly, -4) : $digitsOnly;
                $pattern['left2'] = strlen($digitsOnly) >= 2 ? substr($digitsOnly, 0, 2) : '';
                $pattern['left4'] = strlen($digitsOnly) >= 4 ? substr($digitsOnly, 0, 4) : '';
                return $pattern;
            }

            if (preg_match('/^X+([0-9]{4})$/', $filtered, $m)) {
                $pattern['mode'] = 'last4';
                $pattern['right4'] = $m[1];
                return $pattern;
            }

            if (preg_match('/^([0-9]{4})X+([0-9]{4})$/', $filtered, $m)) {
                $pattern['mode'] = 'left4_right4';
                $pattern['left4'] = $m[1];
                $pattern['right4'] = $m[2];
                return $pattern;
            }

            if (preg_match('/^([0-9]{2})X+([0-9]{4})$/', $filtered, $m)) {
                $pattern['mode'] = 'left2_right4';
                $pattern['left2'] = $m[1];
                $pattern['right4'] = $m[2];
                return $pattern;
            }

            if (preg_match('/([0-9]{4})$/', $filtered, $m)) {
                $pattern['mode'] = 'last4';
                $pattern['right4'] = $m[1];
                return $pattern;
            }

            return $pattern;
        };

        // ===== 5) Resolve candidate members (two-pass) =====
        $memberCode = 0;
        $autocheck = 'Y';
        $remarkAdmin = '';
        $foundMembers = [];

        $payerBankShort = $normalizeBankShort($payerBankRaw);

        // ชื่อเต็ม (หลังตัดคำนำหน้า) ใช้สำหรับแสดงผล
        $payerNameFull = $normalizePayerName($payerNameRaw);

        // ✅ ชื่อที่ใช้เช็ค DB = ชื่อจริงคำแรกเท่านั้น
        $payerName = '';
        if ($payerNameFull !== '') {
            $parts = preg_split('/\s+/u', $payerNameFull) ?: [];
            $payerName = trim((string)($parts[0] ?? ''));
        }

        $accPattern = $extractAccountPattern($payerAccountRaw);

        $payerBank = null;
        $payerBankCode = null;

        if ($payerBankShort !== '') {
            $payerBank = $this->bankRepository->findOneWhere(['shortcode' => $payerBankShort]);
            if ($payerBank && isset($payerBank->code)) {
                $payerBankCode = (int)$payerBank->code;
            }
        }

        $memberRepo = app('Gametech\\Member\\Repositories\\MemberRepository');

        // helper: apply account constraints (bank+acc) to query
        $applyAccountWhere = function ($query) use ($accPattern): void {
            if (!is_array($accPattern) || ($accPattern['mode'] ?? '') === '') {
                return;
            }

            $mode = (string)($accPattern['mode'] ?? '');

            if ($mode === 'full') {
                if (($accPattern['left4'] ?? '') !== '' && ($accPattern['right4'] ?? '') !== '') {
                    $query->whereRaw('LEFT(acc_no, 4) = ?', [$accPattern['left4']]);
                    $query->whereRaw('RIGHT(acc_no, 4) = ?', [$accPattern['right4']]);
                } elseif (($accPattern['right4'] ?? '') !== '') {
                    $query->whereRaw('RIGHT(acc_no, 4) = ?', [$accPattern['right4']]);
                }
                return;
            }

            if ($mode === 'left4_right4') {
                $query->whereRaw('LEFT(acc_no, 4) = ?', [$accPattern['left4']]);
                $query->whereRaw('RIGHT(acc_no, 4) = ?', [$accPattern['right4']]);
                return;
            }

            if ($mode === 'left2_right4') {
                $query->whereRaw('LEFT(acc_no, 2) = ?', [$accPattern['left2']]);
                $query->whereRaw('RIGHT(acc_no, 4) = ?', [$accPattern['right4']]);
                return;
            }

            // last4
            if (($accPattern['right4'] ?? '') !== '') {
                $query->whereRaw('RIGHT(acc_no, 4) = ?', [$accPattern['right4']]);
            }
        };

        // helper: apply name constraints (firstname/firstname_addon) to query
        $applyNameWhere = function ($query) use ($payerName): void {
            if ((string)$payerName === '') {
                return;
            }

            $query->where(function ($q) use ($payerName) {
                $q->where('firstname', $payerName)->orWhere('firstname', 'LIKE', $payerName . '%')->orWhere('firstname_addon', $payerName)->orWhere('firstname_addon', 'LIKE', $payerName . '%');
            });
        };

        // helper: fetch rows to foundMembers
        $fetchMembers = function ($query) use (&$foundMembers): void {
            $rows = $query->limit(50)->get(['code', 'acc_no', 'firstname', 'user_name']);

            foreach ($rows as $r) {
                $foundMembers[] = ['code' => (int)($r->code ?? 0), 'acc_no' => (string)($r->acc_no ?? ''), 'firstname' => (string)($r->firstname ?? ''), 'user_name' => (string)($r->user_name ?? ''),];
            }
        };

        // ===== PASS 1: bank + acc only (ไม่ใช้ชื่อ) =====
        if ($payerBankCode && ($accPattern['mode'] ?? '') !== '') {
//            Log::channel('autotransfer_check_ma')->warning('start check member (pass1 bank+acc)', ['bank_code' => $payerBankCode,]);

            $query1 = $memberRepo->query();
            $query1->where('bank_code', $payerBankCode);

//            Log::channel('autotransfer_check_ma')->warning('check member mode (pass1)', ['mode' => (string)($accPattern['mode'] ?? ''), 'left2' => (string)($accPattern['left2'] ?? ''), 'left4' => (string)($accPattern['left4'] ?? ''), 'right4' => (string)($accPattern['right4'] ?? ''),]);

            $applyAccountWhere($query1);

            $fetchMembers($query1);
        }

        // ถ้าพบหลายรายการ และมีชื่อ -> PASS 2a: ลดความกำกวมด้วยชื่อ
        if (count($foundMembers) > 1 && $payerBankCode && $payerName !== '' && ($accPattern['mode'] ?? '') !== '') {
//            Log::channel('autotransfer_check_ma')->warning('start check member (pass2a disambiguate by name)', ['bank_code' => $payerBankCode, 'firstname' => $payerName,]);

            $filtered = [];

            $query2a = $memberRepo->query();
            $query2a->where('bank_code', $payerBankCode);
            $applyAccountWhere($query2a);
            $applyNameWhere($query2a);

            $rows2a = $query2a->limit(50)->get(['code', 'acc_no', 'firstname', 'user_name']);
            foreach ($rows2a as $r) {
                $filtered[] = ['code' => (int)($r->code ?? 0), 'acc_no' => (string)($r->acc_no ?? ''), 'firstname' => (string)($r->firstname ?? ''), 'user_name' => (string)($r->user_name ?? ''),];
            }

            // ใช้ผลกรอง ถ้ามีอย่างน้อย 1 รายการ (ไม่งั้นคง foundMembers เดิมไว้เพื่อสะท้อนความกำกวม)
            if (count($filtered) > 0) {
                $foundMembers = $filtered;
            }
        }

        // ถ้าไม่เจอเลย และมีชื่อ -> PASS 2b: fallback search (ค่อยใช้ชื่อเพิ่ม)
        if (count($foundMembers) === 0 && $payerBankCode && $payerName !== '') {
//            Log::channel('autotransfer_check_ma')->warning('start check member (pass2b fallback by name)', ['bank_code' => $payerBankCode, 'firstname' => $payerName, 'acc_mode' => (string)($accPattern['mode'] ?? ''), 'right4' => (string)($accPattern['right4'] ?? ''),]);

            // แนวปลอดภัยก่อน: bank + (last4 ถ้ามี) + name
            $query2b = $memberRepo->query();
            $query2b->where('bank_code', $payerBankCode);

            if (($accPattern['right4'] ?? '') !== '') {
                $query2b->whereRaw('RIGHT(acc_no, 4) = ?', [$accPattern['right4']]);
            }

            $applyNameWhere($query2b);

            $rows2b = $query2b->limit(50)->get(['code', 'acc_no', 'firstname', 'user_name']);
            foreach ($rows2b as $r) {
                $foundMembers[] = ['code' => (int)($r->code ?? 0), 'acc_no' => (string)($r->acc_no ?? ''), 'firstname' => (string)($r->firstname ?? ''), 'user_name' => (string)($r->user_name ?? ''),];
            }

            // ถ้ายังไม่เจอ และ last4 ไม่มี (หรือ provider ส่ง acc แปลกมาก) -> ค่อย fallback bank+name อย่างเดียว (เสี่ยงชื่อซ้ำ)
            if (count($foundMembers) === 0 && ($accPattern['right4'] ?? '') === '') {
//                Log::channel('autotransfer_check_ma')->warning('start check member (pass2c fallback bank+name only)', ['bank_code' => $payerBankCode, 'firstname' => $payerName,]);

                $query2c = $memberRepo->query();
                $query2c->where('bank_code', $payerBankCode);
                $applyNameWhere($query2c);

                $rows2c = $query2c->limit(50)->get(['code', 'acc_no', 'firstname', 'user_name']);
                foreach ($rows2c as $r) {
                    $foundMembers[] = ['code' => (int)($r->code ?? 0), 'acc_no' => (string)($r->acc_no ?? ''), 'firstname' => (string)($r->firstname ?? ''), 'user_name' => (string)($r->user_name ?? ''),];
                }
            }
        }

        // ===== 6) Decide outcome (remark_admin ต้องใช้ user_name) =====
        if (count($foundMembers) === 1) {
            $one = $foundMembers[0];

            $memberCode = (int)$one['code'];
            $autocheck = 'W';

            $userName = $one['user_name'] !== '' ? $one['user_name'] : '-';
            $first = $one['firstname'] !== '' ? $one['firstname'] : ($payerName !== '' ? $payerName : '-');

            $remarkAdmin = 'พบหมายเลขบัญชี ' . $userName . ' (' . $first . ') รอระบบเติมอัตโนมัติ';
        } elseif (count($foundMembers) > 1) {
            $autocheck = 'Y';

            $items = [];
            foreach ($foundMembers as $m) {
                $userName = $m['user_name'] !== '' ? $m['user_name'] : '-';
                $first = $m['firstname'] !== '' ? $m['firstname'] : '-';
                $items[] = $userName . ' (' . $first . ')';
            }

            $remarkAdmin = 'พบหมายเลขบัญชี ' . count($foundMembers) . ' บัญชี ' . implode(', ', $items);
        } else {
            $autocheck = 'Y';

            $bankStr = $payerBankShort !== '' ? $payerBankShort : ($payerBankRaw !== '' ? $payerBankRaw : '-');
            $accStr = $payerAccountRaw !== '' ? $payerAccountRaw : '-';

            $remarkAdmin = 'ไม่พบสมาชิกจากข้อมูลผู้โอน ' . $bankStr . ' ' . $accStr;

            // แสดงชื่อเต็ม (อ่านง่าย)
            if ($payerNameFull !== '') {
                $remarkAdmin .= ' (' . $payerNameFull . ')';
            }
        }

        // ===== 7) Compose detail (ให้แสดงแค่บรรทัดเดียว) =====
        // ต้องการรูปแบบ: PAYER: KBANK 10xxxx9255 (ชื่อเต็มหลังตัดคำนำหน้า)
        $payerBankForDetail = $payerBankShort !== '' ? $payerBankShort : ($payerBankRaw !== '' ? strtoupper(trim($payerBankRaw)) : '');
        $detail = 'PAYER:';

        if ($payerBankForDetail !== '') {
            $detail .= ' ' . $payerBankForDetail;
        }

        if ($payerAccountRaw !== '') {
            $detail .= ' ' . $payerAccountRaw;
        }

        // ✅ detail ใช้ชื่อเต็ม เพื่อให้อ่านย้อนหลังรู้เรื่อง
        if ($payerNameFull !== '') {
            $detail .= ' (' . $payerNameFull . ')';
        }

        // deterministic tx_hash
        $hash = md5($bankAccount->code . '|' . $transactionId . '|' . number_format($amount, 2, '.', ''));

        // ===== 8) Save bank_payments =====
        $data = ['bank' => strtolower($bankShort . '_' . $bankAccount->acc_no), 'detail' => $detail, 'account_code' => $bankAccount->code, 'autocheck' => $autocheck, 'bankstatus' => 1, 'bankname' => $bankShort, 'bank_time' => $bankTime ?? now()->toDateTimeString(), 'channel' => $verifiedFrom !== '' ? $verifiedFrom : 'API', 'value' => $amount, 'tx_hash' => $hash, 'txid' => $transactionId, 'status' => 0, 'ip_admin' => $request->ip(), 'member_topup' => $memberCode, 'remark_admin' => $remarkAdmin, 'emp_topup' => 0, 'user_create' => 'รอระบบเติมอัตโนมัติ ทำรายการฝากเงินโดย AutoTransfer (Auto Transfer)', 'create_by' => 'SYSAUTO',];

        $this->bankPaymentRepository->create($data);

//        // ===== 9) Update bank_accounts.balance from provider (best-effort; never fail callback) =====
//        try {
//            /** @var AutoTransfer $client */
//            $client = app(AutoTransfer::class);
//
//            $res = $client->getAccountBalances();
//            Log::channel('autotransfer_deposit_callback')->info('AutoTransfer get balance', ['raw' => $res]);
//            if (is_array($res) && ($res['success'] ?? false) === true) {
//                $rows = $res['data'] ?? [];
//                if (is_array($rows)) {
//                    $targetAcc = preg_replace('/\D+/', '', (string)($bankAccount->acc_no ?? '')) ?? '';
//                    $targetBank = strtoupper((string)($bankShort ?? ''));
//
//                    $foundBalance = null;
//
//                    foreach ($rows as $row) {
//                        if (!is_array($row)) {
//                            continue;
//                        }
//
//                        $rowBank = strtoupper((string)($row['bank_abbr'] ?? $row['bank'] ?? $row['bank_code'] ?? $row['payee_bank_abbr'] ?? ''));
//                        $rowAccRaw = (string)($row['account'] ?? $row['account_no'] ?? $row['account_number'] ?? $row['acc_no'] ?? $row['payee_account'] ?? '');
//                        $rowAcc = preg_replace('/\D+/', '', $rowAccRaw) ?? '';
//
//                        // match by bank + account (exact). If provider doesn't send bank, match by account only.
//                        $bankOk = $rowBank !== '' ? ($rowBank === $targetBank) : true;
//                        $accOk = $targetAcc !== '' && $rowAcc !== '' ? ($rowAcc === $targetAcc) : false;
//
//                        if ($bankOk && $accOk) {
//                            $bal = $row['balance'] ?? $row['available_balance'] ?? $row['amount'] ?? null;
//                            if ($bal !== null && $bal !== '') {
//                                $foundBalance = (float)$bal;
//                                break;
//                            }
//                        }
//                    }
//
//                    if ($foundBalance !== null) {
//                        $this->bankAccountRepository->update(['balance' => $foundBalance,], $bankAccount->code);
//
//                        Log::channel('autotransfer_deposit_callback')->info('AutoTransfer updated bank_account balance', ['bank_account_code' => $bankAccount->code, 'bank' => $targetBank, 'acc_no' => $targetAcc, 'balance' => $foundBalance, 'txid' => $transactionId,]);
//                    } else {
//                        Log::channel('autotransfer_deposit_callback')->warning('AutoTransfer getAccountBalances: no matching account', ['bank_account_code' => $bankAccount->code, 'bank' => $targetBank, 'acc_no' => $targetAcc, 'txid' => $transactionId,]);
//                    }
//                }
//            } else {
//                Log::channel('autotransfer_deposit_callback')->warning('AutoTransfer getAccountBalances not successful', ['txid' => $transactionId, 'response' => $res,]);
//            }
//        } catch (Throwable $e) {
//            Log::channel('autotransfer_deposit_callback')->warning('AutoTransfer balance update failed', ['txid' => $transactionId, 'bank_account_code' => $bankAccount->code ?? null, 'error' => $e->getMessage(),]);
//        }

        // ===== 9) Update bank_accounts.balance from provider (best-effort; never fail callback) =====
        try {
            /** @var AutoTransfer $client */
            $client = app(AutoTransfer::class);

            $res = $client->getAccountBalances();

            // log แบบ mask (กัน PII)
            Log::channel('autotransfer_deposit_callback')->info('AutoTransfer get balance (meta)', [
                'success' => (bool) data_get($res, 'success', false),
                'code' => data_get($res, 'code'),
                'status_code' => data_get($res, 'data.status.code'),
                'status_type' => data_get($res, 'data.status.type'),
            ]);

            // ✅ extract rows ให้ถูก path: data.data (รองรับ provider เปลี่ยน)
            $rows = data_get($res, 'data.data');
            if (!is_array($rows)) {
                // fallback: บาง provider อาจส่ง list มาใน data ตรง ๆ
                $rows = data_get($res, 'data');
            }

            $statusOk =
                ((bool) data_get($res, 'success', false) === true)
                && ((string) data_get($res, 'data.status.code', '') !== '')
                && (str_starts_with((string) data_get($res, 'data.status.code', ''), '200'));

            if ($statusOk && is_array($rows)) {
                $targetAcc = preg_replace('/\D+/', '', (string) ($bankAccount->acc_no ?? '')) ?? '';
                $targetBank = $normalizeBankShort((string) ($bankShort ?? ''));

                $foundBalance = null;

                foreach ($rows as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $rowBankRaw = (string) ($row['bank_abbr'] ?? $row['bank'] ?? $row['bank_code'] ?? $row['payee_bank_abbr'] ?? '');
                    $rowBank = $normalizeBankShort($rowBankRaw);

                    $rowAccRaw = (string) ($row['account'] ?? $row['account_no'] ?? $row['account_number'] ?? $row['acc_no'] ?? $row['payee_account'] ?? '');
                    $rowAcc = preg_replace('/\D+/', '', $rowAccRaw) ?? '';

                    // match by bank + account (exact). If provider doesn't send bank, match by account only.
                    $bankOk = $rowBank !== '' ? ($rowBank === $targetBank) : true;
                    $accOk = $targetAcc !== '' && $rowAcc !== '' ? ($rowAcc === $targetAcc) : false;

                    if ($bankOk && $accOk) {
                        $bal = $row['balance'] ?? $row['available_balance'] ?? $row['amount'] ?? null;
                        if ($bal !== null && $bal !== '') {
                            $foundBalance = (float) $bal;
                            break;
                        }
                    }
                }

                if ($foundBalance !== null) {
                    $this->bankAccountRepository->update(['balance' => $foundBalance], $bankAccount->code);

                    Log::channel('autotransfer_deposit_callback')->info('AutoTransfer updated bank_account balance', [
                        'bank_account_code' => $bankAccount->code,
                        'bank' => $targetBank,
                        'acc_last4' => $targetAcc !== '' ? substr($targetAcc, -4) : null,
                        'balance' => $foundBalance,
                        'txid' => $transactionId,
                    ]);
                } else {
                    Log::channel('autotransfer_deposit_callback')->warning('AutoTransfer getAccountBalances: no matching account', [
                        'bank_account_code' => $bankAccount->code,
                        'bank' => $targetBank,
                        'acc_last4' => $targetAcc !== '' ? substr($targetAcc, -4) : null,
                        'txid' => $transactionId,
                    ]);
                }
            } else {
                Log::channel('autotransfer_deposit_callback')->warning('AutoTransfer getAccountBalances not successful', [
                    'txid' => $transactionId,
                    'success' => (bool) data_get($res, 'success', false),
                    'status' => data_get($res, 'data.status'),
                ]);
            }
        } catch (Throwable $e) {
            Log::channel('autotransfer_deposit_callback')->warning('AutoTransfer balance update failed', [
                'txid' => $transactionId,
                'bank_account_code' => $bankAccount->code ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Withdraw callback (callback_url)
     */
    /**
     * Withdraw callback (callback_url)
     *
     * success:
     * - status.type=success
     * failure:
     * - status.type=failure -> rollback
     * ambiguous_failure:
     * - status.type=ambiguous_failure -> "HOLD" (no rollback)
     */

    public function withdraw_callback(Request $request)
    {
        $config  = core()->getConfigData();
        $payload = (array) $request->all();

        Log::channel('autotransfer_withdraw_callback')->info('AutoTransfer withdraw callback', $payload);

        $statusType    = strtolower((string) data_get($payload, 'status.type', ''));
        $statusCode    = (string) data_get($payload, 'status.code', '');
        $statusMessage = (string) data_get($payload, 'status.message', '');
        $transactionId = (string) data_get($payload, 'data.transaction_id', '');

        if ($transactionId === '') {
            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        $validStatuses = ['A', 'H', 'P','W'];
        $session = (string) data_get($payload, 'data.session', '');
        $refId = (string) (data_get($payload, 'data.transfer_ref_id') ?? data_get($payload, 'data.transfer_qr_code') ?? '');

        // parse code จาก session: AUTOWRD-007350-... => 7350
        $sessionCode = 0;
        if ($session !== '' && preg_match('/^AUTOWRD-(\d+)-/i', $session, $m)) {
            $sessionCode = (int) ltrim($m[1], '0');
        }

        // broadcast เดียวต่อ callback (ยิงหลัง commit)
        $broadcastMessage = null;
        $broadcastPayload = null;

        try {
            DB::transaction(function () use (
                $config,
                $payload,
                $statusType,
                $statusCode,
                $statusMessage,
                $transactionId,
                $validStatuses,
                $refId,
                $session,
                $sessionCode,
                &$broadcastMessage,
                &$broadcastPayload
            ) {
                $baseRepo = $config->seamless === 'Y'
                    ? app('Gametech\\Payment\\Repositories\\WithdrawSeamlessRepository')->query()
                    : app('Gametech\\Payment\\Repositories\\WithdrawRepository')->query();

                $data = null;

                // 1) txid (session) ก่อน
                if ($session !== '') {
                    $data = (clone $baseRepo)
                        ->where('txid', $session)
                        ->whereIn('status_withdraw', $validStatuses)
                        ->lockForUpdate()
                        ->first();
                }

                // 2) transaction_id
                if (! $data) {
                    $data = (clone $baseRepo)
                        ->where('transaction_id', $transactionId)
                        ->whereIn('status_withdraw', $validStatuses)
                        ->lockForUpdate()
                        ->first();
                }

                // 3) code จาก session (last resort)
                if (! $data && $sessionCode > 0) {
                    $data = (clone $baseRepo)
                        ->where('code', $sessionCode)
                        ->whereIn('status_withdraw', $validStatuses)
                        ->lockForUpdate()
                        ->first();
                }

                Log::channel('autotransfer_withdraw_callback')->info('AutoTransfer withdraw callback Data', ['raw' => $data]);

                if (! $data) {
                    // Diagnostic lookup: ไม่กรอง status เพื่อดูว่ามี row แต่โดนกรองทิ้งหรือไม่มีจริง
                    $diag = null;

                    if ($session !== '') {
                        $diag = (clone $baseRepo)->where('txid', $session)->first();
                    }
                    if (! $diag) {
                        $diag = (clone $baseRepo)->where('transaction_id', $transactionId)->first();
                    }
                    if (! $diag && $sessionCode > 0) {
                        $diag = (clone $baseRepo)->where('code', $sessionCode)->first();
                    }

                    Log::channel('autotransfer_withdraw_callback')->info('AutoTransfer withdraw callback Data FAIL', [
                        'raw' => $data,
                        'diag_found' => (bool) $diag,
                        'diag_status_withdraw' => $diag ? (string) ($diag->status_withdraw ?? '') : null,
                        'diag_code' => $diag ? ($diag->code ?? null) : null,
                        'diag_txid' => $diag ? ($diag->txid ?? null) : null,
                        'diag_transaction_id' => $diag ? ($diag->transaction_id ?? null) : null,
                        'lookup' => [
                            'seamless' => (string) ($config->seamless ?? ''),
                            'session' => $session,
                            'session_code' => $sessionCode,
                            'transaction_id' => $transactionId,
                            'valid_statuses' => $validStatuses,
                            'status_type' => $statusType,
                            'status_code' => $statusCode,
                        ],
                    ]);

                    return;
                }

                // idempotent: ปิดไปแล้วไม่ทำซ้ำ
                if (in_array((string) ($data->status_withdraw ?? ''), ['C', 'R'], true)) {
                    Log::channel('autotransfer_withdraw_callback')->info('AutoTransfer withdraw callback: already finalized', [
                        'txid' => $transactionId,
                        'status_withdraw' => (string) ($data->status_withdraw ?? ''),
                        'code' => $data->code ?? null,
                    ]);
                    return;
                }

                $amount = (float) ($data['amount'] ?? 0);

                // ===== SUCCESS =====
                if ($statusType === 'success') {
                    $uniqueAmount      = data_get($payload, 'data.unique_amount');
                    $responsePayeeName = data_get($payload, 'data.response_payee_name');
                    $viaService        = (string) data_get($payload, 'data.via_service', '');

                    $remark = '[ AutoTransfer ] โอนให้ลุกค้าแล้ว';
                    if ($refId !== '') $remark .= ' [ Ref:' . $refId . ' ]';
                    if ($uniqueAmount !== null) $remark .= ' [ Unique:' . $uniqueAmount . ' ]';
                    if ($responsePayeeName) $remark .= ' [ Payee:' . $responsePayeeName . ' ]';
                    if ($viaService !== '') $remark .= ' [ Via:' . $viaService . ' ]';

                    $data->status = 1;
                    $data->remark_admin = $remark;
                    $data->status_withdraw = 'C';
                    $data->save();

                    app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->create([
                        'ip' => request()->ip(),
                        'credit_type' => 'W',
                        'balance_before' => 0,
                        'balance_after' => 0,
                        'credit' => 0,
                        'total' => 0,
                        'credit_bonus' => 0,
                        'credit_total' => 0,
                        'credit_before' => $amount,
                        'credit_after' => $amount,
                        'pro_code' => 0,
                        'bank_code' => 0,
                        'auto' => 'N',
                        'enable' => 'Y',
                        'user_create' => 'System Auto',
                        'user_update' => 'System Auto',
                        'refer_code' => $data->code,
                        'refer_table' => 'withdraws',
                        'remark' => 'รายการแจ้งถอนที่ ' . $data->code . ' / ไอดีที่ถอน : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' โอนเงินให้ลูกค้าแล้ว AutoTransfer ' . $transactionId . ($refId !== '' ? ' [ ' . $refId . ' ]' : ''),
                        'kind' => 'OTHER',
                        'member_code' => $data->member_code,
                        'amount' => $amount,
                        'amount_balance' => 0,
                        'withdraw_limit' => 0,
                        'withdraw_limit_amount' => 0,
                    ]);

                    $broadcastMessage = 'Autotransfer ' . $data->txid . ' โอนเงินให้ลูกค้าแล้ว ID : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' รายการแจ้งถอนที่ ' . $data->code;
                    $broadcastPayload = [
                        'ui' => 'toast',
                        'as' => 'RealTime.Message.All',
                        'toast' => [
                            'className' => 'gt-toast gt-toast-success',
                            'duration' => 30000,
                            'gravity' => 'top',
                            'position' => 'right',
                            'avatar' => '/assets/admin/icons/alert.webp',
                        ],
                    ];

                    return;
                }

                // ===== AMBIGUOUS FAILURE (NO ROLLBACK) =====
                if ($statusType === 'ambiguous_failure') {
                    $message    = $statusMessage;
                    $viaService = (string) data_get($payload, 'data.via_service', '');

                    $data->remark_admin =
                        '[ AutoTransfer ] สถานะคลุมเครือ (ambiguous_failure) - ห้ามคืนยอดอัตโนมัติ โปรดตรวจสลิป/statement ก่อนตัดสินใจ'
                        . ' | TX:' . $transactionId
                        . ($statusCode !== '' ? ' | CODE:' . $statusCode : '')
                        . ($message !== '' ? ' | MSG:' . $message : '')
                        . ($viaService !== '' ? ' | VIA:' . $viaService : '');

                    $data->status_withdraw = 'H';
                    $data->status = 3;
                    $data->save();

                    // ✅ เพิ่ม status.message ใน broadcastMessage
                    $broadcastMessage = 'Autotransfer ยังไม่ทราบสถานะการโอน อาจะสำเร็จแล้วหรือยังไม่สำเร็จ โปรดรอต่อไป ของ ID ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . $refId
                        . ($message !== '' ? ' | ' . $message : '');

                    $broadcastPayload = [
                        'ui' => 'toast',
                        'as' => 'RealTime.Message.All',
                        'toast' => [
                            'className' => 'gt-toast gt-toast-error',
                            'duration' => 0,
                            'gravity' => 'top',
                            'position' => 'right',
                            'avatar' => '/assets/admin/icons/alert.webp',
                        ],
                    ];

                    return;
                }

                // ===== FAILURE (ROLLBACK) =====
                $message = $statusMessage;

                $data->remark_admin =
                    '[ AutoTransfer ] โอนไม่สำเร็จ'
                    . ($refId !== '' ? ' [ Ref:' . $refId . ' ]' : '')
                    . ' | TX:' . $transactionId
                    . ($statusCode !== '' ? ' | CODE:' . $statusCode : '')
                    . ($message !== '' ? ' | MSG:' . $message : '');

                try {
                    if ($config->seamless == 'Y') {
                        $datanew = [
                            'refer_code' => $data->code,
                            'refer_table' => 'withdraws',
                            'remark' => 'คืนยอดจากการถอน ' . $transactionId,
                            'kind' => 'ROLLBACK',
                            'amount' => $amount,
                            'amount_balance' => $data->amount_balance,
                            'withdraw_limit' => $data->amount_limit,
                            'withdraw_limit_amount' => $data->amount_limit_rate,
                            'method' => 'D',
                            'member_code' => $data->member_code,
                            'emp_code' => 0,
                            'emp_name' => 'SYSTEM',
                        ];
                        $response = app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->setWalletSeamlessWithdraw($datanew);
                    } else {
                        $datanew = [
                            'refer_code' => $data->code,
                            'refer_table' => 'withdraws',
                            'remark' => 'คืนยอดจากการถอน ' . $transactionId,
                            'kind' => 'ROLLBACK',
                            'amount' => $amount,
                            'amount_balance' => $data->amount_balance,
                            'withdraw_limit' => $data->amount_limit,
                            'withdraw_limit_amount' => $data->amount_limit_rate,
                            'pro_code' => $data->pro_code,
                            'pro_name' => $data->pro_name,
                            'method' => 'D',
                            'member_code' => $data->member_code,
                            'emp_code' => 0,
                            'emp_name' => 'SYSTEM',
                        ];
                        $response = app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->setWalletSingleWithdraw($datanew);
                    }

                    Log::channel('autotransfer_withdraw_callback')->info('AutoTransfer withdraw callback Response', ['raw' => $response]);

                    if ($response) {
                        // ✅ เพิ่ม status.message ใน broadcastMessage
                        $broadcastMessage = 'AutoTransfer ยกเลิกรายการแจ้งถอน ของ ID ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . $refId . ' ระบบคืนยอดให้ลูกค้าแล้ว'
                            . ($message !== '' ? ' | ' . $message : '');

                        $broadcastPayload = [
                            'ui' => 'toast',
                            'as' => 'RealTime.Message.All',
                            'toast' => [
                                'className' => 'gt-toast gt-toast-error',
                                'duration' => 0,
                                'gravity' => 'top',
                                'position' => 'right',
                                'avatar' => '/assets/admin/icons/alert.webp',
                            ],
                        ];

                        $data->remark_admin .= ' | ระบบคืนยอดแล้ว';
                    } else {
                        $data->remark_admin .= ' | ระบบคืนยอดไม่ได้ โปรดคืนยอดเอง';

                        // ✅ เพิ่ม status.message ใน broadcastMessage
                        $broadcastMessage = 'Autotransfer ยกเลิกรายการแจ้งถอน ของ ID ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . $refId . ' ระบบคืนยอด ให้ลูกค้าไม่ได้'
                            . ($message !== '' ? ' | ' . $message : '');

                        $broadcastPayload = [
                            'ui' => 'toast',
                            'as' => 'RealTime.Message.All',
                            'toast' => [
                                'className' => 'gt-toast gt-toast-error',
                                'duration' => 0,
                                'gravity' => 'top',
                                'position' => 'right',
                                'avatar' => '/assets/admin/icons/alert.webp',
                            ],
                        ];
                    }

                    $data->remark_admin .= ' | ระบบคืนยอดแล้ว';
                } catch (Throwable $e) {
                    Log::channel('autotransfer_withdraw_callback')->error('AutoTransfer withdraw callback: rollback failed', [
                        'txid' => $transactionId,
                        'error' => $e->getMessage(),
                    ]);

                    $data->remark_admin .= ' | ระบบคืนยอดไม่ได้ โปรดคืนยอดเอง';

                    // ✅ เพิ่ม status.message ใน broadcastMessage
                    $broadcastMessage = 'Autotransfer ยกเลิกรายการแจ้งถอน ของ ID ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . $refId . ' ระบบคืนยอด ให้ลูกค้าไม่ได้'
                        . ($message !== '' ? ' | ' . $message : '');

                    $broadcastPayload = [
                        'ui' => 'toast',
                        'as' => 'RealTime.Message.All',
                        'toast' => [
                            'className' => 'gt-toast gt-toast-error',
                            'duration' => 0,
                            'gravity' => 'top',
                            'position' => 'right',
                            'avatar' => '/assets/admin/icons/alert.webp',
                        ],
                    ];

                    $data->remark_admin .= ' | ระบบคืนยอดไม่ได้ โปรดคืนยอดเอง';
                }

                $data->status_withdraw = 'R';
                $data->status = 2;
                $data->save();
            });

            if ($broadcastMessage !== null && $broadcastPayload !== null) {
                broadcast(new RealTimeNewMessage($broadcastMessage, $broadcastPayload));
            }

            return response()->json(['code' => 0, 'msg' => 'success']);
        } catch (Throwable $e) {
            Log::channel('autotransfer_withdraw_callback')->error('AutoTransfer withdraw callback: fatal error', [
                'txid' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            // best-effort mark HOLD เพื่อกันหลุด แล้วให้คนตรวจ
            try {
                $baseRepo = $config->seamless === 'Y'
                    ? app('Gametech\\Payment\\Repositories\\WithdrawSeamlessRepository')->query()
                    : app('Gametech\\Payment\\Repositories\\WithdrawRepository')->query();

                $d = null;

                if ($session !== '') {
                    $d = (clone $baseRepo)->where('txid', $session)->lockForUpdate()->first();
                }

                if (! $d) {
                    $d = (clone $baseRepo)->where('transaction_id', $transactionId)->lockForUpdate()->first();
                }

                if (! $d && $sessionCode > 0) {
                    $d = (clone $baseRepo)->where('code', $sessionCode)->lockForUpdate()->first();
                }

                if ($d && ! in_array((string) ($d->status_withdraw ?? ''), ['C', 'R'], true)) {
                    $d->status_withdraw = 'H';
                    $d->status = 3;
                    $d->remark_admin = '[ AutoTransfer ] CALLBACK ERROR - set HOLD | TX:' . $transactionId . ' | ERR:' . $e->getMessage();
                    $d->save();
                }
            } catch (Throwable $inner) {
                Log::channel('autotransfer_withdraw_callback')->error('AutoTransfer withdraw callback: failed to mark HOLD', [
                    'txid' => $transactionId,
                    'error' => $inner->getMessage(),
                ]);
            }

            return response()->json(['code' => 0, 'msg' => 'success']);
        }
    }
    public function withdraw_callback_last(Request $request)
    {
        $config  = core()->getConfigData();
        $payload = (array) $request->all();

        Log::channel('autotransfer_withdraw_callback')->info('AutoTransfer withdraw callback', $payload);

        $statusType    = strtolower((string) data_get($payload, 'status.type', ''));
        $statusCode    = (string) data_get($payload, 'status.code', '');
        $statusmessage    = (string) data_get($payload, 'status.message', '');
        $transactionId = (string) data_get($payload, 'data.transaction_id', '');

        if ($transactionId === '') {
            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        // เดิมหาเฉพาะ A ทำให้ถ้า job ใช้ P (processing) แล้ว callback จะหาไม่เจอ
        $validStatuses = ['A', 'H', 'P'];

        $session = (string) data_get($payload, 'data.session', '');
        $refId = (string) (data_get($payload, 'data.transfer_ref_id') ?? data_get($payload, 'data.transfer_qr_code') ?? '');

        // parse code จาก session: AUTOWRD-007350-20260224011716 => 7350
        $sessionCode = 0;
        if ($session !== '' && preg_match('/^AUTOWRD-(\d+)-/i', $session, $m)) {
            $sessionCode = (int) ltrim($m[1], '0');
        }

        // broadcast เดียวต่อ callback (ยิงหลัง commit)
        $broadcastMessage = null;
        $broadcastPayload = null;

        try {
            DB::transaction(function () use (
                $config,
                $payload,
                $statusType,
                $statusCode,
                $transactionId,
                $validStatuses,
                $refId,
                $session,
                $sessionCode,
                &$broadcastMessage,
                &$broadcastPayload
            ) {
                $baseRepo = $config->seamless === 'Y'
                    ? app('Gametech\\Payment\\Repositories\\WithdrawSeamlessRepository')->query()
                    : app('Gametech\\Payment\\Repositories\\WithdrawRepository')->query();

                $data = null;

                // 1) txid (session) ก่อน
                if ($session !== '') {
                    $data = (clone $baseRepo)
                        ->where('txid', $session)
                        ->whereIn('status_withdraw', $validStatuses)
                        ->lockForUpdate()
                        ->first();
                }

                // 2) transaction_id
                if (! $data) {
                    $data = (clone $baseRepo)
                        ->where('transaction_id', $transactionId)
                        ->whereIn('status_withdraw', $validStatuses)
                        ->lockForUpdate()
                        ->first();
                }

                // 3) code จาก session (last resort)
                if (! $data && $sessionCode > 0) {
                    $data = (clone $baseRepo)
                        ->where('code', $sessionCode)
                        ->whereIn('status_withdraw', $validStatuses)
                        ->lockForUpdate()
                        ->first();
                }

                Log::channel('autotransfer_withdraw_callback')->info('AutoTransfer withdraw callback Data', ['raw' => $data]);

                if (! $data) {
                    // ---- Diagnostic lookup: ตัด whereIn ออก เพื่อดูว่ามี row แต่โดนกรอง status ทิ้งหรือไม่มีจริง ----
                    $diag = null;

                    if ($session !== '') {
                        $diag = (clone $baseRepo)->where('txid', $session)->first();
                    }

                    if (! $diag) {
                        $diag = (clone $baseRepo)->where('transaction_id', $transactionId)->first();
                    }

                    if (! $diag && $sessionCode > 0) {
                        $diag = (clone $baseRepo)->where('code', $sessionCode)->first();
                    }

                    Log::channel('autotransfer_withdraw_callback')->info('AutoTransfer withdraw callback Data FAIL', [
                        'raw' => $data,
                        'diag_found' => (bool) $diag,
                        'diag_status_withdraw' => $diag ? (string) ($diag->status_withdraw ?? '') : null,
                        'diag_code' => $diag ? ($diag->code ?? null) : null,
                        'diag_txid' => $diag ? ($diag->txid ?? null) : null,
                        'diag_transaction_id' => $diag ? ($diag->transaction_id ?? null) : null,
                        'lookup' => [
                            'seamless' => (string) ($config->seamless ?? ''),
                            'session' => $session,
                            'session_code' => $sessionCode,
                            'transaction_id' => $transactionId,
                            'valid_statuses' => $validStatuses,
                            'status_type' => $statusType,
                            'status_code' => $statusCode,
                        ],
                    ]);

                    return;
                }

                // idempotent: ปิดไปแล้วไม่ทำซ้ำ
                if (in_array((string) ($data->status_withdraw ?? ''), ['C', 'R'], true)) {
                    Log::channel('autotransfer_withdraw_callback')->info('AutoTransfer withdraw callback: already finalized', [
                        'txid' => $transactionId,
                        'status_withdraw' => (string) ($data->status_withdraw ?? ''),
                        'code' => $data->code ?? null,
                    ]);
                    return;
                }

                $amount = (float) ($data['amount'] ?? 0);

                // ===== SUCCESS =====
                if ($statusType === 'success') {
                    $uniqueAmount      = data_get($payload, 'data.unique_amount');
                    $responsePayeeName = data_get($payload, 'data.response_payee_name');
                    $viaService        = (string) data_get($payload, 'data.via_service', '');

                    $remark = '[ AutoTransfer ] โอนให้ลุกค้าแล้ว';
                    if ($refId !== '') $remark .= ' [ Ref:' . $refId . ' ]';
                    if ($uniqueAmount !== null) $remark .= ' [ Unique:' . $uniqueAmount . ' ]';
                    if ($responsePayeeName) $remark .= ' [ Payee:' . $responsePayeeName . ' ]';
                    if ($viaService !== '') $remark .= ' [ Via:' . $viaService . ' ]';

                    $data->status = 1;
                    $data->remark_admin = $remark;
                    $data->status_withdraw = 'C';
                    $data->save();

                    app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->create([
                        'ip' => request()->ip(),
                        'credit_type' => 'W',
                        'balance_before' => 0,
                        'balance_after' => 0,
                        'credit' => 0,
                        'total' => 0,
                        'credit_bonus' => 0,
                        'credit_total' => 0,
                        'credit_before' => $amount,
                        'credit_after' => $amount,
                        'pro_code' => 0,
                        'bank_code' => 0,
                        'member_code' => $data->member_code,
                        'auto' => 'N',
                        'enable' => 'Y',
                        'user_create' => 'System Auto',
                        'user_update' => 'System Auto',
                        'refer_code' => $data->code,
                        'refer_table' => 'withdraws',
                        'remark' => 'รายการแจ้งถอนที่ ' . $data->code . ' / ไอดีที่ถอน : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' โอนเงินให้ลูกค้าแล้ว AutoTransfer ' . $transactionId . ($refId !== '' ? ' [ ' . $refId . ' ]' : ''),
                        'kind' => 'OTHER',
                        'amount' => $amount,
                        'amount_balance' => 0,
                        'withdraw_limit' => 0,
                        'withdraw_limit_amount' => 0,
                    ]);

                    $broadcastMessage = 'Autotransfer ' . $data->txid . ' โอนเงินให้ลูกค้าแล้ว ID : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' รายการแจ้งถอนที่ ' . $data->code;
                    $broadcastPayload = [
                        'ui' => 'toast',
                        'as' => 'RealTime.Message.All',
                        'toast' => [
                            'className' => 'gt-toast gt-toast-success',
                            'duration' => 30000,
                            'gravity' => 'top',
                            'position' => 'right',
                            'avatar' => '/assets/admin/icons/alert.webp',
                        ],
                    ];

                    return;
                }

                // ===== AMBIGUOUS FAILURE (NO ROLLBACK) =====
                if ($statusType === 'ambiguous_failure') {
                    $message    = (string) data_get($payload, 'status.message', '');
                    $viaService = (string) data_get($payload, 'data.via_service', '');

                    $data->remark_admin =
                        '[ AutoTransfer ] สถานะคลุมเครือ (ambiguous_failure) - ห้ามคืนยอดอัตโนมัติ โปรดตรวจสลิป/statement ก่อนตัดสินใจ'
                        . ' | TX:' . $transactionId
                        . ($statusCode !== '' ? ' | CODE:' . $statusCode : '')
                        . ($message !== '' ? ' | MSG:' . $message : '')
                        . ($viaService !== '' ? ' | VIA:' . $viaService : '');

                    $data->status_withdraw = 'H';
                    $data->status = 3;
                    $data->save();

                    $broadcastMessage = 'Autotransfer ยังไม่ทราบสถานะการโอน อาจะสำเร็จแล้วหรือยังไม่สำเร็จ โปรดรอต่อไป ของ ID ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . $refId;
                    $broadcastPayload = [
                        'ui' => 'toast',
                        'as' => 'RealTime.Message.All',
                        'toast' => [
                            'className' => 'gt-toast gt-toast-error',
                            'duration' => 0,
                            'gravity' => 'top',
                            'position' => 'right',
                            'avatar' => '/assets/admin/icons/alert.webp',
                        ],
                    ];

                    return;
                }

                // ===== FAILURE (ROLLBACK) =====
                $message = (string) data_get($payload, 'status.message', '');
                $data->remark_admin =
                    '[ AutoTransfer ] โอนไม่สำเร็จ'
                    . ($refId !== '' ? ' [ Ref:' . $refId . ' ]' : '')
                    . ' | TX:' . $transactionId
                    . ($statusCode !== '' ? ' | CODE:' . $statusCode : '')
                    . ($message !== '' ? ' | MSG:' . $message : '');

                try {
                    if ($config->seamless == 'Y') {
                        $datanew = [
                            'refer_code' => $data->code,
                            'refer_table' => 'withdraws',
                            'remark' => 'คืนยอดจากการถอน ' . $transactionId,
                            'kind' => 'ROLLBACK',
                            'amount' => $amount,
                            'amount_balance' => $data->amount_balance,
                            'withdraw_limit' => $data->amount_limit,
                            'withdraw_limit_amount' => $data->amount_limit_rate,
                            'method' => 'D',
                            'member_code' => $data->member_code,
                            'emp_code' => 0,
                            'emp_name' => 'SYSTEM',
                        ];
                        $response = app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->setWalletSeamlessWithdraw($datanew);
                    } else {
                        $datanew = [
                            'refer_code' => $data->code,
                            'refer_table' => 'withdraws',
                            'remark' => 'คืนยอดจากการถอน ' . $transactionId,
                            'kind' => 'ROLLBACK',
                            'amount' => $amount,
                            'amount_balance' => $data->amount_balance,
                            'withdraw_limit' => $data->amount_limit,
                            'withdraw_limit_amount' => $data->amount_limit_rate,
                            'pro_code' => $data->pro_code,
                            'pro_name' => $data->pro_name,
                            'method' => 'D',
                            'member_code' => $data->member_code,
                            'emp_code' => 0,
                            'emp_name' => 'SYSTEM',
                        ];
                        $response = app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->setWalletSingleWithdraw($datanew);
                    }

                    Log::channel('autotransfer_withdraw_callback')->info('AutoTransfer withdraw callback Response', ['raw' => $response]);

                    if ($response) {
                        $broadcastMessage = 'AutoTransfer ยกเลิกรายการแจ้งถอน ของ ID ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . $refId . ' ระบบคืนยอดให้ลูกค้าแล้ว';
                        $broadcastPayload = [
                            'ui' => 'toast',
                            'as' => 'RealTime.Message.All',
                            'toast' => [
                                'className' => 'gt-toast gt-toast-error',
                                'duration' => 0,
                                'gravity' => 'top',
                                'position' => 'right',
                                'avatar' => '/assets/admin/icons/alert.webp',
                            ],
                        ];
                        $data->remark_admin .= ' | ระบบคืนยอดแล้ว';
                    } else {
                        $data->remark_admin .= ' | ระบบคืนยอดไม่ได้ โปรดคืนยอดเอง';
                        $broadcastMessage = 'Autotransfer ยกเลิกรายการแจ้งถอน ของ ID ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . $refId . ' ระบบคืนยอด ให้ลูกค้าไม่ได้';
                        $broadcastPayload = [
                            'ui' => 'toast',
                            'as' => 'RealTime.Message.All',
                            'toast' => [
                                'className' => 'gt-toast gt-toast-error',
                                'duration' => 0,
                                'gravity' => 'top',
                                'position' => 'right',
                                'avatar' => '/assets/admin/icons/alert.webp',
                            ],
                        ];
                    }
                } catch (Throwable $e) {
                    Log::channel('autotransfer_withdraw_callback')->error('AutoTransfer withdraw callback: rollback failed', [
                        'txid' => $transactionId,
                        'error' => $e->getMessage(),
                    ]);

                    $data->remark_admin .= ' | ระบบคืนยอดไม่ได้ โปรดคืนยอดเอง';

                    $broadcastMessage = 'Autotransfer ยกเลิกรายการแจ้งถอน ของ ID ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . $refId . ' ระบบคืนยอด ให้ลูกค้าไม่ได้';
                    $broadcastPayload = [
                        'ui' => 'toast',
                        'as' => 'RealTime.Message.All',
                        'toast' => [
                            'className' => 'gt-toast gt-toast-error',
                            'duration' => 0,
                            'gravity' => 'top',
                            'position' => 'right',
                            'avatar' => '/assets/admin/icons/alert.webp',
                        ],
                    ];
                }

                $data->status_withdraw = 'R';
                $data->status = 2;
                $data->save();
            });

            if ($broadcastMessage !== null && $broadcastPayload !== null) {
                broadcast(new RealTimeNewMessage($broadcastMessage, $broadcastPayload));
            }

            return response()->json(['code' => 0, 'msg' => 'success']);
        } catch (Throwable $e) {
            Log::channel('autotransfer_withdraw_callback')->error('AutoTransfer withdraw callback: fatal error', [
                'txid' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            // best-effort mark HOLD เพื่อกันหลุด แล้วให้คนตรวจ
            try {
                $baseRepo = $config->seamless === 'Y'
                    ? app('Gametech\\Payment\\Repositories\\WithdrawSeamlessRepository')->query()
                    : app('Gametech\\Payment\\Repositories\\WithdrawRepository')->query();

                $d = null;

                if ($session !== '') {
                    $d = (clone $baseRepo)->where('txid', $session)->lockForUpdate()->first();
                }

                if (! $d) {
                    $d = (clone $baseRepo)->where('transaction_id', $transactionId)->lockForUpdate()->first();
                }

                if (! $d && $sessionCode > 0) {
                    $d = (clone $baseRepo)->where('code', $sessionCode)->lockForUpdate()->first();
                }

                if ($d && ! in_array((string) ($d->status_withdraw ?? ''), ['C', 'R'], true)) {
                    $d->status_withdraw = 'H';
                    $d->status = 3;
                    $d->remark_admin = '[ AutoTransfer ] CALLBACK ERROR - set HOLD | TX:' . $transactionId . ' | ERR:' . $e->getMessage();
                    $d->save();
                }
            } catch (Throwable $inner) {
                Log::channel('autotransfer_withdraw_callback')->error('AutoTransfer withdraw callback: failed to mark HOLD', [
                    'txid' => $transactionId,
                    'error' => $inner->getMessage(),
                ]);
            }

            // callback ห้ามทำให้ provider retry
            return response()->json(['code' => 0, 'msg' => 'success']);
        }
    }
    public function withdraw_callback_(Request $request)
    {
        $config = core()->getConfigData();
        $payload = (array)$request->all();

        Log::channel('autotransfer_withdraw_callback')->info('AutoTransfer withdraw callback', $payload);

//        $requiredKey = (string) config('autotransfer.triggered_apikey', '');
//        if ($requiredKey !== '') {
//            $inKey = (string) $request->header('apikey', '');
//            if (!hash_equals($requiredKey, $inKey)) {
//                Log::channel('autotransfer_withdraw_callback')->warning('AutoTransfer withdraw callback: invalid apikey', [
//                    'ip' => $request->ip(),
//                ]);
//
//                return response()->json(['code' => 401, 'msg' => 'invalid_apikey'], 401);
//            }
//        }

        $statusType = strtolower((string)data_get($payload, 'status.type', ''));
        $statusCode = (string)data_get($payload, 'status.code', '');
        $transactionId = (string)data_get($payload, 'data.transaction_id', '');

        if ($transactionId === '') {
            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        // เดิมหาเฉพาะ A ทำให้ถ้า job ใช้ P (processing) แล้ว callback จะหาไม่เจอ
        $validStatuses = ['A','H'];

        if ($config->seamless === 'Y') {
            $data = app('Gametech\\Payment\\Repositories\\WithdrawSeamlessRepository')->query()->where('transaction_id', $transactionId)->whereIn('status_withdraw', $validStatuses)->first();
        } else {
            $data = app('Gametech\\Payment\\Repositories\\WithdrawRepository')->query()->where('transaction_id', $transactionId)->whereIn('status_withdraw', $validStatuses)->first();
        }
        Log::channel('autotransfer_withdraw_callback')->info('AutoTransfer withdraw callback Data', [ 'raw' => $data]);


        if (!$data) {
            Log::channel('autotransfer_withdraw_callback')->info('AutoTransfer withdraw callback Data FAIL', [ 'raw' => $data]);

            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        $amount = (float)($data['amount'] ?? 0);
        $refId = (string)(data_get($payload, 'data.transfer_ref_id') ?? data_get($payload, 'data.transfer_qr_code') ?? '');

        // ===== SUCCESS =====
        if ($statusType === 'success') {
            $uniqueAmount = data_get($payload, 'data.unique_amount');
            $responsePayeeName = data_get($payload, 'data.response_payee_name');
            $viaService = (string)data_get($payload, 'data.via_service', '');

            $remark = '[ AutoTransfer ] โอนให้ลุกค้าแล้ว';
            if ($refId !== '') $remark .= ' [ Ref:' . $refId . ' ]';
            if ($uniqueAmount !== null) $remark .= ' [ Unique:' . $uniqueAmount . ' ]';
            if ($responsePayeeName) $remark .= ' [ Payee:' . $responsePayeeName . ' ]';
            if ($viaService !== '') $remark .= ' [ Via:' . $viaService . ' ]';

            $data->status = 1;
            $data->remark_admin = $remark;
            $data->status_withdraw = 'C';
            $data->save();

            app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->create(['ip' => request()->ip(), 'credit_type' => 'W', 'balance_before' => 0, 'balance_after' => 0, 'credit' => 0, 'total' => 0, 'credit_bonus' => 0, 'credit_total' => 0, 'credit_before' => $amount, 'credit_after' => $amount, 'pro_code' => 0, 'bank_code' => 0, 'auto' => 'N', 'enable' => 'Y', 'user_create' => 'System Auto', 'user_update' => 'System Auto', 'refer_code' => $data->code, 'refer_table' => 'withdraws', 'remark' => 'รายการแจ้งถอนที่ ' . $data->code . ' / ไอดีที่ถอน : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' โอนเงินให้ลูกค้าแล้ว AutoTransfer ' . $transactionId . ($refId !== '' ? ' [ ' . $refId . ' ]' : ''), 'kind' => 'OTHER', 'amount' => $amount, 'amount_balance' => 0, 'withdraw_limit' => 0, 'withdraw_limit_amount' => 0,]);

            broadcast(new RealTimeNewMessage('Autotransfer ' . $data->txid . ' โอนเงินให้ลูกค้าแล้ว ID : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' รายการแจ้งถอนที่ ' . $data->code, ['ui' => 'toast', 'as' => 'RealTime.Message.All', 'toast' => ['className' => 'gt-toast gt-toast-success', 'duration' => 30000, 'gravity' => 'top', 'position' => 'right', 'avatar' => '/assets/admin/icons/alert.webp',],]));

            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        // ===== AMBIGUOUS FAILURE (NO ROLLBACK) =====
        if ($statusType === 'ambiguous_failure') {
            $message = (string)data_get($payload, 'status.message', '');
            $viaService = (string)data_get($payload, 'data.via_service', '');

            // ห้ามคืนเงินอัตโนมัติ
            $data->remark_admin = '[ AutoTransfer ] สถานะคลุมเครือ (ambiguous_failure) - ห้ามคืนยอดอัตโนมัติ โปรดตรวจสลิป/statement ก่อนตัดสินใจ' . ' | TX:' . $transactionId . ($statusCode !== '' ? ' | CODE:' . $statusCode : '') . ($message !== '' ? ' | MSG:' . $message : '') . ($viaService !== '' ? ' | VIA:' . $viaService : '');

            // แนะนำให้เปลี่ยนเป็นสถานะ hold/manual
            $data->status_withdraw = 'H';
            $data->status = 3; // แยกจาก fail+rollback (ถ้าระบบคุณมี convention อื่น บอกได้)
            $data->save();

            broadcast(new RealTimeNewMessage('Autotransfer ยังไม่ทราบสถานะการโอน อาจะสำเร็จแล้วหรือยังไม่สำเร็จ โปรดรอต่อไป ของ ID ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . $refId, ['ui' => 'toast', 'as' => 'RealTime.Message.All', 'toast' => ['className' => 'gt-toast gt-toast-error', 'duration' => 0, 'gravity' => 'top', 'position' => 'right', 'avatar' => '/assets/admin/icons/alert.webp',],]));

            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        // ===== FAILURE (ROLLBACK) =====
        $message = (string)data_get($payload, 'status.message', '');
        $data->remark_admin = '[ AutoTransfer ] โอนไม่สำเร็จ' . ($refId !== '' ? ' [ Ref:' . $refId . ' ]' : '') . ' | TX:' . $transactionId . ($statusCode !== '' ? ' | CODE:' . $statusCode : '') . ($message !== '' ? ' | MSG:' . $message : '');

        try {
            if ($config->seamless == 'Y') {
                $datanew = ['refer_code' => $data->code, 'refer_table' => 'withdraws', 'remark' => 'คืนยอดจากการถอน ' . $transactionId, 'kind' => 'ROLLBACK', 'amount' => $amount, 'amount_balance' => $data->amount_balance, 'withdraw_limit' => $data->amount_limit, 'withdraw_limit_amount' => $data->amount_limit_rate, 'method' => 'D', 'member_code' => $data->member_code, 'emp_code' => 0, 'emp_name' => 'SYSTEM',];
                $response = app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->setWalletSeamlessWithdraw($datanew);
            } else {
                $datanew = ['refer_code' => $data->code, 'refer_table' => 'withdraws', 'remark' => 'คืนยอดจากการถอน ' . $transactionId, 'kind' => 'ROLLBACK', 'amount' => $amount, 'amount_balance' => $data->amount_balance, 'withdraw_limit' => $data->amount_limit, 'withdraw_limit_amount' => $data->amount_limit_rate, 'pro_code' => $data->pro_code, 'pro_name' => $data->pro_name, 'method' => 'D', 'member_code' => $data->member_code, 'emp_code' => 0, 'emp_name' => 'SYSTEM',];
                $response = app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->setWalletSingleWithdraw($datanew);
            }
            Log::channel('autotransfer_withdraw_callback')->info('AutoTransfer withdraw callback Response', [ 'raw' => $response]);

            if ($response) {

                broadcast(new RealTimeNewMessage('AutoTransfer ยกเลิกรายการแจ้งถอน ของ ID ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . $refId . ' ระบบคืนยอดให้ลูกค้าแล้ว', ['ui' => 'toast', 'as' => 'RealTime.Message.All', 'toast' => ['className' => 'gt-toast gt-toast-error', 'duration' => 0, 'gravity' => 'top', 'position' => 'right', 'avatar' => '/assets/admin/icons/alert.webp',],]));
            }

            $data->remark_admin .= ' | ระบบคืนยอดแล้ว';
        } catch (Throwable $e) {
            Log::channel('autotransfer_withdraw_callback')->error('AutoTransfer withdraw callback: rollback failed', ['txid' => $transactionId, 'error' => $e->getMessage(),]);

            broadcast(new RealTimeNewMessage('Autotransfer ยกเลิกรายการแจ้งถอน ของ ID ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . $refId . ' ระบบคืนยอด ให้ลูกค้าไม่ได้', ['ui' => 'toast', 'as' => 'RealTime.Message.All', 'toast' => ['className' => 'gt-toast gt-toast-error', 'duration' => 0, 'gravity' => 'top', 'position' => 'right', 'avatar' => '/assets/admin/icons/alert.webp',],]));

            $data->remark_admin .= ' | ระบบคืนยอดไม่ได้ โปรดคืนยอดเอง';
        }

        $data->status_withdraw = 'R';
        $data->status = 2;
        $data->save();

        return response()->json(['code' => 0, 'msg' => 'success']);
    }
}
