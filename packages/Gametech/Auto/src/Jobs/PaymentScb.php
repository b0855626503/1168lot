<?php

namespace Gametech\Auto\Jobs;

use Gametech\Member\Models\Member;
use Gametech\Payment\Models\BankPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

class PaymentScb implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $failOnTimeout = true;

    public $uniqueFor = 60;

    public $timeout = 40;

    public $tries = 0;

    public $maxExceptions = 3;

    public $retryAfter = 0;

    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function tags()
    {
        return ['render', 'scb:'.$this->id];
    }

    public function uniqueId()
    {
        return $this->id;
    }

    public function handle()
    {
        $datenow = now()->toDateTimeString();

        $mobile_number = $this->id;
        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')
            ->getAccountOne('scb', $mobile_number);
        if (! $bank) {
            return 1;
        }

        if ($bank->webhook == 'Y') {
            $bank->api_refresh = 'ใช้ดึงจาก SMS อยู่จ้า';
            $bank->checktime = $datenow;
            $bank->save();

            return 1;
        }

        $accname = str_replace('-', '', $bank->acc_no);
        $url = 'https://me2me.biz/scb/'.$mobile_number.'/';

        $response = rescue(function () use ($url) {
            return Http::timeout(15)->withHeaders([
                'x-api-key' => 'af96aa1c-e1f5-4c22-ab96-7f5453704aa9',
            ])->post($url);
        }, fn ($e) => $e);

        if ($response->failed()) {
            $bank->api_refresh = 'เชื่อมต่อ API ไม่ได้';
            $bank->checktime = $datenow;
            $bank->save();

            return 1;
        }

        if ($response->successful()) {
            $response = $response->json();

            if (! $response['status']) {
                $bank->api_refresh = $response['msg'];
                $bank->checktime = $datenow;
                $bank->save();

                return 1;
            }

            $bank->balance = floatval($response['balance']);
            $bank->checktime = now()->toDateTimeString();
            $bank->save();

            $lists = $response['data'];
            if (empty($lists)) {
                $bank->api_refresh = 'API ไม่มีรายการ';
                $bank->checktime = $datenow;
                $bank->save();

                return 1;
            }

            $path = storage_path('logs/scb/transaction_'.$accname.'_'.now()->format('Y_m_d').'.log');
            file_put_contents($path, print_r($response, true));

            // === เตรียมข้อมูลและกันซ้ำ ===
            $prepared = [];
            foreach ($lists as $list) {
                if (core()->DateDiffMin($list['fullDate']) > 10) {
                    continue;
                }

                $list['tx_hash'] = md5($list['transactionID'].$list['amount'].$list['from_acc']);

                $frombank = rtrim($list['from_bank'] ?? '', 'A');

                $bank_code = $this->Banks($frombank);
                if ($bank_code === false) {
                    $bank_code = 0;
                }
                $suffix = isset($list['from_acc']) ? preg_replace('/\D/', '', (string) $list['from_acc']) : '';

                $clean = $this->splitNameUniversal($list['from_name']);

                $fromname = $clean ?? '';

                $prepared[] = [
                    'raw' => $list,
                    'tx_hash' => $list['tx_hash'],
                    'from_bank' => $frombank,
                    'bank_code' => $bank_code,
                    'bank_found' => ($bank_code === 0 ? false : true),
                    'from_name' => $fromname,
                    'suffix' => $suffix,
                ];
            }

            if (empty($prepared)) {
                return 0;
            }

            $hashes = array_values(array_unique(array_map(fn ($it) => $it['tx_hash'], $prepared)));
            $existing = BankPayment::query()
                ->where('account_code', $bank->code)
                ->whereIn('tx_hash', $hashes)
                ->pluck('tx_hash')
                ->all();
            $exists = array_flip($existing);

            $memberCache = [];

            foreach ($prepared as $it) {
                if (isset($exists[$it['tx_hash']])) {
                    continue;
                }

                $found = false;
                $concat = 'ไม่พบหมายเลขบัญชี';
                $member_code = 0;

                $cacheKey = $it['bank_code'].'|'.$it['suffix'];
                if (array_key_exists($cacheKey, $memberCache)) {
                    [$concat, $member_code] = $memberCache[$cacheKey];
                } else {
                    $column = 'acc_no';  // ใช้ตรง ๆ เพราะเก็บเป็นตัวเลขล้วนอยู่แล้ว

                    // ====== 1) base query: กรองด้วย bank_code + suffix ก่อน ======
                    $baseQuery = Member::query()
                        ->where('bank_code', $it['bank_code']);

                    $suffix = '';
                    $len = 0;

                    // suffix เลขท้ายบัญชี (ถ้ามี)
                    if ($it['suffix'] !== '') {
                        // เผื่อ API ส่งตัวแปลกมา เช่น เว้นวรรค / ขีด
                        $suffix = preg_replace('/\D+/', '', $it['suffix']);
                        $len = strlen($suffix);

                        if ($len > 0) {
                            $baseQuery->whereRaw("RIGHT({$column}, ?) = ?", [$len, $suffix]);
                        }
                    }

                    // ดึงผลลัพธ์รอบแรก (ยังไม่ใช้ชื่อช่วยกรอง) — ดึง field ที่ต้องใช้ทั้งหมด
                    /** @var \Illuminate\Support\Collection|\App\Models\Member[] $membersBase */
                    $membersBase = $baseQuery->get(['user_name', 'firstname', 'code']);

                    $members = $membersBase;
                    $usedNameFilter = false;

                    // ====== 2) ถ้าเจอมากกว่า 1 คน และมี from_name → เอาชื่อไปช่วยกรองต่อ ======
                    if ($membersBase->count() > 1 && ! empty($it['from_name'])) {

                        $name = trim($it['from_name']);
                        $escaped = addcslashes($name, '%_');  // กัน wildcard แตก

                        $nameQuery = Member::query()
                            ->where('bank_code', $it['bank_code']);

                        if ($len > 0) {
                            $nameQuery->whereRaw("RIGHT({$column}, ?) = ?", [$len, $suffix]);
                        }

                        $nameQuery->where(function ($q) use ($escaped) {
                            $q->where('name', 'LIKE', "%{$escaped}%")
                                ->orWhere('name_addon', 'LIKE', "%{$escaped}%");
                        });

                        $membersByName = $nameQuery->get(['user_name', 'firstname', 'code']);

                        // ถ้าใช้ชื่อแล้วเจอแมตช์ → ใช้ชุดนี้แทน
                        if ($membersByName->isNotEmpty()) {
                            $members = $membersByName;
                            $usedNameFilter = true;
                        }
                        // ถ้าใช้ชื่อแล้วไม่เจอเลย → fallback เป็น $membersBase (ตาม requirement เดิม)
                    }

                    // helper เล็ก ๆ สำหรับ format "user_name (firstname)"
                    $formatDisplay = function ($m) {
                        $user = $m->user_name;
                        $first = $m->firstname ?? '';

                        return $first !== ''
                            ? "{$user} ({$first})"
                            : $user;
                    };

                    // ====== 3) ตีความผลลัพธ์จาก $members ======
                    if ($members->count() > 1) {
                        $found = false;

                        // ตัวอย่าง: "0855626503 (วรเดช), 0912345678 (สมชาย)"
                        $list = $members->map(function ($m) use ($formatDisplay) {
                            return $formatDisplay($m);
                        })->implode(', ');

                        if ($usedNameFilter) {
                            $concat = 'พบหมายเลขบัญชีจากชื่อ '.$members->count().' บัญชี '.$list;
                        } else {
                            $concat = 'พบหมายเลขบัญชี '.$members->count().' บัญชี '.$list;
                        }

                    } elseif ($members->count() === 1) {
                        $found = true;
                        $member = $members->first(); // Member เดียว

                        $displayName = $formatDisplay($member); // "0855626503 (วรเดช)"

                        $concat = 'พบหมายเลขบัญชี '.$displayName.' รอระบบเติมอัตโนมัติ';
                        $member_code = $member->code;

                    } else {
                        // ไม่เจอเลยจาก bank_code + suffix (+ ชื่อ ถ้าใช้)
                        $found = false;
                        $concat = 'ไม่พบสมาชิกจากข้อมูลบัญชีนี้';
                    }

                    $memberCache[$cacheKey] = [$concat, $member_code];
                }

                $row = $it['raw'];

                $newpayment = BankPayment::firstOrNew([
                    'tx_hash' => $it['tx_hash'],
                    'account_code' => $bank->code,
                ]);
                $newpayment->account_code = $bank->code;
                $newpayment->bank = 'scb_'.$accname;
                $newpayment->bankstatus = 1;
                $newpayment->autocheck = $found ? 'W' : 'Y';
                $newpayment->remark_admin = $concat;
                $newpayment->bankname = 'SCB';
                $newpayment->bank_time = $row['fullDate'];
                $newpayment->report_id = $row['transactionID'];
                $newpayment->atranferer = $it['suffix'];
                $newpayment->channel = 'API';
                $newpayment->value = $row['amount'];
                $newpayment->tx_hash = $it['tx_hash'];
                $newpayment->detail = 'รับโอนจาก '.$row['from_bank'].($it['bank_found'] ? "" : "(ไม่พบ)").' บช '.($row['from_acc'] ?? '').($it['from_name'] ? ' '.$it['from_name'] : '');
                $newpayment->title = $it['from_name'];
                $newpayment->member_topup = $member_code;
                $newpayment->time = $row['fullDate'];
                $newpayment->create_by = 'SYSAUTO';
                $newpayment->ip_topup = '';
                $newpayment->save();
            }

            $bank->api_refresh = 'สำเร็จ';
            $bank->checktime = $datenow;
            $bank->save();
        }

        return 0;
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

    public function splitNameUniversal(string $fullName)
    {
        // ล้าง invisible chars + normalize space
        $fullName = $this->cleanInvisibleAndSpaces($fullName);

        // เก็บต้นฉบับหลัง clean
        $original = $fullName;

        // prefix ไทย/อังกฤษ (ไม่ต้องใส่จุดซ้ำ)
        $prefixes = [
            // ไทย
            'นาย', 'นางสาว', 'นาง', 'น.ส', 'น.', 'น',
            'ดร', 'ศ', 'ผศ', 'รศ',
            'ด.ญ', 'ด.ช', 'เด็กชาย', 'เด็กหญิง', 'สาว',

            // English
            'mr', 'mrs', 'ms', 'miss', 'dr', 'prof',
            'sir', 'madam', 'mister',
        ];

        /*
         |-------------------------------------------
         | สร้าง regex:
         | ^\s*(mr|mrs|dr)\.?\s*
         |-------------------------------------------
         */
        $pattern = '/^\s*('.implode('|', array_map('preg_quote', $prefixes)).')\.?\s*/iu';

        // ถ้าเจอ prefix → ตัดทิ้ง
        $original = preg_replace($pattern, '', $original);

        // clean ซ้ำอีกรอบก่อนส่งกลับ
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
                return '13';
            case 'ออมสิน':
                return '14';
            case 'ธกส':
            case 'ธกส.':
            case 'ธ.ก.ส.':
                return '17';

            case 'ทีทีบี':
            case 'ธนชาติ':
            case 'ทหารไทย':
            case 'ทหารไทยธนชาติ':
                return '19';
            default:
                return false;
        }
    }

    public function failed(Throwable $exception)
    {
        report($exception);
    }
}
