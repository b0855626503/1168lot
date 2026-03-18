<?php

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\SmkPay;
use Gametech\Payment\Models\PaymentProviderAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PaymentOutSmkPay implements ShouldQueue
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
        $api = new SmkPay();

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

        $min = (float) config('smkpay.min_withdraw', 0);
        if ($min > 0 && (float) $order->amount < $min) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = 'ยอดเงินถอนออโต้ ขั้นต่ำ ' . $min . ' บาท';

            return $return;
        }

        // ✅ Gate แรก: ต้องอยู่สถานะรอถอนเท่านั้น
        if ($order->status_withdraw != 'W') {
            $return['success'] = 'NOTWAIT';
            $return['msg'] = 'รายการนี้ กำลังอยู่ระหว่างประมวลผล';

            return $return;
        }

        // txid ภายในระบบ (merchant_ref_id)
        $order_id = 'SWRD-' . str_pad((string) $order->code, 6, '0', STR_PAD_LEFT) . '-' . date('YmdHis');
        $order->txid = $order_id;
        $order->save();

        $transactionId = (string) $order->txid;

        $member = app('Gametech\\Member\\Repositories\\MemberRepository')->find($order->member_code);
        if (! $member) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = 'ไม่พบสมาชิก';

            return $return;
        }

        $amount = (float) ($order['amount'] - $order['fee']);

        /**
         * ✅ Flow ที่ถูกต้องตาม step:
         * 1) เช็คก่อนว่ามี customer_id/customer_account_id ครบไหม
         * 2) ถ้าขาดค่อยยิง ensure
         * 3) ensure เสร็จเช็คข้อมูลอีกครั้ง (ครั้งเดียว) — ถ้ายังขาด ห้ามไปต่อ
         * 4) ก่อนยิง payout re-check เฉพาะ "สถานะ order" (กันคิวรอนานแล้วโดนเปลี่ยนสถานะ)
         */
        $ppa = PaymentProviderAccount::query()
            ->where('provider', 'smkpay')
            ->where('member_code', (int) $member->code)
            ->first();

        if (! $ppa || empty($ppa->customer_id) || empty($ppa->customer_account_id)) {
            $ensure = app('Gametech\\Payment\\Http\\Controllers\\SmkPayController')->ensureProviderAccountUpToDate($member);

            if (! data_get($ensure, 'success')) {
                Log::channel('smkpay_withdraw_create')->error('[SMKPAY] ensureProviderAccountUpToDate failed', [
                    'member_code' => (int) $member->code,
                    'txid' => $transactionId,
                    'ensure' => $ensure,
                ]);

                $return['success'] = 'FAIL_AUTO';
                $return['msg'] = 'เตรียมข้อมูลบัญชี SMKPay ไม่สำเร็จ: ' . (string) data_get($ensure, 'msg', 'unknown');

                return $return;
            }

            // ✅ re-fetch หลัง ensure (ยืนยันผลลัพธ์)
            $ppa = PaymentProviderAccount::query()
                ->where('provider', 'smkpay')
                ->where('member_code', (int) $member->code)
                ->first();

            if (! $ppa || empty($ppa->customer_id) || empty($ppa->customer_account_id)) {
                Log::channel('smkpay_withdraw_create')->error('[SMKPAY] provider account not ready for payout (after ensure)', [
                    'member_code' => (int) $member->code,
                    'ppa_exists' => (bool) $ppa,
                    'customer_id' => $ppa ? $ppa->customer_id : null,
                    'customer_account_id' => $ppa ? $ppa->customer_account_id : null,
                    'txid' => $transactionId,
                ]);

                $return['success'] = 'FAIL_AUTO';
                $return['msg'] = 'ไม่พบข้อมูล provider account ของสมาชิก (customer/account)';

                return $return;
            }
        }

        $payload = [
            'currency_code' => (string) ($ppa->currency_code ?: 'THB'),
            'customer_account_id' => (string) $ppa->customer_account_id,
            'customer_id' => (string) $ppa->customer_id,
            'notes' => 'Payout for withdraw #' . (string) $order->code,
            'merchant_ref_id' => trim($transactionId),
            'request_amount' => (float) $amount,
            'sender_type' => 'BANK_TRANSFER',
        ];

        Log::channel('smkpay_withdraw_create')->info('[SMKPAY] create payout start', [
            'txid' => $transactionId,
            'member_code' => (int) $member->code,
            'withdraw_code' => (int) $order->code,
            'amount' => $amount,
        ]);

        // ✅ Gate สุดท้ายก่อนยิง payout: กันสถานะเปลี่ยนระหว่างรอคิว
        $order->refresh();
        if ($order->status_withdraw != 'W') {
            $return['success'] = 'NOTWAIT';
            $return['msg'] = 'รายการนี้ กำลังอยู่ระหว่างประมวลผล';

            return $return;
        }

        $resp = $api->request('POST', '/v1/payouts', $payload);

        if (! data_get($resp, 'success')) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg'] = (string) (data_get($resp, 'msg') ?: 'create payout failed');

            Log::channel('smkpay_withdraw_create')->error('[SMKPAY] create payout failed', [
                'txid' => $transactionId,
                'resp' => $resp,
            ]);

            return $return;
        }

        $provider = (array) data_get($resp, 'data.data', []);

        // create check_case เหมือน OnPay
        app('Gametech\\Core\\Repositories\\CheckCaseRepository')->create([
            'method' => 2,
            'bank_code' => $bank->banks,
            'txid' => $order_id,
            'amount' => $amount,
            'payamount' => $amount,
            'username' => $member->user_name,
            'name' => $member->name,
            'detail' => (string) (data_get($provider, 'id', '') ?: $transactionId),
            'qrcode' => '',
            'status' => 'pending',
            'user_create' => $member->name,
            'user_update' => $member->name,
        ]);

        $order->status = 1;
        $order->status_withdraw = 'A';
        $order->remark_admin = $order->remark_admin . ' กำลังทำรายการถอนเงินออกจาก SMKPay โอนเข้าบัญชี ' . $member->name . ' เลขที่บัญชี ' . $member->acc_no . ' ธนาคาร ' . $member->bank->shortcode . ' จำนวน ' . $amount;
        $order->save();

        $return['complete'] = true;
        $return['success'] = 'COMPLETE';
        $return['msg'] = 'สร้างรายการถอน SMKPay สำเร็จ';

        return $return;
    }
}
