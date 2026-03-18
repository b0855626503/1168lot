<?php

namespace Gametech\Auto\Jobs;


use Gametech\Payment\Models\BankPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;


class PaymentTrue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $timeout = 30;

    public $tries = 0;

    public $maxExceptions = 5;

    public $retryAfter = 0;

    protected $id;


    public function __construct($id)
    {
        $this->id = $id;
    }

    public function tags()
    {
        return ['render', 'tw:' . $this->id];
    }

//    public function uniqueId()
//    {
//        return $this->id;
//    }

    public function handle()
    {

        $mobile_number = $this->id;

        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('tw', $mobile_number);
        if (!$data) {
            $this->fail();
        }
        if ($data->webhook == 'N') {
//            return true;

            if ($data->local == 'Y') {
//                return true;
                if ($data->pattern == 'G') {
                    return $this->TrueLocal();
                } else {
                    return $this->TrueOther();
                }

            } else {
                return $this->TrueApi();
            }

        }


    }

    public function TrueLocal()
    {
        $lists = [];
        $response = [];
        $mobile_number = $this->id;
        $datenow = now()->toDateTimeString();
        $date = now()->format('Y_m_d');
        $success = false;

        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOne('tw', $mobile_number);
        if (!$data) {
            $this->fail();
        }

        $website = $data->website . '/tw/Transaction_' . $mobile_number . '_' . $date . '.json';


        $response = rescue(function () use ($website) {
            return Http::timeout(15)->get($website);

        }, function ($e) {
            return $e;
        });


        if ($response->successful()) {
            $response = $response->json();
            $success = true;
        }


        $path = storage_path('logs/tw/Transaction_' . $mobile_number . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($response, true));


        if ($success) {
            if (is_numeric($response['current_balance'])) {
                $data->balance = $response['current_balance'];
                $data->checktime = $datenow;
                $data->save();
            }


            if (!empty($response['activities'])) {
                $lists = $response['activities'];
            }

            if (count($lists) > 0) {

                foreach ($lists as $value) {

                    if (empty($value['transaction_reference_id'])) continue;
//                    if($value['original_action'] !== 'creditor') continue;

                    $str = $value['date_time'];
                    $arr = explode(" ", $str);
                    $dtmp = explode('/', $arr[0]);
                    $dates = '20' . $dtmp[2] . '-' . $dtmp[1] . '-' . $dtmp[0] . ' ' . $arr[1] . ':00';

                    $amount = Str::of($value['amount'])->replace('+', '');
                    $amount = Str::of($amount)->replace(',', '')->__toString();

                    $detail = Str::of($value['transaction_reference_id'])->replace('-', '')->__toString();

                    $hash = md5($data->code . $dates . $amount . $detail);

                    $diff = core()->DateDiff($dates);
                    if ($diff > 1) continue;

                    $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
                    $newpayment->account_code = $data->code;
                    $newpayment->bank = 'twl_' . $mobile_number;
                    $newpayment->bankstatus = 1;
                    $newpayment->bankname = 'TW';
                    $newpayment->report_id = $value['report_id'];
                    $newpayment->bank_time = $dates;
                    $newpayment->type = $value['type'];
                    $newpayment->title = $value['title'];
                    $newpayment->value = $amount;
                    $newpayment->tx_hash = $hash;
                    $newpayment->detail = $detail;
                    $newpayment->atranferer = $detail;
                    $newpayment->time = $dates;
                    $newpayment->create_by = 'SYSAUTO';
                    $newpayment->save();

                }
            }
            return 0;
        }
    }


    public function TrueOther()
    {
        $lists = [];
        $response = [];
        $mobile_number = $this->id;
        $datenow = now()->toDateTimeString();
        $date = now()->format('Y-m-d');
        $success = false;

        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOne('tw', $mobile_number);
        if (!$data) {
            $this->fail();
        }

        $website = $data->website . '/data/Transaction_' . $mobile_number . '_' . $date . '.json';


        $response = rescue(function () use ($website) {
            return Http::timeout(15)->get($website);

        }, function ($e) {
            return $e->response;
        },false);


        if ($response->successful()) {
            $response = $response->json();
            $success = true;
        }


        $path = storage_path('logs/tw/Transaction_' . $mobile_number . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($response, true));


        if ($success) {
            if (is_numeric($response['Profile']['data']['currentBalance'])) {
                $data->balance = $response['Profile']['data']['currentBalance'];
                $data->checktime = $datenow;
                $data->save();
            }


            if (!empty($response['Transaction'])) {
                $lists = $response['Transaction'];
            }

            if (count($lists) > 0) {

                foreach ($lists as $value) {

                    if (empty($value['transaction_reference_id'])) continue;
//                    if($value['original_action'] !== 'creditor') continue;

                    $str = $value['date_time'];
                    $arr = explode(" ", $str);
                    $dtmp = explode('/', $arr[0]);
                    $dates = '20' . $dtmp[2] . '-' . $dtmp[1] . '-' . $dtmp[0] . ' ' . $arr[1] . ':00';

                    $amount = Str::of($value['amount'])->replace('+', '');
                    $amount = Str::of($amount)->replace(',', '')->__toString();

                    $detail = Str::of($value['transaction_reference_id'])->replace('-', '')->__toString();

                    $hash = md5($data->code . $dates . $amount . $detail);

                    $diff = core()->DateDiff($dates);

                    if ($diff > 1) continue;

                    $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
                    $newpayment->account_code = $data->code;
                    $newpayment->bank = 'twl_' . $mobile_number;
                    $newpayment->bankstatus = 1;
                    $newpayment->bankname = 'TW';
                    $newpayment->report_id = $value['report_id'];
                    $newpayment->bank_time = $dates;
                    $newpayment->type = $value['type'];
                    $newpayment->title = $value['title'];
                    $newpayment->value = $amount;
                    $newpayment->tx_hash = $hash;
                    $newpayment->detail = $detail;
                    $newpayment->atranferer = $detail;
                    $newpayment->time = $dates;
                    $newpayment->create_by = 'SYSAUTO';
                    $newpayment->save();

                }
            }
            return 0;
        }
    }

    public function TrueApi()
    {
        $lists = [];
        $response = [];
        $mobile_number = $this->id;
        $datenow = now()->toDateTimeString();
        $date = now()->format('Y_m_d');

        $data = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('tw', $mobile_number);
        if (!$data) {
            $this->fail();
        }

        $url = 'https://z.z7z.work/truewallet/' . $mobile_number . '/api.php?action=getbalance';

        $response = rescue(function () use ($url) {
            return Http::timeout(15)->withHeaders([
                'access-key' => 'ca37d204-921b-4d33-a8f6-f7ff7bcc2356'
            ])->post($url);

        }, function ($e) {
            return false;
        }, true);

        if ($response === false) {
            $this->fail();
        }


        if ($response->successful()) {
            $response = $response->json();

            $path = storage_path('logs/tw/Transaction_' . $mobile_number . '_' . now()->format('Y_m_d') . '.log');
            file_put_contents($path, print_r($response, true));

            if ($response['status'] === true) {
                $data->balance = $response['balance'];
                $data->checktime = $datenow;
                $data->save();
            }


            $url = 'https://z.z7z.work/truewallet/' . $mobile_number . '/api.php?action=transaction';

            $response = rescue(function () use ($url) {
                return Http::timeout(15)->withHeaders([
                    'access-key' => 'ca37d204-921b-4d33-a8f6-f7ff7bcc2356'
                ])->post($url);

            }, function ($e) {
                return $e;
            }, true);

            if ($response->failed()) {
                $this->fail();
            }

            if ($response->successful()) {
                $lists = $response->json();
                if (count($lists) > 0) {

                    foreach ($lists as $value) {

                        if (empty($value['transaction_reference_id'])) continue;
//                        if($value['original_action'] !== 'creditor') continue;

                        $str = $value['date_time'];
                        $arr = explode(" ", $str);
                        $dtmp = explode('/', $arr[0]);
                        $dates = '20' . $dtmp[2] . '-' . $dtmp[1] . '-' . $dtmp[0] . ' ' . $arr[1] . ':00';

                        $amount = Str::of($value['amount'])->replace('+', '');
                        $amount = Str::of($amount)->replace(',', '')->__toString();

                        $detail = Str::of($value['transaction_reference_id'])->replace('-', '')->__toString();

                        $hash = md5($data->code . $dates . $amount . $detail);

                        $diff = core()->DateDiff($dates);
                        if ($diff > 1) continue;

                        $newpayment = BankPayment::firstOrNew(['tx_hash' => $hash, 'account_code' => $data->code]);
                        $newpayment->account_code = $data->code;
                        $newpayment->bank = 'twl_' . $mobile_number;
                        $newpayment->bankstatus = 1;
                        $newpayment->bankname = 'TW';
                        $newpayment->report_id = $value['report_id'];
                        $newpayment->bank_time = $dates;
                        $newpayment->type = $value['type'];
                        $newpayment->title = $value['title'];
                        $newpayment->value = $amount;
                        $newpayment->tx_hash = $hash;
                        $newpayment->detail = $detail;
                        $newpayment->atranferer = $detail;
                        $newpayment->time = $dates;
                        $newpayment->create_by = 'SYSAUTO';
                        $newpayment->save();

                    }
                }
            }
            return 0;
        }
    }

    public function failed(Throwable $exception)
    {
        report($exception);
    }
}
