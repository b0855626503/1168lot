<?php

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\XEPay;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PaymentOutXEPay implements ShouldQueue
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
        $api = new XEPay();
        $return = [
            'complete' => false,
            'success' => 'NORMAL',
            'msg' => 'อนุมัติรายการเรียบร้อยแล้ว (รายการทั่วไป)',
        ];

        $config = core()->getConfigData();
        $id = $this->id;

        if ($config->seamless === 'Y') {
            $order = app('Gametech\\Payment\\Repositories\\WithdrawSeamlessRepository')->with('memberBank.bank')->find($id);
        } else {
            $order = app('Gametech\\Payment\\Repositories\\WithdrawRepository')->with('memberBank.bank')->find($id);
        }

        if (!$order) {
            $return['success'] = 'NOTFOUND';
            $return['msg'] = 'ไม่พบรายการถอน';

            return $return;
        }

        $bank = app('Gametech\\Payment\\Repositories\\BankAccountRepository')->getAccountOutOne($order->account_code ?? $order->bank ?? null);
        if (!$bank) {
            $return['success'] = 'NOBANK';
            $return['msg'] = 'ไม่พบบัญชีถอนออกของระบบ';

            return $return;
        }

        $min = (float) config('xepay.min_withdraw', 200);
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

        $orderId = 'XEWDR-' . str_pad((string) $order->code, 6, '0', STR_PAD_LEFT) . '-' . date('YmdHis');
        $order->txid = $orderId;
        $order->save();

        $member = app('Gametech\\Member\\Repositories\\MemberRepository')->find($order->member_code);
        if (!$member) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = 'ไม่พบสมาชิก';

            return $return;
        }

        $amount = number_format((float) ($order['amount'] - $order['fee']), 2, '.', '');
        $providerBankCode = $api->resolveBankCode($order->memberBank?->bank_code ?? $member->bank_code ?? '');
        $notifyUrl = (string) (config('xepay.withdraw_notify_url') ?: route('api.xepay.withdraw.callback', [], true));
        $email = trim((string) ($member->email ?? config('xepay.default_player_email', '')));

        $payload = [
            'merNo' => (string) config('xepay.mer_no'),
            'tradeNo' => trim($orderId),
            'cType' => (string) config('xepay.c_type'),
            'bankCode' => $providerBankCode,
            'bankCardNo' => trim((string) ($order->memberBank?->account_no ?? $member->acc_no ?? '')),
            'orderAmount' => (string) $amount,
            'accountName' => trim((string) ($order->memberBank?->account_name ?? $member->name ?? '')),
            'openProvince' => (string) config('xepay.default_open_province', '1'),
            'openCity' => (string) config('xepay.default_open_city', '1'),
            'notifyUrl' => $notifyUrl,
            'playerEmail' => $email,
        ];

        $verifyChannelNo = config('xepay.verify_channel_no');
        if ($verifyChannelNo !== null && $verifyChannelNo !== '') {
            $payload['VerifyChannelNo'] = (string) $verifyChannelNo;
        }

        $payload['sign'] = XEPay::signWithdraw(
            (string) $payload['merNo'],
            (string) $payload['tradeNo'],
            (string) $payload['bankCode'],
            (string) $payload['orderAmount'],
            (string) config('xepay.api_key')
        );

        Log::channel('xepay_withdraw_create')->info('[XEPAY] create payout start', [
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

        $resp = $api->createWithdraw($payload);
        if (!data_get($resp, 'success')) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = (string) (data_get($resp, 'msg') ?: 'create payout failed');

            Log::channel('xepay_withdraw_create')->error('[XEPAY] create payout failed', [
                'txid' => $orderId,
                'resp' => $resp,
            ]);

            return $return;
        }

        $provider = (array) data_get($resp, 'data', []);

        app('Gametech\\Core\\Repositories\\CheckCaseRepository')->create([
            'method' => 2,
            'bank_code' => $bank->banks,
            'txid' => $orderId,
            'amount' => $amount,
            'payamount' => $amount,
            'username' => $member->user_name,
            'name' => $member->name,
            'detail' => (string) (data_get($provider, 'oid', '') ?: $orderId),
            'qrcode' => '',
            'status' => 'pending',
            'user_create' => $member->name,
            'user_update' => $member->name,
        ]);

        $order->status = 1;
        $order->status_withdraw = 'A';
        $order->remark_admin = (string) $order->remark_admin . ' กำลังทำรายการถอนเงินออกจาก XEPay โอนเข้าบัญชี ' . ($order->memberBank?->account_name ?? $member->name) . ' เลขที่บัญชี ' . ($order->memberBank?->account_no ?? $member->acc_no) . ' ธนาคาร ' . ($order->memberBank?->bank?->shortcode ?? $member->bank?->shortcode ?? '-') . ' จำนวน ' . $amount;
        $order->save();

        $return['complete'] = true;
        $return['success'] = 'COMPLETE';
        $return['msg'] = 'สร้างรายการถอน XEPay สำเร็จ';

        return $return;
    }
}
