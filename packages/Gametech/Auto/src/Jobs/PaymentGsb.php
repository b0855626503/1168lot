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

class PaymentGsb implements ShouldQueue, ShouldBeUnique
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
        return ['render', 'gsb:' . $this->id];
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
            ->getAccountOne('gsb', $mobile_number);
        if (!$bank) return 1;

        $accname = str_replace("-", "", $bank->acc_no);
        $url = 'https://me2me.biz/gsb/' . $mobile_number . '/';

        $response = rescue(function () use ($url) {
            return Http::timeout(15)->withHeaders([
                'x-api-key' => 'af96aa1c-e1f5-4c22-ab96-7f5453704aa9'
            ])->post($url);
        }, fn($e) => $e);

        if ($response->failed()) {
            $bank->api_refresh = 'เชื่อมต่อ API ไม่ได้';
            $bank->checktime = $datenow;
            $bank->save();
            return 1;
        }

        if ($response->successful()) {
            $response = $response->json();

            if(!$response['status']){
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
                $bank->api_refresh = 'ดึงรายการเดินบัญชีไม่ได้ หรือไม่มีรายการ';
                $bank->checktime = $datenow;
                $bank->save();
                return 1;
            }

            $path = storage_path('logs/gsb/transaction_' . $accname . '_' . now()->format('Y_m_d') . '.log');
            file_put_contents($path, print_r($response, true));


            // === เตรียมข้อมูลและกันซ้ำ ===
            $prepared = [];
            foreach ($lists as $list) {
                if (core()->DateDiffMin($list['fullDate']) > 10) continue;

                $list['tx_hash'] = md5($list['transactionID'].$list['amount'].$list['from_acc']);

                $frombank = rtrim($list['from_bank'] ?? '', 'A');
                if ($frombank === 'KBNK') $frombank = 'KBANK';
                if ($frombank === 'TMBA') $frombank = 'TTB';

                $bank_code = $this->Banks($frombank);
                if ($bank_code === false) continue;

                $prefix = isset($list['from_acc_first']) ? preg_replace('/\D/', '', (string)$list['from_acc_first']) : '';
                $suffix = isset($list['from_acc']) ? preg_replace('/\D/', '', (string)$list['from_acc']) : '';

                $prepared[] = [
                    'raw'        => $list,
                    'tx_hash'    => $list['tx_hash'],
                    'from_bank'  => $frombank,
                    'bank_code'  => $bank_code,
                    'prefix'     => $prefix,
                    'suffix'     => $suffix,
                ];
            }

            if (empty($prepared)) return 0;

            $hashes = array_values(array_unique(array_map(fn($it)=>$it['tx_hash'],$prepared)));
            $existing = BankPayment::query()
                ->where('account_code', $bank->code)
                ->whereIn('tx_hash',$hashes)
                ->pluck('tx_hash')
                ->all();
            $exists = array_flip($existing);

            $memberCache = [];

            foreach ($prepared as $it) {
                if (isset($exists[$it['tx_hash']])) continue;

                $found = false;
                $concat = 'ไม่พบหมายเลขบัญชี';
                $member_code = 0;

                $prefixRaw = $it['prefix'];
                $suffixRaw = $it['suffix'];
                $prefixCandidates = [];

                if ($it['from_bank'] === 'BAAC') {
                    if ($prefixRaw !== '') {
                        $prefixCandidates[] = '01'.$prefixRaw;
                        $prefixCandidates[] = '02'.$prefixRaw;
                    }
                } else {
                    if ($prefixRaw !== '') $prefixCandidates[] = $prefixRaw;
                }

                $cacheKey = $it['bank_code'].'|'.implode(',',$prefixCandidates).'|'.$suffixRaw;
                if (array_key_exists($cacheKey,$memberCache)) {
                    [$concat,$member_code] = $memberCache[$cacheKey];
                } else {
                    $column = 'acc_no';
                    $query = Member::query()->where('bank_code',$it['bank_code']);

                    if (!empty($prefixCandidates)) {
                        $query->where(function($qq) use ($prefixCandidates,$column){
                            foreach ($prefixCandidates as $pf) {
                                $qq->orWhereRaw("LEFT($column, ?) = ?",[strlen($pf),$pf]);
                            }
                        });
                    }
                    if ($suffixRaw !== '') {
                        $query->whereRaw("RIGHT($column, ?) = ?",[strlen($suffixRaw),$suffixRaw]);
                    }

                    $users = $query->pluck('code','user_name');

                    if ($users->count() > 1) {
                        $found = false;
                        $concat = 'พบหมายเลขบัญชี '.$users->count().' บัญชี '.$users->map(fn($c,$n)=>"$n")->implode(', ');
                    } elseif ($users->count() === 1) {
                        $found = true;
                        $name = $users->map(fn($c,$n)=>"$n")->first();
                        $code = $users->map(fn($c,$n)=>"$c")->first();
                        $concat = 'พบหมายเลขบัญชี '.$name.' รอระบบเติมอัตโนมัติ';
                        $member_code = $code;
                    }

                    $memberCache[$cacheKey] = [$concat,$member_code];
                }

                $row = $it['raw'];

                $newpayment = BankPayment::firstOrNew([
                    'tx_hash'=>$it['tx_hash'],
                    'account_code'=>$bank->code
                ]);
                $newpayment->account_code = $bank->code;
                $newpayment->bank = 'gsb_'.$accname;
                $newpayment->bankstatus = 1;
                $newpayment->autocheck = $found ? 'W' : 'Y';
                $newpayment->remark_admin = $concat;
                $newpayment->bankname = 'GSB';
                $newpayment->bank_time = $row['fullDate'];
                $newpayment->report_id = $row['transactionID'];
                $newpayment->atranferer = '';
                $newpayment->channel = 'API';
                $newpayment->value = $row['amount'];
                $newpayment->tx_hash = $it['tx_hash'];
                $newpayment->detail = 'รับโอนจาก '.$row['from_bank'].' บช '.($row['from_acc_first']??'').'XXX'.($row['from_acc']??'');
                $newpayment->title = '';
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

    public function Banks($bankcode)
    {
        switch ($bankcode) {
            case 'BBL': return '1';
            case 'KBANK': return '2';
            case 'KTB': return '3';
            case 'SCB': return '4';
            case 'GHB': case 'GHBANK': case 'GHBNK': return '5';
            case 'KKP': case 'KK': case 'KKB': return '6';
            case 'CIMB': return '7';
            case 'IBNK': case 'IBANK': return '8';
            case 'TISCO': return '9';
            case 'BAY': return '11';
            case 'UOB': case 'UOBT': return '12';
            case 'LHB': case 'LHBANK': case 'LHBNK': return '13';
            case 'GSB': return '14';
            case 'BAC': case 'BAAC': return '17';
            case 'TTB': case 'TMB': return '19';
            default: return false;
        }
    }

    public function failed(Throwable $exception)
    {
        report($exception);
    }
}
