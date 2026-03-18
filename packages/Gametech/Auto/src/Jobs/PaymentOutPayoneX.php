<?php

namespace Gametech\Auto\Jobs;


use Gametech\Payment\Libraries\PayoneX;
use Gametech\Payment\Libraries\WellPay;
use Gametech\Payment\Models\BankAccountProxy;
use Gametech\Payment\Models\PaymentProviderAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PaymentOutPayoneX implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;

    public $tries = 0;

    public $maxExceptions = 5;

    public $retryAfter = 0;

    public $id;

    protected $customer_uuid;


    public function __construct($id)
    {
        $this->id = $id;
    }

    public function handle()
    {
        $api = new PayoneX();
        $return['complete'] = false;
        $return['success'] = 'NORMAL';
        $return['msg'] = 'อนุมัติรายการเรียบร้อยแล้ว (รายการทั่วไป)';

        $baseurl = 'https://'.config('app.admin_url').'.'.(is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));

        $id = $this->id;
        $balance_start = 0;
        $balance_stop = 0;
        $customer_uuid = '';
        $datenow = now()->toDateTimeString();

        $order = app('Gametech\Payment\Repositories\WithdrawRepository')->find($id);
        $bank = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutOne($order->account_code);
        if (! $bank) {
            $return['complete'] = true;

            return $return;
        }

        $min = $bank->deposit_min;
        if ($order->amount < $min) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = 'ยอดเงินถอนออโต้ ขั้นต่ำ '.$min.' บาท';

            return $return;
        }
        if ($order->status_withdraw != 'W') {
            $return['success'] = 'NOTWAIT';
            $return['msg'] = 'รายการนี้ กำลังอยู่ระหว่างประมวลผล';

            return $return;
        }

        $order_id = 'PWRD-'.str_pad($order->code, 6, '0', STR_PAD_LEFT).'-'.date('YmdHis');

        $order->txid = $order_id;
//        $order->status_withdraw = 'A';
        $order->save();

        $transactionId = $order->txid;

        $member = app('Gametech\Member\Repositories\MemberRepository')->find($order->member_code);

        $amount = (float) ($order['amount'] - $order['fee']);
//        $amount = number_format($amount, 2, '.', '');
//        $amount = number_format($amount, 0);
        $bankAccountNumber = $member->acc_no;
        $bankName = $api->Banks($member->bank_code);
        if ($bankName === false) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = 'บัญชีธนาคารของสมาชิก ไม่รองรับการโอน ในขณะนี้';

            return $return;
        }

        $token = $this->auth();

        $check_user = PaymentProviderAccount::query()
            ->where('provider', 'payonex')
            ->where('member_code', $member->code)
            ->first();

        if (!$check_user) {
            $param_user = [
                'name' => $member->name,
                'bankCode' => $bankName,
                'accountNo' => $bankAccountNumber,
            ];

            $url = config('payonex.api_url') . '/v2/customers';
            $result = $api->create_customer($url, $param_user, $this->token);

            if (($result['success'] ?? false) === true) {
                $check_user = new PaymentProviderAccount();
                $check_user->provider = 'payonex';
                $check_user->member_code = $member->code;

                $check_user->account_identifier = (string) $member->code;
                $check_user->account_platform = (string) $bankName;
                $check_user->currency_code = 'THB';

                $check_user->bank_code = (int) $member->bank_code;
                $check_user->bank_account_number = (string) $bankAccountNumber;
                $check_user->bank_account_name = (string) $member->name;

                $check_user->customer_id = (string) data_get($result, 'data.customerUuid', '');

                $meta = (array) ($check_user->meta ?? []);
                $meta['payonex'] = array_merge((array) ($meta['payonex'] ?? []), [
                    'account_bank' => (string) $bankName,
                    'account_number' => (string) $bankAccountNumber,
                ]);
                $check_user->meta = $meta;

                $check_user->save();

                $this->customer_uuid = (string) data_get($result, 'data.customerUuid', '');
            }
        } else {
            $oldBankName = (string) data_get($check_user->meta, 'payonex.account_bank', '');
            $oldAccountNumber = (string) data_get($check_user->meta, 'payonex.account_number', '');

            if ($oldAccountNumber === '' && !empty($check_user->bank_account_number)) {
                $oldAccountNumber = (string) $check_user->bank_account_number;
            }

            // ถ้าธนาคาร/เลขบัญชีเปลี่ยน -> update provider
            if ($oldBankName !== (string) $bankName || $oldAccountNumber !== (string) $bankAccountNumber) {
                $url = config('payonex.api_url') . '/v2/customers/' . $check_user->customer_id;
                $param_user = [
                    'name' => $member->name,
                    'bankCode' => $bankName,
                    'accountNo' => $bankAccountNumber,
                ];

                $response = Http::timeout(30)
                    ->withOptions(['connect_timeout' => 10])
                    ->withHeaders(['Authorization' => $this->token])
                    ->asJson()
                    ->put($url, $param_user);

                if ($response->successful()) {
                    $result = (array) $response->json();
                    if (($result['success'] ?? false) === true) {
                        $check_user->account_platform = (string) $bankName;
                        $check_user->bank_code = (int) $member->bank_code;
                        $check_user->bank_account_number = (string) $bankAccountNumber;
                        $check_user->bank_account_name = (string) $member->name;

                        $meta = (array) ($check_user->meta ?? []);
                        $meta['payonex'] = array_merge((array) ($meta['payonex'] ?? []), [
                            'account_bank' => (string) $bankName,
                            'account_number' => (string) $bankAccountNumber,
                        ]);
                        $check_user->meta = $meta;

                        $check_user->save();
                    }
                }
            }

            $this->customer_uuid = (string) $check_user->customer_id;
        }

        $param = [
            'customerUuid' => trim((string) $this->customer_uuid),
            'amount' => (float) $amount,
            'referenceId' => trim((string) $transactionId),
            'note' => '',
            'remark' => '',
        ];


        $url = config('payonex.api_url') . '/transactions/withdraw/request';
        $response = $api->create_withdraw($url, $param, $token);

        if (($response['success'] ?? false) === true) {

            app('Gametech\Core\Repositories\CheckCaseRepository')->create([
                'method' => 2,
                'bank_code' => $bank->banks,
                'txid' => $order_id,
                'amount' => $amount,
                'payamount' => $amount,
                'username' => $member->user_name,
                'name' => $member->name,
                'detail' => data_get($response, 'data.uuid'),
                //                'url' => $response['data']['qrcode'],
                'qrcode' => '',
                'status' => 'PENDING',
                'user_create' => $member->name,
                'user_update' => $member->name,
            ]);


            $order->status = 1;
            $order->status_withdraw = 'A';
            $order->remark_admin = $order->remark_admin.' กำลัง ทำรายการถอนเงินออกจาก PayoneX โอนเข้าบัญชี '.$member->name.' เลขที่บัญชี '.$member->acc_no.' ธนาคาร '.$member->bank->shortcode.' จำนวน '.$amount;
            $order->save();
            $return['complete'] = true;
            $return['success'] = 'COMPLETE';
            $return['msg'] = $response['msg'];
        } else {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = $response['msg'];
        }

        return $return;
    }

    public function auth()
    {
        $api = new PayoneX;
        $bank_account = BankAccountProxy::where('banks', 307)
            ->where('bank_type', 1)
            ->where('enable', 'Y')
            ->where('status_auto', 'Y')->first();

        if ($bank_account) {
//            if (!$bank_account->token) {
            if (!$bank_account->device_id || $bank_account->expired_date < now()->toDateTimeString()) {
                $url = config('payonex.api_url') . '/authenticate';
                $result = $api->auth($url);
                if ($result['success'] === true) {
                    $bank_account->device_id = $result['token'];
                    $bank_account->expired_date = date('Y-m-d H:i:s', strtotime("+24 hours"));
                    $bank_account->save();
                    return $result['token'];
                }

            } else {
                return $bank_account->device_id;
            }
//            } else {
//                $this->token = $bank_account->token;
//
//            }
        }
    }
}
