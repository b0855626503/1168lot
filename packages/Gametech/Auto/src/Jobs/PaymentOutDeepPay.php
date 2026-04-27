<?php

declare(strict_types=1);

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\DeepPay;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PaymentOutDeepPay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 0;
    public $maxExceptions = 5;
    public $retryAfter = 0;

    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function handle()
    {
        $return = [
            'complete' => false,
            'success' => 'NORMAL',
            'msg' => 'อนุมัติรายการเรียบร้อยแล้ว (รายการทั่วไป)',
        ];

        $config = core()->getConfigData();
        $id = $this->id;

        if ($config->seamless === 'Y') {
            $order = app('Gametech\\Payment\\Repositories\\WithdrawSeamlessRepository')->find($id);
        } else {
            $order = app('Gametech\\Payment\\Repositories\\WithdrawRepository')->find($id);
        }

        if (!$order) {
            $return['success'] = 'NOTFOUND';
            $return['msg'] = 'ไม่พบรายการถอน';
            return $return;
        }

        $bank = app('Gametech\\Payment\\Repositories\\BankAccountRepository')->getAccountOutOne($order->account_code);
        if (!$bank) {
            $return['success'] = 'NOBANK';
            $return['msg'] = 'ไม่พบบัญชีถอนออกของระบบ';
            return $return;
        }

        $min = (float) config('deeppay.min_withdraw', 0);
        if ($min > 0 && (float) $order->amount < $min) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = 'ยอดเงินถอนออโต้ ขั้นต่ำ ' . $min . ' บาท';
            return $return;
        }

        if ($order->status_withdraw != 'W') {
            $return['success'] = 'NOTWAIT';
            $return['msg'] = 'รายการนี้ กำลังอยู่ระหว่างประมวลผล';
            return $return;
        }

        $member = app('Gametech\\Member\\Repositories\\MemberRepository')->find($order->member_code);
        if (!$member) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = 'ไม่พบสมาชิก';
            return $return;
        }

        $memberBankCode = $this->resolveMemberBankCode($member);
        $memberAccountNo = preg_replace('/\D+/', '', (string) data_get($member, 'acc_no', ''));
        $memberAccountName = trim((string) (data_get($member, 'acc_name') ?: data_get($member, 'name')));

        if ($memberBankCode === '' || $memberAccountNo === '' || $memberAccountName === '') {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = 'ข้อมูลบัญชีสมาชิกไม่ครบถ้วน';
            return $return;
        }

        $api = new DeepPay();
        $orderId = 'DWIT-' . str_pad((string) $order->code, 6, '0', STR_PAD_LEFT) . '-' . date('YmdHis');
        $amount = (float) ($order['amount'] - $order['fee']);
        $callbackUrl = (string) (config('deeppay.withdraw_callback_url') ?: route('api.deeppay.withdraw.callback'));

        $order->txid = $orderId;
        $order->save();

        $payload = [
            'member_code' => (string) $member->code,
            'currency' => (string) config('deeppay.currency', 'THB'),
            'order_id' => $orderId,
            'hash' => $api->hashOrderId($orderId),
            'amount' => number_format($amount, 2, '.', ''),
            'bank_code' => $memberBankCode,
            'bank_account_no' => $memberAccountNo,
            'bank_account_name' => $memberAccountName,
            'pin_code' => (string) config('deeppay.pin_code'),
            'callback_url' => $callbackUrl,
        ];

        Log::channel('deeppay_withdraw_create')->info('[DEEPPAY] create withdraw start', [
            'txid' => $orderId,
            'member_code' => (int) $member->code,
            'withdraw_code' => (int) $order->code,
            'amount' => $amount,
        ]);

        $order->refresh();
        if ($order->status_withdraw != 'W') {
            $return['success'] = 'NOTWAIT';
            $return['msg'] = 'รายการนี้ กำลังอยู่ระหว่างประมวลผล';
            return $return;
        }

        $resp = $api->withdraw($payload);
        if (!data_get($resp, 'success')) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = (string) (data_get($resp, 'msg') ?: 'create withdraw failed');

            Log::channel('deeppay_withdraw_create')->error('[DEEPPAY] create withdraw failed', [
                'txid' => $orderId,
                'resp' => $resp,
            ]);

            return $return;
        }

        $provider = (array) data_get($resp, 'data.data', []);

        app('Gametech\\Core\\Repositories\\CheckCaseRepository')->create([
            'method' => 2,
            'bank_code' => $bank->banks,
            'txid' => $orderId,
            'amount' => $amount,
            'payamount' => (float) data_get($provider, 'amount_transfer', $amount),
            'username' => $member->user_name,
            'name' => $member->name,
            'detail' => (string) (data_get($provider, 'txn_no', '') ?: $orderId),
            'qrcode' => '',
            'status' => 'pending',
            'user_create' => $member->name,
            'user_update' => $member->name,
        ]);

        $order->status = 1;
        $order->status_withdraw = 'A';
        $order->remark_admin = $order->remark_admin . ' กำลังทำรายการถอนเงินออกจาก DeepPay โอนเข้าบัญชี ' . $member->name . ' เลขที่บัญชี ' . $member->acc_no . ' ธนาคาร ' . $member->bank->shortcode . ' จำนวน ' . $amount;
        $order->save();

        $return['complete'] = true;
        $return['success'] = 'COMPLETE';
        $return['msg'] = 'สร้างรายการถอน DeepPay สำเร็จ';

        return $return;
    }

    private function resolveMemberBankCode($member): string
    {
        $map = (array) config('deeppay.bank_code_map', []);
        $candidates = [
            data_get($member, 'bank.shortcode'),
            data_get($member, 'bank.code'),
            data_get($member, 'bank_code'),
        ];

        foreach ($candidates as $candidate) {
            $value = strtoupper(trim((string) $candidate));
            if ($value === '') {
                continue;
            }
            if (preg_match('/^\d{3}$/', $value)) {
                return $value;
            }
            if (isset($map[$value])) {
                return (string) $map[$value];
            }
        }

        return '';
    }
}
