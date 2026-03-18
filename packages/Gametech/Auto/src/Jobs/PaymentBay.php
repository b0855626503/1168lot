<?php

namespace Gametech\Auto\Jobs;

use App\Libraries\BayBiz;
use Gametech\Payment\Models\BankPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;


class PaymentBay implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $failOnTimeout = true;

    public $uniqueFor = 60;

    public $timeout = 30;

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
        return ['render', 'bay:' . $this->id];
    }

    public function uniqueId()
    {
        return $this->id;
    }


    public function handle_()
    {
        $header = [];
        $response = [];
        $mobile_number = $this->id;
        $update = true;


        $datenow = now()->toDateTimeString();

        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOne('bay', $mobile_number);
        if (!$bank) {
            return true;
        }

        $bank_number = $bank['acc_no'];
        $bank_username = $bank['user_name'];
        $bank_password = $bank['user_pass'];

        $url = 'https://bays.z7z.work/' . $mobile_number . '/transection.php';

        $response = rescue(function () use ($url) {
            return Http::timeout(15)->withHeaders([
                'access-key' => '0dbbe3a5-8a3d-4505-9a8e-5790b4a6c90d'
            ])->post($url);

        }, function ($e) {

            return $e;

        }, true);

        if ($response->failed()) {
            return false;
        }

        if ($response->successful()) {

            $response = $response->json();

            $lists = $response['data'];
            $balance = $response['available_balance'];

            $bank->balance = str_replace(',', '', number_format((float)$balance, 2));
            $bank->checktime = $datenow;
            $bank->save();

            if (count($lists) > 0) {
                krsort($lists);

                foreach ($lists as $list) {

//                    $date = str_replace('/','-',$list['date']);
//                    $newdate = date('Y-m-d',strtotime($date)).' '.$list['time'];

                    $date = explode('/', $list['date']);
                    $newdate = $date[2] . '-' . $date[1] . '-' . $date[0] . ' ' . $list['time'] . ':00';

                    $note = explode(' ', $list['note']);

                    if (core()->DateDiff($newdate) > 1) continue;

                    $amount = floatval(preg_replace('/[^0-9\.\+\-]/', '', $list['amount']));
                    $amount = str_replace(',', '', number_format($amount, 2));

                    $list['tx_hash'] = md5($newdate . $list['note'] . $amount . $list['channel']);

                    $newpayment = BankPayment::firstOrNew(['tx_hash' => $list['tx_hash'], 'account_code' => $bank->code]);
                    $newpayment->account_code = $bank->code;
                    $newpayment->bank = 'bay_' . $bank_number;
                    $newpayment->bankstatus = 1;
                    if ($list['type'] == 'D') {
                        $newpayment->value = $amount;
                    } else {
                        $newpayment->value = $amount;
                    }

                    $newpayment->bankname = 'BAY';
                    $newpayment->bank_time = $newdate;
                    $newpayment->detail = $list['note'];
                    $newpayment->atranferer = $list['note_ref'];
                    $newpayment->channel = $list['channel'];
                    $newpayment->tx_hash = $list['tx_hash'];
                    $newpayment->title = $list['note_bank'];
                    $newpayment->create_by = 'SYSAUTO';
                    $newpayment->ip_topup = '';
                    $newpayment->save();
                }

            }

        }

    }

    public function handle()
    {
        $datenow = now()->toDateTimeString();
        $header = [];
        $response = [];
        $mobile_number = $this->id;
        $update = true;

        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('bay', $mobile_number);
        if (!$bank) {
            return true;
        }

//        return $this->BayLocal();

        if ($bank->local == 'Y') {
            return $this->BayLocal();
        } else {
            return $this->BayApi();
        }
    }

    public function BayLocal()
    {
        $datenow = now()->toDateTimeString();
        $header = [];
        $response = [];
        $mobile_number = $this->id;
        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOne('bay', $mobile_number);
        if (!$bank) {
            return 1;
        }

        if($bank->smestatus == 'N'){
            return false;
        }

        $balance = 0;
        $USERNAME = $bank->user_name; //"give855";
        $PASSWORD = $bank->user_pass; //"Zxcv@3622";

//        $ACCOUNT_NAME = substr($bank['acc_no'], 0, 3) . "-" . substr($bank['acc_no'], 3, 1) . "-" . substr($bank['acc_no'], 4, 5) . "-" . substr($bank['acc_no'], 9, 1); //"719-220819-2"
        $accname = $mobile_number;

        $em = new BayBiz();
        $em->setLogin($USERNAME, $PASSWORD);
        $em->setAccountNumber($accname);
        $em->login();
//        if ($em->login()) {

        $collect = $em->getTransaction();


        $path = storage_path('logs/bay/gettransaction_' . $accname . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($collect, true));

        $balance = $em->getBalance();
        if ($balance >= 0) {
            $bank->balance = floatval($balance);
            $bank->checktime = $datenow;
            $bank->save();
        } else {
            $bank->api_refresh = 'เชคยอดเงินไม่ได้';
            $bank->checktime = $datenow;
            $bank->save();
        }


        $path = storage_path('logs/bay/getbalance_' . $accname . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($balance, true));

        if (count($collect) > 0) {

            foreach ($collect as $list) {

                if (core()->DateDiff($list['date']) > 1) continue;

                $list['value'] = str_replace(",", "", $list['in']);
                $list['tx_hash'] = md5($accname . $list['date'] . $list['value']);
                if ($list['value'] == "" || $list['value'] == 0) {
                    continue;
                }

                $newpayment = BankPayment::firstOrNew(['tx_hash' => $list['tx_hash'], 'account_code' => $bank->code]);
                $newpayment->account_code = $bank->code;
                $newpayment->bank = 'bay_' . $accname;
                $newpayment->bankstatus = 1;
                $newpayment->bankname = 'bay';
                $newpayment->bank_time = $list['date'];
                $newpayment->report_id = $list['bank'];
                $newpayment->atranferer = $list['fromaccno'];
                $newpayment->channel = 'BIZ';
                $newpayment->value = $list['value'];
                $newpayment->tx_hash = $list['tx_hash'];
                $newpayment->detail = $list['info'];
                $newpayment->title = '';
                $newpayment->time = $list['date'];
                $newpayment->create_by = 'SYSAUTO';
                $newpayment->ip_topup = '';
                $newpayment->save();

            }

            $bank->api_refresh = 'สำเร็จ';
            $bank->checktime = $datenow;
            $bank->save();
            return 0;

        } else {

            $bank->api_refresh = 'ดึงรายการเดินบัญชีไม่ได้ หรือไม่มีรายการ';
            $bank->checktime = $datenow;
            $bank->save();
            return 0;

        }

//        }else{
//            $bank->api_refresh = 'เข้าสู่ระบบไม่ได้';
//            $bank->checktime = $datenow;
//            $bank->save();
//            return 0;
//        }
    }

    public function BayApi()
    {
        $header = [];
        $response = [];
        $mobile_number = $this->id;
        $update = true;


        $datenow = now()->toDateTimeString();

        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOneNew('bay', $mobile_number);
        if (!$bank) {
            return false;
        }

        if($bank->smestatus == 'N'){
            return false;
        }

        $bank_number = $bank['acc_no'];
        $bank_username = $bank['user_name'];
        $bank_password = $bank['user_pass'];

        $url = 'https://api.superich168.com/bay/apibay.php';
        $param = [
            'username' => $bank_username,
            'password' => $bank_password,
            'account' => $bank_number
        ];

        $response = rescue(function () use ($url,$param) {
            return Http::timeout(30)->asForm()->post($url,$param);

        }, function ($e) {

            return $e;

        }, true);

        if ($response->failed()) {
            return false;
        }

        $responses = $response->json();
//        $path = storage_path('logs/bay/gettransaction_' . $mobile_number . '_' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r($response, true));


        if ($response->successful()) {

            $lists = $responses['transaction'];

            $balance = $responses['balance'];

            $bank->balance = str_replace(',', '', number_format((float)$balance, 2));


            if (count($lists) > 0) {
                krsort($lists);
                foreach ($lists as $list) {

                    if (core()->DateDiff($list['date']) > 1) continue;

                    $list['value'] = str_replace(",", "", $list['in']);
                    $list['tx_hash'] = md5($list['fromaccno'] . $list['date'] . $list['value']);
                    if ($list['value'] == "" || $list['value'] == 0) {
                        continue;
                    }
                    if(is_null($list['bank']) || $list['bank'] === ''){
                        $list['bank'] = 'BAY';
                    }

                    $newpayment = BankPayment::firstOrNew(['tx_hash' => $list['tx_hash'], 'account_code' => $bank->code]);
                    $newpayment->account_code = $bank->code;
                    $newpayment->bank = 'bay_' . $bank_number;
                    $newpayment->bankstatus = 1;
                    $newpayment->bankname = 'bay';
                    $newpayment->bank_time = $list['date'];
                    $newpayment->report_id = $list['bank'];
                    $newpayment->atranferer = $list['fromaccno'];
                    $newpayment->channel = 'BIZ';
                    $newpayment->value = $list['value'];
                    $newpayment->tx_hash = $list['tx_hash'];
                    $newpayment->detail = $list['info'];
                    $newpayment->title = $list['bank'];
                    $newpayment->time = $list['date'];
                    $newpayment->create_by = 'SYSAUTO';
                    $newpayment->ip_topup = '';
                    $newpayment->save();

                }

                $bank->api_refresh = 'สำเร็จ';
                $bank->checktime = $datenow;
                $bank->save();
                return 1;

            } else {

                $bank->api_refresh = 'ดึงรายการเดินบัญชีไม่ได้ หรือไม่มีรายการ';
                $bank->checktime = $datenow;
                $bank->save();
                return 1;

            }

        }

    }


    public function failed(Throwable $exception)
    {
        report($exception);
    }
}
