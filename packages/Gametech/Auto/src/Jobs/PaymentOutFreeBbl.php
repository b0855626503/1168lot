<?php

namespace Gametech\Auto\Jobs;


use App\Libraries\Bbl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PaymentOutFreeBbl implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public $tries = 1;

    public $maxExceptions = 5;

    public $retryAfter = 130;

    protected $item;

    protected $id;


    public function __construct($item, $id)
    {
        $this->item = $item;
        $this->id = $id;
    }


    public function handle()
    {
        $header = [];
        $response = [];
        $item = $this->item;
        $id = $this->id;
        $update = true;
        $balance = 0;

        $datenow = now()->toDateTimeString();

        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutOne($item['account_code']);
        $member = app('Gametech\Member\Repositories\MemberRepository')->find($item['member_code']);


        $bank_number = $bank['acc_no'];
        $bank_username = $bank['user_name'];
        $bank_password = $bank['user_pass'];

        $bbl = new Bbl();

        $chk = $bbl->BankCurl($bank['acc_no'], 'getbalance', 'POST');
        if ($chk['status']) {
            $balance = str_replace(',', '', $chk['data']['balance']);
            if ($balance >= 0) {
                $bank->balance = $balance;
            }

            $bank->checktime = $datenow;
            $bank->save();
        }

        if ($balance >= $item['amount']) {


            $bank_trans = $bbl->Banks($member->bank_code);
            $data['ToBank'] = $member->acc_no;
            $data['ToBankCode'] = $bank_trans;
            $data['amount'] = $item['amount'];
            $param = json_encode($data);
            $curl = $bbl->BankCurlTrans($bank['acc_no'], 'transfer', $param, 'POST');

            if ($curl['status']) {
                $bank_date = explode(' ', $curl['data']['transfer_date']);

                $update = [
                    'status' => 1,
                    'account_code' => $bank['code'],
                    'remark_admin' => 'ทำรายการถอนเงินออกจากบัญชีอัตโนมัติ',
                    'date_bank' => $bank_date[0],
                    'time_bank' => $bank_date[1],
                    'status_withdraw' => 'C'
                ];

                return app('Gametech\Payment\Repositories\WithdrawFreeRepository')->update($update, $id);
            }
        } else {
            return false;
        }
    }
}
