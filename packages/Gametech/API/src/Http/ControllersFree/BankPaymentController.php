<?php

namespace Gametech\API\Http\ControllersFree;

use App\Libraries\KbankOut;
use App\Libraries\ScbOut;
use Gametech\Auto\Jobs\PaymentBay;
use Gametech\Auto\Jobs\PaymentOutKbankNew;
use Gametech\Auto\Jobs\PaymentOutScbNew;
use Gametech\Payment\Repositories\BankAccountRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\BankRepository;
use Gametech\Payment\Repositories\WithdrawNewRepository;
use Illuminate\Http\Request;

class BankPaymentController extends AppBaseController
{
    protected $_config;

    protected $repository;
    protected $bankAccount;

    protected $bank;

    protected $withdraw;

    public function __construct(
        BankPaymentRepository $repository,
        BankAccountRepository $bankAccount,
        BankRepository        $bank,
        WithdrawNewRepository $withdraw
    )
    {
        $this->_config = request('_config');

        $this->middleware('api');

        $this->repository = $repository;

        $this->bankAccount = $bankAccount;

        $this->bank = $bank;

        $this->withdraw = $withdraw;
    }


    public function krungsri(Request $request)
    {
        $data = json_decode($request['data'], true);

        $path = storage_path('bankpayment/krungsri.log');
        file_put_contents($path, print_r($data, true));

        $bank_account = $this->bankAccount->findOneByField('acc_no', $data['acc_no']);
        if (!$bank_account) {
            return $this->sendError('ไม่พบเลขบัญชี', 200);
        }

        $data['bankcode'] = $bank_account['code'];

        if (isset($data['balance'])) {
            $balance = ($data['balance'] ? $data['balance'] : 0);
            $this->bankAccount->update([
                'balance' => $balance
            ], $bank_account->code);
        }

        $out = $data['data'];
        if (!(empty($out)) && count($out) > 0) {

            for ($indexrow = count($out); $indexrow >= 0; $indexrow--) {

                $list = [
                    'date' => $out[$indexrow]['time'],
                    'channel' => $out[$indexrow]['channel'],
                    'acc_num' => $out[$indexrow]['acc_num'],
                    'detail' => $out[$indexrow]['detail'],
                    'checktime' => strtotime(date("Y-m-d H:i:s")),
                    'value' => str_replace(",", "", $out[$indexrow]['value'])
                ];


                if ($out[$indexrow]['value'] == "" || $out[$indexrow]['value'] == 0) {
                    continue;
                }
                if (strlen($list['date']) < 6) {
                    continue;
                }

                PaymentBay::dispatchAfterResponse($list, $data)->onQueue('payment');

            }
        }

    }

    public function getBank($method, $acc)
    {
//        $acc = $request->input('account');
        $firstname = '';
        $value = 0;
//        return $this->sendError('ไม่พบเลขบัญชี', 200);


        $datas = $this->repository->scopeQuery(function ($query) use ($method, $acc) {
            return $query->select('code', 'bankname', 'bank_time', 'report_id', 'value', 'detail', 'atranferer', 'tx_hash', 'title')->where('bank', $method . '_' . $acc)->orderBy('code', 'desc')->take(20);
        })->all();


        $data = $datas->map(function ($items) {
            $item = (object)$items;
//            if (!empty($item->atranferer)) {
//                if(Str::length($item->atranferer) == 4){
//                    $acc_chk = explode(' ', $item->detail);
//                    if (isset($acc_chk[4])) {
//                        $firstname = $acc_chk[4];
//                        $acc = Str::of($acc_chk[2])->replaceMatches('/[^0-9]/', '')->trim();
//                        $value = Str::of($acc)->replace('*', '')->__toString();
//                    }
//                }else{
//                    $firstname = '';
//                    $acc = Str::of($item->atranferer)->replaceMatches('/[^0-9]/', '')->trim();
//                    $value = Str::of($acc)->replace('*', '')->__toString();
//                }
//            } else {
//
//                $acc_chk = explode(' ', $item->detail);
//                if (isset($acc_chk[4])) {
//                    $firstname = $acc_chk[4];
//                    $acc = Str::of($acc_chk[2])->replaceMatches('/[^0-9]/', '')->trim();
//                    $value = Str::of($acc)->replace('*', '')->__toString();
//
//                }
//            }

            return [
                'id' => $item->code,
                'bank' => $item->bankname,
                'time' => $item->bank_time->format('Y-m-d H:i:s'),
                'channel' => $item->report_id,
                'amount' => $item->value,
                'detail' => $item->detail,
                'atranferer' => $item->atranferer,
                'tx_hash' => $item->tx_hash,
                'firstname' => $item->title

            ];
        });

//        dd()

//        $newdata = new ScbResource($da1ta);
        return $this->sendResponse($data, 'complete');
    }

    public function getScb($acc)
    {
//        $acc = $request->input('account');
        $firstname = '';
        $value = 0;
        return $this->sendError('ไม่พบเลขบัญชี', 200);


        $datas = $this->repository->scopeQuery(function ($query) use ($acc) {
            return $query->select('code', 'bankname', 'bank_time', 'channel', 'value', 'detail', 'atranferer', 'tx_hash', 'title')->where('bank', 'scb_' . $acc)->orderBy('code', 'desc')->take(50);
        })->all();


        $data = $datas->map(function ($items) {
            $item = (object)$items;
//            if (!empty($item->atranferer)) {
//                if(Str::length($item->atranferer) == 4){
//                    $acc_chk = explode(' ', $item->detail);
//                    if (isset($acc_chk[4])) {
//                        $firstname = $acc_chk[4];
//                        $acc = Str::of($acc_chk[2])->replaceMatches('/[^0-9]/', '')->trim();
//                        $value = Str::of($acc)->replace('*', '')->__toString();
//                    }
//                }else{
//                    $firstname = '';
//                    $acc = Str::of($item->atranferer)->replaceMatches('/[^0-9]/', '')->trim();
//                    $value = Str::of($acc)->replace('*', '')->__toString();
//                }
//            } else {
//
//                $acc_chk = explode(' ', $item->detail);
//                if (isset($acc_chk[4])) {
//                    $firstname = $acc_chk[4];
//                    $acc = Str::of($acc_chk[2])->replaceMatches('/[^0-9]/', '')->trim();
//                    $value = Str::of($acc)->replace('*', '')->__toString();
//
//                }
//            }

            return [
                'id' => $item->code,
                'bank' => $item->bankname,
                'time' => $item->bank_time,
                'channel' => $item->channel,
                'amount' => $item->value,
                'detail' => $item->detail,
                'atranferer' => $item->atranferer,
                'tx_hash' => $item->tx_hash,
                'firstname' => $item->title

            ];
        });

//        dd()

//        $newdata = new ScbResource($da1ta);
        return $this->sendResponse($data, 'complete');
    }

    public function getKbank($acc)
    {
//        $acc = $request->input('account');
        $firstname = '';
        $value = 0;
        return $this->sendError('ไม่พบเลขบัญชี', 200);


        $datas = $this->repository->scopeQuery(function ($query) use ($acc) {
            return $query->select('code', 'bankname', 'bank_time', 'channel', 'value', 'detail', 'atranferer', 'tx_hash', 'title')->where('bank', 'kbank_' . $acc)->orderBy('code', 'desc')->take(50);
        })->all();


        $data = $datas->map(function ($items) {
            $item = (object)$items;
//            if (!empty($item->atranferer)) {
//                if(Str::length($item->atranferer) == 4){
//                    $acc_chk = explode(' ', $item->detail);
//                    if (isset($acc_chk[4])) {
//                        $firstname = $acc_chk[4];
//                        $acc = Str::of($acc_chk[2])->replaceMatches('/[^0-9]/', '')->trim();
//                        $value = Str::of($acc)->replace('*', '')->__toString();
//                    }
//                }else{
//                    $firstname = '';
//                    $acc = Str::of($item->atranferer)->replaceMatches('/[^0-9]/', '')->trim();
//                    $value = Str::of($acc)->replace('*', '')->__toString();
//                }
//            } else {
//
//                $acc_chk = explode(' ', $item->detail);
//                if (isset($acc_chk[4])) {
//                    $firstname = $acc_chk[4];
//                    $acc = Str::of($acc_chk[2])->replaceMatches('/[^0-9]/', '')->trim();
//                    $value = Str::of($acc)->replace('*', '')->__toString();
//
//                }
//            }

            return [
                'id' => $item->code,
                'bank' => $item->bankname,
                'time' => $item->bank_time,
                'channel' => $item->channel,
                'amount' => $item->value,
                'detail' => $item->detail,
                'atranferer' => $item->atranferer,
                'tx_hash' => $item->tx_hash,
                'firstname' => $item->title

            ];
        });

//        dd()

//        $newdata = new ScbResource($da1ta);
        return $this->sendResponse($data, 'complete');
    }

    public function getBankAll()
    {
//        $acc = $request->input('account');
        $firstname = '';
        $value = 0;
//        return $this->sendError('ไม่พบเลขบัญชี', 200);


        $datas = $this->repository->scopeQuery(function ($query) {
            return $query->select('code', 'bankname', 'bank_time', 'report_id', 'value', 'detail', 'atranferer', 'tx_hash', 'title')->orderBy('code', 'desc')->take(50);
        })->all();


        $data = $datas->map(function ($items) {
            $item = (object)$items;
//            if (!empty($item->atranferer)) {
//                if(Str::length($item->atranferer) == 4){
//                    $acc_chk = explode(' ', $item->detail);
//                    if (isset($acc_chk[4])) {
//                        $firstname = $acc_chk[4];
//                        $acc = Str::of($acc_chk[2])->replaceMatches('/[^0-9]/', '')->trim();
//                        $value = Str::of($acc)->replace('*', '')->__toString();
//                    }
//                }else{
//                    $firstname = '';
//                    $acc = Str::of($item->atranferer)->replaceMatches('/[^0-9]/', '')->trim();
//                    $value = Str::of($acc)->replace('*', '')->__toString();
//                }
//            } else {
//
//                $acc_chk = explode(' ', $item->detail);
//                if (isset($acc_chk[4])) {
//                    $firstname = $acc_chk[4];
//                    $acc = Str::of($acc_chk[2])->replaceMatches('/[^0-9]/', '')->trim();
//                    $value = Str::of($acc)->replace('*', '')->__toString();
//
//                }
//            }

            return [
                'id' => $item->code,
                'bank' => $item->bankname,
                'time' => $item->bank_time->format('Y-m-d H:i:s'),
                'channel' => $item->report_id,
                'amount' => $item->value,
                'detail' => $item->detail,
                'atranferer' => $item->atranferer,
                'tx_hash' => $item->tx_hash,
                'firstname' => $item->title

            ];
        });

//        dd()

//        $newdata = new ScbResource($da1ta);
        return $this->sendResponse($data, 'complete');
    }

    public function BankList($id, Request $request)
    {

        $keys = $request->header('access-key');
        if ($keys !== '43JP?!Cw/533w82IVJhHiKscY-q7mRzhHENH?M0YrDLnpOQj5DM9g2xdMEqcL2kQqMmoUD!y2EvChb5opws?bAECTgeH1a2-AovFSOaBDGN/cLy87o5DB9x0iMK3kUvPxh/exz!dOnuUyZ3wDSm?onuWxRW603UKL1KL0veHQpsGWGHC-Tva66YmNfel=9mhp3foj=!Dc7C89bRzMbkOVHh?62vQ9UK?HH!p3q/Ix4liGSiM/?gHU/8lV=oQL0Wx') {
            return $this->sendResponseFail([], 'Cannot Access');
        }

        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutOneNew($id);
        if (!$bank) {
            return $this->sendResponseFail([], 'Not Found Account');
        }

        $banks = [
            'value' => '',
            'text' => '== Select =='
        ];

        $responses = collect(app('Gametech\Payment\Repositories\BankRepository')->findWhere(['enable' => 'Y'])->toArray());

        $responses = $responses->map(function ($items) {
            $item = (object)$items;
            return [
                'value' => $item->shortcode,
                'text' => $item->name_en
            ];

        })->prepend($banks);


        $result['data'] = $responses;
        return $this->sendResponseNew($result, 'Complete');
    }

    public function BankCheck($id, Request $request)
    {
        $keys = $request->header('access-key');
        if ($keys !== '43JP?!Cw/533w82IVJhHiKscY-q7mRzhHENH?M0YrDLnpOQj5DM9g2xdMEqcL2kQqMmoUD!y2EvChb5opws?bAECTgeH1a2-AovFSOaBDGN/cLy87o5DB9x0iMK3kUvPxh/exz!dOnuUyZ3wDSm?onuWxRW603UKL1KL0veHQpsGWGHC-Tva66YmNfel=9mhp3foj=!Dc7C89bRzMbkOVHh?62vQ9UK?HH!p3q/Ix4liGSiM/?gHU/8lV=oQL0Wx') {
            return $this->sendResponseFail([], 'Cannot Access');
        }

        $data = [];
        $to_bank = $request->input('to_bank');
        $account = $request->input('to_account');
//        $id = $request->input('to_account');

        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutOneNew($id);
        if (!$bank) {
            return $this->sendResponseFail([], 'Not Found Account');
        }

        $bank_code = $bank->bank->code;

        if ($bank_code == 2) {

            $kbank = new KbankOut();
            $bank_trans = $to_bank;
            if ($bank_trans == '500') {
                return $this->sendResponseFail([], 'Not Found Bank Code');
            }
            $param['toBankCode'] = $bank_trans;
            $param['toAccount'] = $account;
            $chk = $kbank->BankCurlTrans($bank['acc_no'], 'getname', $param, 'POST');
            if ($chk['status'] === true) {
                $data['name'] = $chk['data']['to']['toAccountName'];
            }

        } elseif ($bank_code == 4) {

            $kbank = new ScbOut();
            $bank_trans = $to_bank;
            if ($bank_trans == '500') {
                return $this->sendResponseFail([], 'Not Found Bank Code');
            }
            $param['ToBankCode'] = $bank_trans;
            $param['ToBank'] = $account;
            $chk = $kbank->BankCurlTrans($bank['acc_no'], 'getname', $param, 'POST');
            if ($chk['status'] === true) {
                $data['name'] = $chk['data']['to']['accountName'];
            }

        }


        if (!$data) {
            return $this->sendResponseFail([], 'Not found Account Data');
        }


        return $this->sendResponse($data, 'Complete');

    }

    public function BankTran($id, Request $request)
    {
        $return = [];
        $keys = $request->header('access-key');
        if ($keys !== '43JP?!Cw/533w82IVJhHiKscY-q7mRzhHENH?M0YrDLnpOQj5DM9g2xdMEqcL2kQqMmoUD!y2EvChb5opws?bAECTgeH1a2-AovFSOaBDGN/cLy87o5DB9x0iMK3kUvPxh/exz!dOnuUyZ3wDSm?onuWxRW603UKL1KL0veHQpsGWGHC-Tva66YmNfel=9mhp3foj=!Dc7C89bRzMbkOVHh?62vQ9UK?HH!p3q/Ix4liGSiM/?gHU/8lV=oQL0Wx') {
            return $this->sendResponseFail([], 'Cannot Access');
        }
        $ip = $request->ip();
        $user = 'API';

        $request->validate([
            'to_bank' => 'required',
            'to_account' => 'required|string',
            'to_name' => 'nullable|string',
            'amount' => 'required|numeric',
            'remark' => 'nullable|string'
        ]);


        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutOneNew($id);
        if (!$bank) {
            return $this->sendResponseFail([], 'Not Found Account');
        }

        $to_bank = $request->input('to_bank');
        $to_bank = app('Gametech\Payment\Repositories\BankRepository')->findOneWhere(['shortcode' => $to_bank]);


        $data['to_bank'] = $to_bank->code;
        $data['to_account'] = $request->input('to_account');
        $data['to_name'] = $request->input('to_name');
        $data['amount'] = $request->input('amount');
        $data['account_code'] = $bank->code;
        $data['remark'] = $request->input('remark');
        $data['emp_approve'] = 0;
        $data['emp_name'] = $user;
        $data['ip_admin'] = $ip;
        $data['ip'] = $ip;
        $data['user_update'] = $user;
        $data['status_withdraw'] = 'A';
        $data['status'] = 0;
        $data['date_bank'] = date('Y-m-d');
        $data['time_bank'] = date('H:i:s');
        $response = $this->withdraw->create($data);


        $bank_code = $bank->bank->code;
        if ($bank_code == 2) {
            $return = PaymentOutKbankNew::dispatchNow($response->code);
        } elseif ($bank_code == 4) {
            $return = PaymentOutScbNew::dispatchNow($response->code);
        }

//        switch ($return['success']) {
//
//
//            case 'COMPLETE':
//            case 'NOTWAIT':
//            case 'MONEY':
//                break;
//
//            case 'NOMONEY':
//            case 'FAIL_AUTO':
//            default:
//                $datanew['status'] = 0;
//                $this->repository->update($datanew, $response->code);
//
//
//        }

        return $this->sendResponse($return, 'Result');


    }

}
