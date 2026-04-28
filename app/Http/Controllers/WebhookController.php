<?php

namespace App\Http\Controllers;

use App\Events\RealTimeMessage;
use DateTime;
use Exception;
use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Member\Models\Member;
use Gametech\Payment\Models\BankPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WebhookController extends AppBaseController
{
    protected $_config;

    public function __construct()
    {
        $this->_config = request('_config');

        //        $this->middleware('api');
    }

    public function index($mobile, Request $request)
    {
        $datenow = now()->toDateTimeString();

        $this->appendWebhookLog('tw', $mobile, $request->all(), $datenow);

        // ไม่มี message → เงียบ ๆ กลับ 1
        if (!$request->has('message')) {
            return response()->json(['success' => true, 'code' => 200, 'message' => 'Success.',], 200, ['Content-Type' => 'application/json',]);
        }

        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('tw', $mobile);
        if (!$data) {
            return response()->json(['success' => true, 'code' => 200, 'message' => 'Success.',], 200, ['Content-Type' => 'application/json',]);
        }

        if ($data->webhook != 'Y') {
            return response()->json(['success' => true, 'code' => 200, 'message' => 'Success.',], 200, ['Content-Type' => 'application/json',]);
        }

        $data->checktime = $datenow;
        $data->save();

        $message = $request->input('message');

        // ดึงฟิลด์ที่ต้องใช้ (จะได้ null หาก payload เป็น handshake/ไม่ครบ)
        $received_time = $this->getJwtPayloadField($message, 'received_time');
        $sender_mobile = $this->getJwtPayloadField($message, 'sender_mobile');
        $amountRaw = $this->getJwtPayloadField($message, 'amount');       // หน่วยสตางค์จากผู้ให้บริการ (เช่น 15000 = 150.00)
        $event_type = $this->getJwtPayloadField($message, 'event_type');

        // ถ้า payload เป็น handshake / หรือขาดฟิลด์สำคัญ → ไม่ทำอะไรต่อ
        if (is_null($received_time) || is_null($sender_mobile) || is_null($amountRaw) || is_null($event_type)) {
            return response()->json(['success' => true, 'code' => 200, 'message' => 'Success.',], 200, ['Content-Type' => 'application/json',]);
        }

        // แปลงจำนวนเงินจากสตางค์ → บาท (กัน type ก่อน)
        $amount = is_numeric($amountRaw) ? ((float)$amountRaw / 100) : null;
        if ($amount === null) {
            return response()->json(['success' => true, 'code' => 200, 'message' => 'Success.',], 200, ['Content-Type' => 'application/json',]);
        }

        // Normalize เวลา: 2025-10-30T12:31:10+0700 → 2025-10-30 12:31:10
        $date = (string)$received_time;
        $date = Str::replace('T', ' ', $date);
        $date = Str::replace('+0700', '', $date);

        // สร้าง hash ป้องกันซ้ำ
        $hash = md5($data->code . $date . $amount . $sender_mobile);

        $member_code = 0;
        $autocheck = 'Y';
        $remarkAdmin = 'ไม่พบสมาชิกจากเบอร์โทรนี้';

        $members = Member::query()
            ->where('tel', $sender_mobile)
            ->get(['code', 'user_name', 'firstname']);

        if ($members->count() === 1) {
            $member = $members->first();
            $member_code = (int) $member->code;
            $autocheck = 'W';

            $displayName = $member->user_name;
            if (!empty($member->firstname)) {
                $displayName .= ' (' . $member->firstname . ')';
            }

            $remarkAdmin = 'พบสมาชิกจากเบอร์โทร ' . $displayName . ' รอระบบเติมอัตโนมัติ';
        } elseif ($members->count() > 1) {
            $remarkAdmin = 'พบสมาชิกจากเบอร์โทร ' . $members->count() . ' รายการ';
        }

        $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
        $newpayment->account_code = $data->code;
        $newpayment->bank = 'tw_' . $mobile;
        $newpayment->bankstatus = 1;
        $newpayment->autocheck = $autocheck;
        $newpayment->remark_admin = $remarkAdmin;
        $newpayment->bankname = 'TW';
        $newpayment->report_id = '';
        $newpayment->bank_time = $date;
        $newpayment->type = $event_type;
        $newpayment->title = 'Webhook';
        $newpayment->channel = 'WEBHOOK';
        $newpayment->value = $amount;
        $newpayment->tx_hash = $hash;
        $newpayment->detail = $sender_mobile;
        $newpayment->atranferer = $sender_mobile;
        $newpayment->member_topup = $member_code;
        $newpayment->time = $date;
        $newpayment->create_by = 'SYSAUTO';
        $newpayment->save();

        return response()->json(['success' => true, 'code' => 200, 'message' => 'Success.',], 200, ['Content-Type' => 'application/json',]);
    }

    public function getJwtPayloadField($jwt, $field)
    {
        // ป้องกัน error: ถ้ารูปแบบไม่ใช่ header.payload.signature → คืน null
        $parts = explode('.', (string)$jwt);
        if (count($parts) !== 3) {
            return null;
        }

        // base64url decode (เติม padding เผื่อขาด)
        $payload = $parts[1];
        $payload = strtr($payload, '-_', '+/');
        $padLen = (4 - (strlen($payload) % 4)) % 4;
        if ($padLen > 0) {
            $payload .= str_repeat('=', $padLen);
        }

        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            return null;
        }

        $data = json_decode($decoded, true);
        if (!is_array($data)) {
            return null;
        }

        return $data[$field] ?? null;
    }

    public function scb($mobile, Request $request)
    {
        $datenow = now()->toDateTimeString();

        $this->appendWebhookLog('scb', $mobile, $request->all(), $datenow);

        $repo = app('Gametech\Payment\Repositories\BankAccountRepository');

        // ✅ ใช้ $mobile แทน $this->id
        $bank = $repo->getAccountOne('scb', $mobile);

        if (!$bank) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => 'ไม่พบบัญชีธนาคาร SCB สำหรับ mobile นี้',
            ], 200);
        }

        if ($bank->webhook === 'N') {
            $bank->api_refresh = 'ใช้ดึงจาก API อยู่';
            $bank->checktime = $datenow;
            $bank->save();

            return response()->json([
                'success' => false,
                'code' => 403,
                'message' => 'Webhook ถูกปิดใช้งานสำหรับบัญชีนี้',
            ], 200);
        }

        // ✅ webhook = 1 รายการ/ครั้ง → ใช้ $row เป็น payload ตัวจริง และห้าม reuse เป็นอย่างอื่น
        $row = $request->all();
        $event = $row['event'] ?? 'deposit';

        $syncBalanceToSiblingAccounts = static function ($repo, $bank, float $balance, string $checkTime, string $apiRefresh): void {
            $repo->query()
                ->where('banks', $bank->banks)
                ->where('acc_no', $bank->acc_no)
                ->whereIn('bank_type', [1, 2])
                ->update([
                    'balance' => $balance,
                    'checktime' => $checkTime,
                    'api_refresh' => $apiRefresh,
                    'date_update' => now(),
                ]);
        };

        if ($event === 'balance') {
            $balance = floatval($row['lastBalance'] ?? $bank->balance ?? 0);
            $checkTime = (string)($row['fullDate'] ?? $datenow);
            $apiRefresh = 'อัปเดตยอดคงเหลือล่าสุด ' . $checkTime;

            $syncBalanceToSiblingAccounts($repo, $bank, $balance, $checkTime, $apiRefresh);

            return response()->json([
                'success' => true,
                'code' => 200,
                'message' => 'Success.',
            ], 200);
        }

        if (!isset($row['fullDate'], $row['transactionID'], $row['amount'])) {
            return response()->json([
                'success' => false,
                'code' => 422,
                'message' => 'Payload ไม่ครบถ้วน',
            ], 200);
        }

        $diff = core()->DateDiffMin($row['fullDate']);
        if ($diff < 0 || $diff > 10) {
            return response()->json([
                'success' => false,
                'code' => 422,
                'message' => 'รายการส่งมาช้าเกิน 10 นาที หรือเวลาในอนาคต',
            ], 200);
        }

        $accname = str_replace('-', '', (string) $bank->acc_no);

        // ใช้เลขท้ายจาก from_acc แบบเดิม
        $suffixFromAcc = isset($row['from_acc']) ? preg_replace('/\D/', '', (string) $row['from_acc']) : '';

        $frombank = rtrim((string)($row['from_bank'] ?? ''), 'A');
        $bank_code = $this->Banks($frombank);
        $memberBankCodes = $this->resolveMemberBankCodes($frombank, $bank_code);
        $cleanName = $this->splitNameUniversal((string)($row['from_name'] ?? ''));

        // ✅ สูตร hash ต้องเหมือนเดิม ห้ามเปลี่ยน
        $txHash = md5(
            (string)($row['lastBalance'] ?? '')
            . (string)($row['amount'] ?? '')
            . (string)($row['from_acc'] ?? '')
            . (string)($row['fullDate'] ?? '')
        );

        $prepared = [
            'raw' => $row,
            'tx_hash' => $txHash,
            'from_bank' => $frombank,
            'bank_code' => $bank_code,
            'member_bank_codes' => $memberBankCodes,
            'bank_found' => !empty($memberBankCodes),
            'from_name' => $cleanName,
            'suffix' => $suffixFromAcc,
        ];

        $found = false;
        $concat = 'ไม่พบหมายเลขบัญชี';
        $member_code = 0;

        $column = 'acc_no';
        $suffix = '';
        $len = 0;
        $members = collect();
        if (!empty($prepared['member_bank_codes']) && $prepared['suffix'] !== '' && !empty($prepared['from_name'])) {
            $suffix = preg_replace('/\D+/', '', $prepared['suffix']);
            $len = strlen($suffix);

            if ($len > 0) {
                $selectColumns = ['user_name', 'firstname', 'code'];
                $hasNameAddon = Schema::hasColumn((new Member())->getTable(), 'name_addon');
                $escaped = addcslashes(trim($prepared['from_name']), '%_');

                $members = Member::query()
                    ->whereIn('bank_code', $prepared['member_bank_codes'])
                    ->whereRaw("RIGHT({$column}, ?) = ?", [$len, $suffix])
                    ->where(function ($q) use ($escaped, $hasNameAddon) {
                        $q->where('name', 'LIKE', "%{$escaped}%");

                        if ($hasNameAddon) {
                            $q->orWhere('name_addon', 'LIKE', "%{$escaped}%");
                        }
                    })
                    ->get($selectColumns);
            }
        }

        $formatDisplay = function ($m) {
            $user = $m->user_name;
            $first = $m->firstname ?? '';

            return $first !== '' ? "{$user} ({$first})" : $user;
        };

        if ($members->count() > 1) {
            $found = false;

            $matchedList = $members->map(function ($m) use ($formatDisplay) {
                return $formatDisplay($m);
            })->implode(', ');

            $concat = 'พบหมายเลขบัญชี ' . $members->count() . ' บัญชี ' . $matchedList;
        } elseif ($members->count() === 1) {
            $found = true;
            $member = $members->first();

            $displayName = $formatDisplay($member);
            $concat = 'พบหมายเลขบัญชี ' . $displayName . ' รอระบบเติมอัตโนมัติ';
            $member_code = $member->code;
        } else {
            $found = false;
            $concat = 'ไม่พบสมาชิกจากข้อมูลบัญชีนี้';
        }

        $newpayment = BankPayment::firstOrNew([
            'tx_hash' => $prepared['tx_hash'],
            'account_code' => $bank->code,
        ]);

        $newpayment->account_code = $bank->code;
        $newpayment->bank = 'scb_' . $accname;
        $newpayment->bankstatus = 1;
        $newpayment->autocheck = $found ? 'W' : 'Y';
        $newpayment->remark_admin = $concat;
        $newpayment->bankname = 'SCB';
        $newpayment->bank_time = $row['fullDate'];
        $newpayment->report_id = $row['transactionID'];
        $newpayment->atranferer = $prepared['suffix'];
        $newpayment->channel = 'WEBHOOK';
        $newpayment->value = $row['amount'];
        $newpayment->tx_hash = $prepared['tx_hash'];
        $newpayment->detail = 'รับโอนจาก ' . ($row['from_bank'] ?? '')
            . ($prepared['bank_found'] ? '' : '(ไม่พบ)')
            . ' บช ' . ($row['from_acc'] ?? '')
            . ($prepared['from_name'] ? ' ' . $prepared['from_name'] : '');
        $newpayment->title = $prepared['from_name'];
        $newpayment->member_topup = $member_code;
        $newpayment->time = $row['fullDate'];
        $newpayment->create_by = 'SYSAUTO';
        $newpayment->ip_topup = '';
        $newpayment->save();

        $balance = floatval($row['lastBalance'] ?? $bank->balance ?? 0);
        $checkTime = (string)($row['fullDate'] ?? $datenow);
        $apiRefresh = 'ยอดฝากล่าสุด ' . $checkTime;

        $syncBalanceToSiblingAccounts($repo, $bank, $balance, $checkTime, $apiRefresh);

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Success.',
        ], 200);
    }

    public function gsb($mobile, Request $request)
    {
        $datenow = now()->toDateTimeString();

        $this->appendWebhookLog('gsb', $mobile, $request->all(), $datenow);

        $row = $request->all();

        $repo = app('Gametech\Payment\Repositories\BankAccountRepository');

        $bank = $repo->getAccountOne('gsb', $mobile);
        if (!$bank) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => 'ไม่พบบัญชีธนาคาร GSB สำหรับ mobile นี้',
            ], 200);
        }

        if ($bank->webhook === 'N') {
            $bank->api_refresh = 'ใช้ดึงจาก API อยู่';
            $bank->checktime = $datenow;
            $bank->save();

            return response()->json([
                'success' => false,
                'code' => 403,
                'message' => 'Webhook ถูกปิดใช้งานสำหรับบัญชีนี้',
            ], 200);
        }

        $event = $row['event'] ?? 'deposit';

        $syncBalanceToSiblingAccounts = static function ($repo, $bank, float $balance, string $checkTime, string $apiRefresh): void {
            $repo->query()
                ->where('banks', $bank->banks)
                ->where('acc_no', $bank->acc_no)
                ->whereIn('bank_type', [1, 2])
                ->update([
                    'balance' => $balance,
                    'checktime' => $checkTime,
                    'api_refresh' => $apiRefresh,
                    'date_update' => now(),
                ]);
        };

        if ($event === 'balance') {
            $balance = floatval($row['lastBalance'] ?? $bank->balance ?? 0);
            $checkTime = (string)($row['fullDate'] ?? $datenow);
            $apiRefresh = 'อัปเดตยอดคงเหลือล่าสุด ' . $checkTime;

            $syncBalanceToSiblingAccounts($repo, $bank, $balance, $checkTime, $apiRefresh);

            return response()->json([
                'success' => true,
                'code' => 200,
                'message' => 'Success.',
            ], 200);
        }

        if (!isset($row['fullDate'], $row['transactionID'], $row['amount'])) {
            return response()->json([
                'success' => false,
                'code' => 422,
                'message' => 'Payload ไม่ครบถ้วน',
            ], 200);
        }

        $diff = core()->DateDiffMin($row['fullDate']);
        if ($diff < 0 || $diff > 10) {
            return response()->json([
                'success' => false,
                'code' => 422,
                'message' => 'รายการส่งมาช้าเกิน 10 นาที หรือเวลาในอนาคต',
            ], 200);
        }

        $accname = str_replace('-', '', (string)$bank->acc_no);

        $txHash = md5(
            (string)($row['lastBalance'] ?? '')
            . (string)($row['amount'] ?? '')
            . (string)($row['from_acc'] ?? '')
            . (string)($row['fullDate'] ?? '')
        );

        $frombank = rtrim((string)($row['from_bank'] ?? ''), 'A');
        if ($frombank === 'KBNK') {
            $frombank = 'KBANK';
        }
        if ($frombank === 'TMBA') {
            $frombank = 'TTB';
        }

        $bank_code = $this->Banks($frombank);
        $memberBankCodes = $this->resolveMemberBankCodes($frombank, $bank_code);

        $prefixRaw = isset($row['from_acc_first']) ? preg_replace('/\D/', '', (string)$row['from_acc_first']) : '';
        $suffixRaw = isset($row['from_acc']) ? preg_replace('/\D/', '', (string)$row['from_acc']) : '';

        $found = false;
        $concat = 'ไม่พบหมายเลขบัญชี';
        $member_code = 0;

        if (!empty($memberBankCodes)) {
            $column = 'acc_no';

            $prefixCandidates = [];
            if ($frombank === 'BAACA') {
                if ($prefixRaw !== '') {
                    $prefixCandidates[] = '01' . $prefixRaw;
                    $prefixCandidates[] = '02' . $prefixRaw;
                }
            } else {
                if ($prefixRaw !== '') {
                    $prefixCandidates[] = $prefixRaw;
                }
            }

            $query = Member::query()->whereIn('bank_code', $memberBankCodes);

            if (!empty($prefixCandidates)) {
                $query->where(function ($qq) use ($prefixCandidates, $column) {
                    foreach ($prefixCandidates as $pf) {
                        $qq->orWhereRaw("LEFT($column, ?) = ?", [strlen($pf), $pf]);
                    }
                });
            }

            if ($suffixRaw !== '') {
                $query->whereRaw("RIGHT($column, ?) = ?", [strlen($suffixRaw), $suffixRaw]);
            }

            $users = $query->pluck('code', 'user_name');

            if ($users->count() > 1) {
                $found = false;
                $concat = 'พบหมายเลขบัญชี ' . $users->count() . ' บัญชี ' . $users->map(fn ($c, $n) => "$n")->implode(', ');
            } elseif ($users->count() === 1) {
                $found = true;
                $name = $users->map(fn ($c, $n) => "$n")->first();
                $code = $users->map(fn ($c, $n) => "$c")->first();
                $concat = 'พบหมายเลขบัญชี ' . $name . ' รอระบบเติมอัตโนมัติ';
                $member_code = (int)$code;
            } else {
                $found = false;
                $concat = 'ไม่พบสมาชิกจากข้อมูลบัญชีนี้';
            }
        }

        $newpayment = BankPayment::firstOrNew([
            'tx_hash' => $txHash,
            'account_code' => $bank->code,
        ]);

        $newpayment->account_code = $bank->code;
        $newpayment->bank = 'gsb_' . $accname;
        $newpayment->bankstatus = 1;
        $newpayment->autocheck = $found ? 'W' : 'Y';
        $newpayment->remark_admin = $concat;
        $newpayment->bankname = 'GSB';
        $newpayment->bank_time = $row['fullDate'];
        $newpayment->report_id = $row['transactionID'];
        $newpayment->atranferer = '';
        $newpayment->channel = 'WEBHOOK';
        $newpayment->value = $row['amount'];
        $newpayment->tx_hash = $txHash;
        $newpayment->detail = 'รับโอนจาก ' . ($row['from_bank'] ?? '')
            . ' บช ' . (($row['from_acc_first'] ?? '') !== '' ? ($row['from_acc_first'] . 'XXX') : '')
            . ($row['from_acc'] ?? '');
        $newpayment->title = '';
        $newpayment->member_topup = $member_code;
        $newpayment->time = $row['fullDate'];
        $newpayment->create_by = 'SYSAUTO';
        $newpayment->ip_topup = '';
        $newpayment->save();

        $balance = floatval($row['lastBalance'] ?? $bank->balance ?? 0);
        $checkTime = (string)($row['fullDate'] ?? $datenow);
        $apiRefresh = 'ยอดฝากล่าสุด ' . $checkTime;

        $syncBalanceToSiblingAccounts($repo, $bank, $balance, $checkTime, $apiRefresh);

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Success.',
        ], 200);
    }

    private function appendWebhookLog(string $channel, string $mobile, array $payload, string $datenow): void
    {
        $dir = storage_path('logs/' . $channel);

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $path = $dir . '/webhooks_' . $mobile . '_' . now()->format('Y_m_d') . '.log';

        file_put_contents($path, $datenow . PHP_EOL, FILE_APPEND);
        file_put_contents($path, print_r($payload, true) . PHP_EOL, FILE_APPEND);
    }

    private function resolveMemberBankCodes(string $bankcode, $primaryCode): array
    {
        if ($primaryCode === false || $primaryCode === null || $primaryCode === '') {
            return [];
        }

        $normalized = strtoupper(trim($bankcode));

        $codes = [(string)$primaryCode];

        if (in_array($normalized, ['TTB', 'TMB', 'TMBA'], true) || in_array((string)$primaryCode, ['19', '10', '15'], true)) {
            $codes[] = '19';
            $codes[] = '10';
            $codes[] = '15';
        }

        return array_values(array_unique($codes));
    }

    private function cleanInvisibleAndSpaces(string $s): string
    {
        $s = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{200E}\x{200F}\x{2060}\x{00A0}\x{202F}\x{FEFF}]/u', '', $s);
        $s = preg_replace('/\s+/u', ' ', $s);

        return trim($s);
    }

    public function splitNameUniversal(string $fullName)
    {
        $fullName = $this->cleanInvisibleAndSpaces($fullName);
        $original = $fullName;

        $prefixes = [
            'นาย', 'นางสาว', 'นาง', 'น.ส', 'น.', 'น', 'ดร', 'ศ', 'ผศ', 'รศ', 'ด.ญ', 'ด.ช', 'เด็กชาย', 'เด็กหญิง', 'สาว',
            'mr', 'mrs', 'ms', 'miss', 'dr', 'prof', 'sir', 'madam', 'mister',
        ];

        $pattern = '/^\s*(' . implode('|', array_map('preg_quote', $prefixes)) . ')\.?\s*/iu';

        $original = preg_replace($pattern, '', $original);

        return $this->cleanInvisibleAndSpaces($original);
    }

    public function Banks($bankcode)
    {
        switch ($bankcode) {
            case 'กรุงเทพ':
                return '1';
            case 'กสิกรไทย':
                return '2';
            case 'กรุงไทย':
                return '3';
            case 'ไทยพาณิชย์':
                return '4';
            case 'GHB':
            case 'GHBANK':
            case 'อาคารสงเคราะห์':
                return '5';

            case 'เกียรตินาคิน':
            case 'เกียรตินาคินภัทร':
                return '6';
            case 'CIMB':
            case 'ซีไอเอ็มบี':
                return '7';
            case 'IBNK':
            case 'อิสลาม':
                return '8';
            case 'ทิสโก้':
                return '9';
            case 'กรุงศรี':
            case 'กรุงศรีอยุธยา':
                return '11';
            case 'UOB':
            case 'UOBT':
            case 'ยูโอบี':
                return '12';
            case 'LHB':
            case 'LHBANK':
            case 'แลนแอนด์เฮ้าส์':
            case 'แลนด์':
                return '13';
            case 'ออมสิน':
            case 'GSB':
                return '14';
            case 'ธกส':
            case 'ธกส.':
            case 'ธ.ก.ส.':
            case 'BAAC':
            case 'BAC':
                return '17';

            case 'ทีทีบี':
            case 'ธนชาติ':
            case 'ทหารไทย':
            case 'ทหารไทยธนชาติ':
            case 'TTB':
            case 'TMB':
            case 'TMBA':
                return '19';

            case 'BBL': return '1';
            case 'KBANK':
            case 'KBNK': return '2';
            case 'KTB': return '3';
            case 'SCB': return '4';
            case 'KKP':
            case 'KK':
            case 'KKB': return '6';
            case 'IBANK': return '8';
            case 'TISCO': return '9';
            case 'BAY': return '11';
            case 'LHBANK':
            case 'LHBNK': return '13';

            default:
                return false;
        }
    }
}
