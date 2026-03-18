<?php

namespace Gametech\Auto\Jobs;

use Gametech\Payment\Libraries\AutoTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PaymentOutAutoTransfer implements ShouldQueue
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
        $api = new AutoTransfer;

        $return = [
            'complete' => false,
            'success'  => 'NORMAL',
            'msg'      => 'อนุมัติรายการเรียบร้อยแล้ว (รายการทั่วไป)',
        ];

        $config = core()->getConfigData();
        $id = $this->id;

        if ($config->seamless === 'Y') {
            $order = app('Gametech\Payment\Repositories\WithdrawSeamlessRepository')->find($id);
        } else {
            $order = app('Gametech\Payment\Repositories\WithdrawRepository')->find($id);
        }

        if (! $order) {
            $return['complete'] = false;
            $return['success']  = 'NOTFOUND';
            $return['msg']      = 'ไม่พบรายการถอน';
            return $return;
        }

        // ต้องอยู่สถานะรอเท่านั้น
        if (($order->status_withdraw ?? null) !== 'W') {
            $return['success'] = 'NOTWAIT';
            $return['msg']     = 'รายการนี้ กำลังอยู่ระหว่างประมวลผล';
            return $return;
        }

        // ตรวจธนาคารฝั่ง “บัญชีที่ใช้โอนออก”
        $accountCode = $order->account_code ?? null;
        if (! $accountCode) {
            $return['complete'] = false;
            $return['success']  = 'NOTFOUND';
            $return['msg']      = 'ไม่พบข้อมูล บช ถอน';
            return $return;
        }

        $bank_account = app('Gametech\Payment\Repositories\BankAccountRepository')->getAccountOutOne($accountCode);
        if (! $bank_account) {
            $return['complete'] = true;
            $return['success']  = 'NORMAL';
            $return['msg']      = 'อนุมัติรายการเรียบร้อยแล้ว (รายการทั่วไป)';
            return $return;
        }

        // สร้าง txid ใหม่ทุกครั้งที่เริ่มทำออโต้
        $orderId = 'AUTOWRD-' . str_pad((string) $order->code, 6, '0', STR_PAD_LEFT) . '-' . date('YmdHis');
        $order->txid = $orderId;
        $order->save();

        $transactionId = $order->txid;

        $member = app('Gametech\Member\Repositories\MemberRepository')->find($order->member_code);
        if (! $member) {
            $return['success'] = 'NO_MEMBER';
            $return['msg']     = 'ไม่พบสมาชิกของรายการนี้';
            return $return;
        }

        // amount = amount - fee (กันติดลบ)
        $amount = (float) ($order->amount ?? 0) - (float) ($order->fee ?? 0);
        if ($amount <= 0) {
            $return['success'] = 'INVALID_AMOUNT';
            $return['msg']     = 'ยอดถอนสุทธิไม่ถูกต้อง';
            return $return;
        }

        // ฝั่งสมาชิก: map bank_code -> shortcode/provider bank name
        $bankName = $api->Banks($member->bank_code);
        if ($bankName === false) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg']     = 'บัญชีธนาคารของสมาชิก ไม่รองรับการโอน ในขณะนี้';
            return $return;
        }

        // ฝั่งบัญชีโอนออก: map bank code -> provider bank name
        $payerBank = $api->Banks($bank_account->banks);
        if ($payerBank === false) {
            $return['success'] = 'FAIL_AUTO';
            $return['msg']     = 'บัญชีธนาคารที่ใช้โอนออก ไม่รองรับการโอน ในขณะนี้';
            return $return;
        }

        $accNo = trim((string) ($member->acc_no ?? ''));
        if ($accNo === '') {
            $return['success'] = 'NO_MEMBER_ACC';
            $return['msg']     = 'ไม่พบเลขบัญชีธนาคารของสมาชิก';
            return $return;
        }

        // callback url
        try {
            $callbackUrl = route('api.autotransfer.withdraw.callback');
        } catch (\Throwable $e) {
            Log::channel('autotransfer_api')->error('PaymentOutAutoTransfer: callback route error', [
                'error' => $e->getMessage(),
            ]);

            $return['success'] = 'NO_CALLBACK';
            $return['msg']     = 'ระบบ callback ไม่พร้อมใช้งาน';
            return $return;
        }

        $param = [
            'session'       => trim((string) $transactionId),
            'amount'        => (float) $amount,
            'payee_account' => trim((string) $accNo),                 // บัญชีปลายทาง (สมาชิก)
            'payee_bank'    => trim((string) $bankName),              // ธนาคารปลายทาง (สมาชิก)
            'payer_account' => trim((string) ($bank_account->acc_no ?? '')), // บัญชีต้นทาง (บัญชีโอนออก)
            'payer_bank'    => trim((string) $payerBank),             // ธนาคารต้นทาง
            'callback_url'  => trim((string) $callbackUrl),
        ];

        $response = $api->withdrawTransfer($param);

        Log::channel('autotransfer_api')->warning('PaymentOutAutoTransfer: withdraw', [
            'response' => $response,
            'param' => $param,
        ]);

        $statusType = data_get($response, 'data.status.type');
        $statusCode = data_get($response, 'data.status.code');
        $message    = data_get($response, 'data.status.message', '');
        $transaction_id = data_get($response, 'data.data.transaction_id'); // provider transaction_id (ถ้ามี)

        if ($statusType === 'processing') {
            // ✅ สำคัญ: ต้องอัปเดต order ให้พร้อมรับ callback "ก่อน" ทำงานอื่น
            try {
                if (! $transaction_id) {
                    $return['success'] = 'FAIL_AUTO';
                    $return['msg']     = 'ถอนเงินไม่สำเร็จ (ไม่พบ transaction_id)';
                    Log::channel('autotransfer_api')->warning('PaymentOutAutoTransfer: processing but missing transaction_id', [
                        'withdraw_code' => $order->code,
                        'response' => $response,
                        'param' => $param,
                    ]);
                    return $return;
                }

                // อัปเดตสถานะหลักก่อน (กัน callback มาไวแล้วหาไม่เจอ)
                $order->transaction_id = $transaction_id;
                $order->status = 1;
                $order->status_withdraw = 'A';

                $remarkAdmin = (string) ($order->remark_admin ?? '');
                $remarkAdmin .= ' กำลัง ทำรายการถอนเงินออกจาก ' . $payerBank
                    . ' ( ' . (($bank_account->acc_no ?? '-') ?: '-') . ' )'
                    . ' โอนเข้าบัญชี ' . (($member->name ?? '-') ?: '-')
                    . ' เลขที่บัญชี ' . (($member->acc_no ?? '-') ?: '-')
                    . ' ธนาคาร ' . (optional($member->bank)->shortcode ?? '-')
                    . ' จำนวน ' . $amount;

                $order->remark_admin = $remarkAdmin;
                $order->save();

                // งานรอง: log เครดิต (ถ้าพัง ไม่ควรกระทบ state หลัก)
                try {
                    app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
                        'ip' => request()->ip(),
                        'credit_type' => 'W',
                        'balance_before' => 0,
                        'balance_after' => 0,
                        'credit' => 0,
                        'total' => 0,
                        'credit_bonus' => 0,
                        'credit_total' => 0,
                        'credit_before' => $amount,
                        'credit_after' => $amount,
                        'pro_code' => 0,
                        'bank_code' => 0,
                        'auto' => 'N',
                        'enable' => 'Y',
                        'user_create' => 'System Auto',
                        'user_update' => 'System Auto',
                        'refer_code' => $id,
                        'refer_table' => 'withdraws',
                        'remark' => 'รายการแจ้งถอนที่ ' . $order->code . ' / ไอดีที่ถอน : ' . $member->user_name . ' จำนวนเงิน ' . $amount . ' ถูกส่งถอนผ่าน ' . $payerBank . '-' . ($bank_account->acc_no ?? '-') . ' ' . $transactionId . ' [ ' . $transaction_id . ' ]',
                        'kind' => 'OTHER',
                        'amount' => $amount,
                        'amount_balance' => 0,
                        'withdraw_limit' => 0,
                        'withdraw_limit_amount' => 0,
                        'method' => 'D',
                        'member_code' => $member->code,
                        'user_name' => $member->user_name,
                        'emp_code' => 0,
                        'emp_name' => 'SYSTEM',
                    ]);
                } catch (\Throwable $e) {
                    Log::channel('autotransfer_api')->error('PaymentOutAutoTransfer: credit log failed (non-blocking)', [
                        'withdraw_code' => $order->code,
                        'error' => $e->getMessage(),
                    ]);
                }

                $return['complete'] = true;
                $return['success']  = 'COMPLETE';
                $return['msg']      = 'ระบบกำลังดำเนินการถอนเงิน';

                return $return;
            } catch (\Throwable $e) {
                // ถ้าพังตรงนี้ = state หลักอาจยังไม่ถูกเซฟ -> ต้อง log ให้หนัก
                Log::channel('autotransfer_api')->error('PaymentOutAutoTransfer: processing handler failed', [
                    'withdraw_code' => $order->code,
                    'error' => $e->getMessage(),
                    'response' => $response,
                    'param' => $param,
                ]);

                $return['success'] = 'FAIL_AUTO';
                $return['msg']     = 'ถอนเงินไม่สำเร็จ (ระบบบันทึกสถานะไม่สำเร็จ)';
                return $return;
            }
        }

        $return['success'] = 'FAIL_AUTO';
        $return['msg']     = $message ?: 'ถอนเงินไม่สำเร็จ';

        Log::channel('autotransfer_api')->warning('PaymentOutAutoTransfer: withdraw failed', [
            'withdraw_code' => $order->code,
            'status_code' => $statusCode,
            'message' => $message,
            'response' => $response,
            'param' => $param,
        ]);

        // (optional) ถ้า provider ส่ง transaction_id กลับมาแม้ fail และอยาก trace ให้ครบ:
        // ไม่เปลี่ยน behavior เดิม (ไม่เปลี่ยน status) แค่เก็บ id ไว้
        if ($transaction_id && empty($order->transaction_id)) {
            try {
                $order->transaction_id = $transaction_id;
                $order->save();
            } catch (\Throwable $e) {
                Log::channel('autotransfer_api')->error('PaymentOutAutoTransfer: failed to store transaction_id on failure (non-blocking)', [
                    'withdraw_code' => $order->code,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $return;
    }
}