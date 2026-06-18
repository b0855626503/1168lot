<?php

declare(strict_types=1);

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\FlashPay;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PaymentOutFlashPay implements ShouldQueue
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
        $api = new FlashPay;

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

        if (! $order) {
            $return['success'] = 'NOTFOUND';
            $return['msg'] = 'ไม่พบรายการถอน';

            return $return;
        }

        $bank = app('Gametech\\Payment\\Repositories\\BankAccountRepository')->getAccountOutOne($order->account_code);
        if (! $bank) {
            $return['success'] = 'NOBANK';
            $return['msg'] = 'ไม่พบบัญชีถอนออกของระบบ';

            return $return;
        }

        $min = (float) config('flashpay.min_withdraw', 1);
        if ($min > 0 && (float) $order->amount < $min) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = 'ยอดเงินถอนออโต้ ขั้นต่ำ '.$min.' บาท';

            return $return;
        }

        if ($order->status_withdraw !== 'W') {
            $return['success'] = 'NOTWAIT';
            $return['msg'] = 'รายการนี้ กำลังอยู่ระหว่างประมวลผล';

            return $return;
        }

        // txid: FWIT + 6-digit order_code + YmdHis = 24 chars, alphanumeric
        $txid = 'FWIT'.str_pad((string) $order->code, 6, '0', STR_PAD_LEFT).date('YmdHis');
        $order->txid = $txid;
        $order->save();

        $member = app('Gametech\\Member\\Repositories\\MemberRepository')->find($order->member_code);
        if (! $member) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = 'ไม่พบสมาชิก';

            return $return;
        }

        $amount = (float) ($order['amount'] - $order['fee']);

        $memberBankCode = $this->resolveMemberBankCode($member);
        $memberAccountNo = preg_replace('/\D+/', '', (string) data_get($member, 'acc_no', ''));
        $memberAccountName = trim((string) (data_get($member, 'acc_name') ?: data_get($member, 'name')));

        if ($memberBankCode === '' || $memberAccountNo === '' || $memberAccountName === '') {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = 'ข้อมูลบัญชีสมาชิกไม่ครบถ้วน';

            return $return;
        }

        $idempotencyKey = (string) \Str::uuid();

        // FlashPay withdraw request format: destination wrapper, no notify_url
        // Idempotency-Key passed via extra headers
        $payload = [
            'amount' => (float) number_format($amount, 2, '.', ''),
            'destination' => [
                'bankCode' => $memberBankCode,
                'accountNumber' => $memberAccountNo,
                'accountName' => $memberAccountName,
            ],
            '__idempotency_key' => $idempotencyKey,
        ];

        if ($order->member_id) {
            $payload['memberId'] = (string) $order->member_id;
        }

        Log::channel('flashpay_withdraw_create')->info('[FLASHPAY] PaymentOut create withdrawal start', [
            'txid' => $txid,
            'member_code' => (int) $member->code,
            'withdraw_code' => (int) $order->code,
            'amount' => $amount,
            'idempotency_key' => $idempotencyKey,
        ]);

        // Gate สุดท้ายก่อนยิง API: กันสถานะเปลี่ยนระหว่างรอคิว
        $order->refresh();
        if ($order->status_withdraw !== 'W') {
            $return['success'] = 'NOTWAIT';
            $return['msg'] = 'รายการนี้ กำลังอยู่ระหว่างประมวลผล';

            return $return;
        }

        $resp = $api->createWithdrawal($payload);

        if (! data_get($resp, 'success')) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = (string) (data_get($resp, 'msg') ?: 'create withdrawal failed');

            Log::channel('flashpay_withdraw_create')->error('[FLASHPAY] PaymentOut create withdrawal failed', [
                'txid' => $txid,
                'resp' => $resp,
            ]);

            return $return;
        }

        $request = (array) data_get($resp, 'data.request', []);
        $requestId = (string) data_get($request, 'id', '');
        $requestRef = (string) data_get($request, 'requestRef', '');

        // Create check_case for tracking
        app('Gametech\\Core\\Repositories\\CheckCaseRepository')->create([
            'method' => 2,
            'bank_code' => $bank->banks,
            'txid' => $txid,
            'amount' => $amount,
            'payamount' => $amount,
            'username' => $member->user_name,
            'name' => $member->name,
            'detail' => ($requestRef !== '' ? $requestRef : $txid),
            'qrcode' => '',
            'status' => 'pending',
            'user_create' => $member->name,
            'user_update' => $member->name,
        ]);

        $order->status = 1;
        $order->status_withdraw = 'A';
        $order->remark_admin = $order->remark_admin.' กำลังทำรายการถอนเงินออกจาก FlashPay โอนเข้าบัญชี '.$member->name.' เลขที่บัญชี '.$member->acc_no.' ธนาคาร '.$memberBankCode.' จำนวน '.$amount;
        $order->save();

        $return['complete'] = true;
        $return['success'] = 'COMPLETE';
        $return['msg'] = 'สร้างรายการถอน FlashPay สำเร็จ';

        Log::channel('flashpay_withdraw_create')->info('[FLASHPAY] PaymentOut withdrawal created', [
            'txid' => $txid,
            'requestRef' => $requestRef,
            'withdraw_code' => (int) $order->code,
        ]);

        return $return;
    }

    /**
     * Map system bank shortcode → FlashPay bank code
     */
    private function resolveMemberBankCode($member): string
    {
        $map = (array) config('flashpay.bank_code_map', []);

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

            if (isset($map[$value])) {
                return (string) $map[$value];
            }

            return $value;
        }

        return '';
    }
}
