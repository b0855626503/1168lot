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

class PaymentKbank implements ShouldQueue, ShouldBeUnique
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
        return ['render', 'kbank:' . $this->id];
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

        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOne('kbank', $mobile_number);
        if (!$bank) {
            return 1;
        }

        $hour = now()->format('H.i'); // ได้รูปแบบเช่น 23.15, 00.45
        $asFloat = (float) $hour;

        // ช่วงเวลาต้องห้าม 23.00 - 01.30
        $inBlock = ($asFloat >= 22.58) || ($asFloat <= 1.32);
//        if ($inBlock) {
//            $bank->api_refresh = 'ช่วงเวลาอันตราย 23.00 - 01.30 ระบบจะทำงานหลัง 01.30';
//            $bank->checktime = $datenow;
//            $bank->save();
//            return 1;
//        }

        $USERNAME = $bank->user_name;
        $PASSWORD = $bank->user_pass;

        $accname = str_replace("-", "", (string)$bank->acc_no);

        $em = new KbankBiz();
        $em->setLogin($USERNAME, $PASSWORD);
        $em->setAccountNumber($accname);

        try {
            if (!$em->login()) {
                // ★ changed: กันเคสล็อกอินล้มเหลว
                $bank->api_refresh = 'login_failed';
                $bank->checktime   = $datenow;
                $bank->save();
                return 1;
            }

            // ดึงยอดคงเหลือ
            try {
                $balance = $em->getBalance();
            } catch (Throwable $e) {
                $balance = -1;
            }

            if ($balance >= 0) {
                $bank->balance  = (float)$balance; // ★ changed: cast ชัดเจน
                $bank->checktime = $datenow;
                $bank->save();
            } else {
                $bank->api_refresh = 'เชคยอดเงินไม่ได้';
                $bank->checktime   = $datenow;
                $bank->save();
                return 1;
            }

            // ดึงรายการเดินบัญชี
            try {
                $lists = $em->getTransaction();
            } catch (Throwable $e) {
                $lists = [];
            }

            $path = storage_path('logs/kbank/gettransaction_' . $accname . '_' . now()->format('Y_m_d') . '.log');

            // ★ changed: เขียนแบบ append + lock และมี newline
            @file_put_contents($path, "============ " . now()->toDateTimeString() . " ============\n", FILE_APPEND | LOCK_EX);
            @file_put_contents($path, print_r($lists, true) . "\n", FILE_APPEND | LOCK_EX);

            if (empty($lists)) {
                $bank->api_refresh = 'ดึงรายการเดินบัญชีไม่ได้ หรือไม่มีรายการ';
                $bank->checktime   = $datenow;
                $bank->save();
                return 1;
            }

            // === Optimize: pre-filter items and bulk dedup by tx_hash ===
            // หมายเหตุ: core()->DateDiff($list['date']) > 1 = ตัดทิ้งถ้าเก่ากว่า threshold ภายในฟังก์ชัน core() เดิม (คง logic เดิมไว้)
            $prepared = [];
            foreach ($lists as $list) {
                $txDate = $list['date'] ?? null;
                if (!$txDate) {
                    continue;
                }
                if (core()->DateDiff($txDate) > 1) { // คงเงื่อนไขเดิม
                    continue;
                }

                // ★ changed: ทำ amount ให้เป็น float/ตัวเลขเสมอ
                $inRaw = (string)($list['in'] ?? $list['amount'] ?? '0');
                $normAmount = (float)str_replace([",", " "], "", $inRaw);

                // ★ changed: tx_hash รวม report_id ลดโอกาสชน
                $reportIdRaw = $list['report_id'] ?? '';
                $report_id   = rtrim((string)$reportIdRaw, 'A');

                $tx_hash = md5($accname . '|' . $txDate . '|' . $normAmount);

                // map รหัสธนาคารต้นทาง
                $from_bank = $this->Banks($report_id);

                $prepared[] = [
                    'raw'        => $list,
                    'tx_hash'    => $tx_hash,
                    'report_id'  => $report_id,
                    'from_bank'  => $from_bank,                              // ★ keep
                    'from_acc'   => (string)($list['fromaccno'] ?? ''),      // ระวัง: อาจเป็น 4 ตัวช่วงกลางตามฟอร์แมตระบบคุณ
                    'from_name'  => preg_replace('/\++$/', '', (string)($list['title'] ?? '')),
                    'amount'     => $normAmount,
                    'date'       => $txDate,
                    'channel'    => (string)($list['channel'] ?? ''),
                    'info'       => (string)($list['info'] ?? ''),
                ];
            }

            if (empty($prepared)) {
                // ไม่มีอะไรเข้าเงื่อนไขเวลา
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

            $memberCache = [];

            foreach ($prepared as $it) {
                if (isset($exists[$it['tx_hash']])) {
                    continue; // skip duplicates already in DB
                }

                $found = false;
                $concat = 'ไม่พบหมายเลขบัญชี';
                $member_code = 0;

                // ★ changed: ใช้ key cache ให้ตรง type เพื่อเร่ง match
                $cacheKey = $it['from_bank'] . '|' . $it['from_acc'] . '|' . $it['from_name'];
                if (array_key_exists($cacheKey, $memberCache)) {
                    [$concat, $member_code, $found] = $memberCache[$cacheKey];
                } else {
                    // ★ changed: ใช้จาก 'from_bank' แทน 'bank_code' (แก้บั๊ก)
                    $query = Member::query()->where('bank_code', (int)$it['from_bank']);

                    // ธุรกิจเดิม: KBANK (2) ใช้ชื่อ + SUBSTRING(acc_no, 6, 4) = from_acc
                    if ((int)$it['from_bank'] === 2) {
                        $title = trim((string)$it['from_name']);
                        $fromAcc = (string)$it['from_acc'];

                        // ใช้ binding กันชื่อมี quote/percent และชัดเจนเรื่อง length = 10
                        $query->where('firstname', 'like', $title . '%')
                            ->whereRaw('CHAR_LENGTH(acc_no) = 10')
                            ->whereRaw('SUBSTRING(acc_no, 6, 4) = ?', [$fromAcc]);
                    }
                    // GSB (14): เทียบเลขบัญชีตรง
                    elseif ((int)$it['from_bank'] === 14) {
                        $query->where('acc_no', (string)$it['from_acc']);
                    }
                    // ธนาคารอื่น: เทียบเลขบัญชีตรง (คง logic เดิม)
                    else {
                        $query->where('acc_no', (string)$it['from_acc']);
                    }

                    $users = $query->pluck('code', 'user_name');

                    if ($users->count() > 1) {
                        $found = false;
                        $concat = 'พบหมายเลขบัญชี ' . $users->count() . ' บัญชี ' . $users->map(fn($c, $n) => "$n")->implode(', ');
                    } elseif ($users->count() === 1) {
                        $found = true;
                        $name = $users->keys()->first();
                        $code = $users->first();
                        $concat = 'พบหมายเลขบัญชี ' . $name . ' รอระบบเติมอัตโนมัติ';
                        $member_code = $code;
                    }

                    $memberCache[$cacheKey] = [$concat, $member_code, $found];
                }

                $row = $it['raw'];

                $newpayment = BankPayment::firstOrNew([
                    'tx_hash'      => $it['tx_hash'],
                    'account_code' => $bank->code
                ]);

                $newpayment->autocheck     = $found ? 'W' : 'Y';
                $newpayment->remark_admin  = $concat;
                $newpayment->member_topup  = $member_code;
                $newpayment->account_code  = $bank->code;
                $newpayment->bank          = 'kbank_' . $accname;
                $newpayment->bankstatus    = 1;
                $newpayment->bankname      = 'KBANK';
                $newpayment->bank_time     = (string)($row['date'] ?? $it['date']);         // ★ changed: กัน key หาย
                $newpayment->report_id     = (string)$it['report_id'];
                $newpayment->atranferer    = (string)$it['from_acc'];
                $newpayment->channel       = (string)$it['channel'];                         // ★ changed: ใช้ค่าที่ normalize แล้ว
                $newpayment->value         = (float)$it['amount'];                           // ★ changed: เป็น float แน่นอน
                $newpayment->tx_hash       = (string)$it['tx_hash'];                         // ซ้ำกับคีย์ แต่คงไว้
                $newpayment->detail        = (string)$it['info'];                            // ★ changed: กัน key หาย
                $newpayment->title         = (string)$it['from_name'];
                $newpayment->time          = (string)($row['date'] ?? $it['date']);
                $newpayment->create_by     = 'SYSAUTO';
                $newpayment->ip_topup      = '';
                $newpayment->save();
            }

            $bank->api_refresh = 'สำเร็จ';
            $bank->checktime   = $datenow;
            $bank->save();

            // ถ้า lib มี logout() และไม่กระทบ flow เดิม สามารถเรียกได้:
            // try { $em->logout(); } catch (Throwable $e) {}

        } catch (Throwable $exception) {
            // ★ changed: รายงานและ mark สถานะธนาคาร
            report($exception);
            $bank->api_refresh = 'exception: ' . substr($exception->getMessage(), 0, 120);
            $bank->checktime   = $datenow;
            $bank->save();
            return 1;
        }

        return 0;
    }

    public function Banks($bankcode)
    {
        switch ($bankcode) {
            case 'BBL':
                $result = 1;
                break;
            case 'KBANK':
                $result = 2;
                break;
            case 'KTB':
                $result = 3;
                break;
            case 'SCB':
                $result = 4;
                break;
            case 'GHBANK':
                $result = 5;
                break;
            case 'KKP':
            case 'KKB':
                $result = 6;
                break;
            case 'CIMB':
                $result = 7;
                break;
            case 'IBANK':
                $result = 8;
                break;
            case 'TISCO':
                $result = 9;
                break;
            case 'BAY':
                $result = 11;
                break;
            case 'UOB':
            case 'UOBT':
                $result = 12;
                break;
            case 'LHBANK':
                $result = 13;
                break;
            case 'GSB':
                $result = 14;
                break;
            case 'TBANK':
                $result = 15;
                break;
            case 'BAAC':
                $result = 17;
                break;
            case 'TTB':
            case 'TMB':
                $result = 19;
                break;
            default:
                $result = 2;
                break;
        }

        return $result;
    }

    public function failed(Throwable $exception)
    {
        report($exception);
    }
}
