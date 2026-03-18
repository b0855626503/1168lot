<?php

namespace Gametech\Auto\Jobs;


use App\Libraries\Kbank;
use App\Libraries\KbankBiz;
use App\Libraries\KbankBizNew;
use App\Libraries\KbankOut;
use App\Libraries\simple_html_dom;
use Gametech\Member\Models\Member;
use Gametech\Payment\Models\BankPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;


class PaymentKtb implements ShouldQueue, ShouldBeUnique
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
        return ['render', 'ktb:' . $this->id];
    }

    public function uniqueId()
    {
        return $this->id;
    }

    public function handle()
    {
        $datenow = now()->toDateTimeString();
        $header = [];
        $response = [];
        $mobile_number = $this->id;
        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOne('ktb', $mobile_number);
        if (!$bank) {
            return 1;
        }

        $accname = str_replace("-", "", $bank->acc_no);

        $url = 'https://me2me.biz/ktb-persernal/' . $mobile_number . '/';

        $response = rescue(function () use ($url) {
            return Http::timeout(15)->withHeaders([
                'x-api-key' => 'af96aa1c-e1f5-4c22-ab96-7f5453704aa9'
            ])->post($url);

        }, function ($e) {
            return $e;
        });

        if ($response->failed()) {
            $bank->api_refresh = 'เชื่อมต่อ API ไม่ได้';
            $bank->checktime = $datenow;
            $bank->save();
            return 1;
        }

        if ($response->successful()) {

            if(!$response['status']){
                $bank->api_refresh = $response['msg'];
                $bank->checktime = $datenow;
                $bank->save();
                return 1;
            }


            $response = $response->json();
            $path = storage_path('logs/ktb/transaction_' . $accname . '_' . now()->format('Y_m_d') . '.log');
            file_put_contents($path, print_r($response, true));

            $lists = $response['data'];

            if (empty($lists)) {
                $bank->api_refresh = 'ดึงรายการเดินบัญชีไม่ได้ หรือไม่มีรายการ';
                $bank->checktime = $datenow;
                $bank->save();
                return 1;
            }

            // === Optimize: pre-filter items and bulk dedup by tx_hash ===
            // 1) Keep only items within 10 minutes and prepare normalized fields
            $prepared = [];
            foreach ($lists as $list) {
                if (core()->DateDiffMin($list['fullDate']) > 10) { continue; }

                // stable tx key
                $list['tx_hash'] = md5($list['transactionID'].$list['amount'].$list['from_acc']);

                // normalize bank code suffix 'A' and special map
                $frombank = rtrim($list['from_bank'] ?? '', 'A');
                if ($frombank === 'KBNK') { $frombank = 'KBANK'; }
                if ($frombank === 'TMBA') { $frombank = 'TTB'; } // TMBA -> TTB
                if($frombank === 'KTB'){

                    $clean = $this->splitNameUniversal($list['from_name']);

                    $fromname = $clean ?? '';
                }else{
                    $fromname = '';
                }


                $bank_code = $this->Banks($frombank);
                if ($bank_code === false) { continue; }

                // sanitize edges; allow variable length (2/4 for prefix etc.)
//                $prefix = isset($list['from_acc_first']) ? preg_replace('/\D/', '', (string) $list['from_acc_first']) : '';
                $suffix = isset($list['from_acc']) ? preg_replace('/\D/', '', (string) $list['from_acc']) : '';

                $prepared[] = [
                    'raw'        => $list,
                    'tx_hash'    => $list['tx_hash'],
                    'from_bank'  => $frombank,
                    'bank_code'  => $bank_code,
                    'from_name'  => $fromname,
                    'suffix'     => $suffix,
                ];
            }

            if (empty($prepared)) {
                return 0;
            }

            // 2) Fetch existing tx_hash in ONE query to skip already-inserted
            $hashes = array_values(array_unique(array_map(fn($it) => $it['tx_hash'], $prepared)));
            $existing = BankPayment::query()
                ->where('account_code', $bank->code)
                ->whereIn('tx_hash', $hashes)
                ->pluck('tx_hash')
                ->all();
            $exists = array_flip($existing);

            // 3) Cache for Member lookup by (bank_code|prefix|suffix) to avoid duplicate queries
            $memberCache = [];

            foreach ($prepared as $it) {
                if (isset($exists[$it['tx_hash']])) {
                    continue; // skip duplicates already in DB
                }
                $found = false;
                $concat = 'ไม่พบหมายเลขบัญชี';
                $member_code = 0;

                // resolve member once per unique (bank_code,prefix,suffix)
                $cacheKey = $it['bank_code'] . '|'  . $it['suffix'];
                if (array_key_exists($cacheKey, $memberCache)) {
                    [$concat, $member_code] = $memberCache[$cacheKey];
                } else {
                    $column = 'acc_no';  // ใช้ตรงๆ เพราะเก็บเป็นตัวเลขล้วนอยู่แล้ว
                    $query = Member::query()->where('bank_code', $it['bank_code']);

                    if ($it['from_name'] !== '') {
                        $query->where("name", $it['from_name']);
                    }
                    if ($it['suffix'] !== '') {
                        $query->whereRaw("RIGHT($column, ?) = ?", [strlen($it['suffix']), $it['suffix']]);
                    }
                    $users = $query->pluck('code', 'user_name');

                    if ($users->count() > 1) {
                        $found = false;
                        $concat = 'พบหมายเลขบัญชี '.$users->count().' บัญชี '.$users->map(fn($code, $name) => "{$name}")->implode(', ');
                    } elseif ($users->count() === 1) {
                        $found = true;
                        $name = $users->map(fn($code, $name) => "{$name}")->first();
                        $code = $users->map(fn($code, $name) => "{$code}")->first();
                        $concat = 'พบหมายเลขบัญชี '.$name. ' รอระบบเติมอัตโนมัติ';
                        $member_code = $code;
                    }

                    $memberCache[$cacheKey] = [$concat, $member_code];
                }

                $row = $it['raw'];

                $newpayment = BankPayment::firstOrNew([
                    'tx_hash'      => $it['tx_hash'],
                    'account_code' => $bank->code
                ]);
                $newpayment->account_code = $bank->code;
                $newpayment->bank = 'ktb_' . $accname;
                $newpayment->bankstatus = 1;
                if($found){
                    $newpayment->autocheck = 'W';
                }else{
                    $newpayment->autocheck = 'Y';

                }
                $newpayment->remark_admin = $concat;
                $newpayment->bankname = 'KTB';
                $newpayment->bank_time = $row['fullDate'];
                $newpayment->report_id = $row['transactionID'];
                $newpayment->atranferer = '';
                $newpayment->channel = 'API';
                $newpayment->value = $row['amount'];
                $newpayment->tx_hash = $it['tx_hash'];
                $newpayment->detail = 'รับโอนจาก '.$row['from_bank'] . ' บช ลงท้ายด้วย ' . ($row['from_acc'] ?? '');
                $newpayment->title = $row['from_name'];
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
        // ล้าง ZWSP/NBSP/BOM ฯลฯ และ normalize ช่องว่าง
        $fullName = $this->cleanInvisibleAndSpaces($fullName);

        // คำนำหน้าที่พบบ่อย (เพิ่ม/แก้ได้ตามดาต้า)
        $prefixes = [
            // ไทย
            'นาย','นางสาว','นาง','น.ส.','น.','ดร.','ศ.','ผศ.','รศ.', 'ด.ญ.', 'ด.ช.', 'เด็กชาย.', 'เด็กหญิง.', 'เด็กชาย', 'เด็กหญิง','สาว',
            // อังกฤษ
            'Mr.','Mrs.','Ms.','Miss','Dr.','Prof.','Sir','Madam','MISTER','MISS','MS','MR','MRS'
        ];

        // ตัดคำนำหน้าออก (ไม่สนตัวพิมพ์ใหญ่เล็ก, รองรับ multibyte)
        foreach ($prefixes as $prefix) {
            if (mb_stripos($fullName, $prefix) === 0) {
                $fullName = trim(mb_substr($fullName, mb_strlen($prefix)));
                break;
            }
        }

        // กันกรณีคั่นด้วยหลายช่องว่าง/อักขระเว้นวรรคหลากชนิด
//        $parts = preg_split('/\s+/u', $fullName);
//
//        $firstname = $parts[0] ?? '';
//        $lastname  = count($parts) > 1 ? $parts[count($parts) - 1] : '';
//
//        // ล้างซ้ำอีกรอบให้ชัวร์ (กันเศษอักขระตกค้าง)
//        $firstname = $this->cleanInvisibleAndSpaces($firstname);
//        $lastname  = $this->cleanInvisibleAndSpaces($lastname);

        return $fullName;
    }

    public function Banks($bankcode)
    {

        switch ($bankcode) {
            case 'BBL':
                $result = '1';
                break;
            case 'KBANK':
                $result = '2';
                break;
            case 'KTB':
                $result = '3';
                break;
            case 'SCB':
                $result = '4';
                break;
            case 'GHB':
            case 'GHBANK':
            case 'GHBNK':
                $result = '5';
                break;
            case 'KKP':
            case 'KK':
            case 'KKB':
                $result = '6';
                break;

            case 'CIMB':
                $result = '7';
                break;
            case 'IBNK':
            case 'IBANK':
                $result = '8';
                break;
            case 'TISCO':
                $result = '9';
                break;

            case 'BAY':
                $result = '11';
                break;
            case 'UOB':
                $result = '12';
                break;
            case 'LHB':
            case 'LHBANK':
            case 'LHBNK':
                $result = '13';
                break;
            case 'GSB':
                $result = '14';
                break;
            case 'BAC':
            case 'BAAC':
                $result = '17';
                break;
            case 'TTB':
            case 'TMB':
                $result = '19';
                break;
            default:
                $result = false;
                break;
        }

        return $result;

    }

    public function failed(Throwable $exception)
    {
        report($exception);
    }
}
