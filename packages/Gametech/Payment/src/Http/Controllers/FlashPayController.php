<?php

declare(strict_types=1);

namespace Gametech\Payment\Http\Controllers;

use App\Events\RealTimeNewMessage;
use Carbon\Carbon;
use Gametech\Auto\Jobs\UpdateBalanceFlashPay;
use Gametech\Core\Repositories\CheckCaseRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Libraries\FlashPay;
use Gametech\Payment\Repositories\BankAccountRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\BankRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FlashPayController extends AppBaseController
{
    public function __construct(
        private readonly CheckCaseRepository $repository,
        private readonly MemberRepository $memberRepository,
        private readonly BankRepository $bankRepository,
        private readonly BankPaymentRepository $bankPaymentRepository,
        private readonly BankAccountRepository $bankAccountRepository,
    ) {}

    /**
     * GET /api/{provider}/qrcode/{id} — JSON detail for a deposit record
     */
    public function index($id)
    {
        $data = $this->repository->findOneWhere(['detail' => $id])
            ?: $this->repository->findOneWhere(['txid' => $id]);

        if (! $data) {
            return response()->json([
                'success' => false,
                'msg' => 'ไม่พบรายการ',
            ]);
        }

        //        $authMember = auth()->guard('customer')->user();
        //        if ($authMember && (string) $data->username !== (string) $authMember->user_name) {
        //            return response()->json([
        //                'success' => false,
        //                'message' => 'ไม่มีสิทธิ์เข้าถึงรายการนี้',
        //            ], 403);
        //        }

        $member = null;
        if (! empty($data->username)) {
            $member = $this->memberRepository->findOneWhere(['user_name' => $data->username]);
        }

        // Convert QR URL to base64 data URI for inline display
        $qrBase64 = null;
        $qrUrl = $data->url ?? $data->qrcode ?? null;
        if ($qrUrl !== null && $qrUrl !== '') {
            try {
                $imageData = @file_get_contents($qrUrl);
                if ($imageData !== false) {
                    $qrBase64 = 'data:image/png;base64,'.base64_encode($imageData);
                }
            } catch (\Throwable $e) {
                // Fallback to raw URL if fetch fails
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'request_id' => $id,
                'txid' => (string) ($data->txid ?? ''),
                'status' => (string) ($data->status ?? ''),
                'amount' => (float) ($data->payamount ?? 0),
                'payamount' => (float) ($data->payamount ?? 0),
                'qrcode' => $qrBase64 ?? $data->qrcode ?? null,
                'qr_string' => $data->url ?? null,
                'expired_date' => ! empty($data->expired_date)
                    ? Carbon::parse($data->expired_date)->toDateTimeString()
                    : null,
                'member' => [
                    'user_name' => (string) ($member->user_name ?? $data->username ?? ''),
                    'name' => (string) ($member->name ?? ''),
                ],
            ],
        ]);
    }

    /**
     * POST /api/{provider}/deposit/create — สร้างรายการฝากเงิน (QR PromptPay)
     */
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $member = auth()->guard('customer')->user();
        if (! $member) {
            return response()->json([
                'success' => false,
                'msg' => 'unauthenticated',
            ], 401);
        }

        $amount = (float) $request->input('amount');
        $amountText = number_format($amount, 2, '.', '');

        $systemBankCode = (int) config('flashpay.system_bank_code', 318);

        $bankAccount = $this->bankAccountRepository->findOneWhere([
            'banks' => $systemBankCode,
            'bank_type' => 1,
            'enable' => 'Y',
            'status_auto' => 'Y',
        ]);

        if (! $bankAccount) {
            return response()->json([
                'success' => false,
                'msg' => __('app.topup.fail'),
            ]);
        }

        $min = (float) config('flashpay.min_deposit', 1);
        if ($amount < $min) {
            return response()->json([
                'success' => false,
                'msg' => __('app.topup.min_deposit', ['amount' => $min]),
            ]);
        }

        $memberBankCode = $this->resolveMemberBankCode($member);
        $memberAccountNo = preg_replace('/\D+/', '', (string) data_get($member, 'acc_no', ''));
        $memberAccountName = trim((string) (data_get($member, 'acc_name') ?: data_get($member, 'name')));

        if ($memberBankCode === '' || $memberAccountNo === '' || $memberAccountName === '') {
            return response()->json([
                'success' => false,
                'msg' => 'ข้อมูลบัญชีสมาชิกไม่ครบถ้วน',
            ]);
        }

        // txid: FDEP + 6-digit member_code + YmdHis = 24 chars, alphanumeric
        $txid = 'FDEP'.str_pad((string) $member->code, 6, '0', STR_PAD_LEFT).date('YmdHis');

        $api = new FlashPay;

        // FlashPay uses orderId (not merchant_order_id) + metadata wrapper
        $payload = [
            'amount' => (float) $amountText,
            'orderId' => $txid,
            'metadata' => [
                'senderBankCode' => $memberBankCode,
                'senderAccountNumber' => $memberAccountNo,
                'memberName' => $memberAccountName,
                'senderAccountLast4' => substr($memberAccountNo, -4),
            ],
        ];

        if ($request->filled('description')) {
            $payload['description'] = $request->input('description');
        }

        if ($request->filled('customer_id')) {
            $payload['metadata']['customerId'] = $request->input('customer_id');
        }

        Log::channel('flashpay_deposit_create')->info('[FLASHPAY] create payment start', [
            'txid' => $txid,
            'member_code' => (int) $member->code,
            'amount' => $amountText,
        ]);

        $resp = $api->createFlexPayment($payload);

        Log::channel('flashpay_deposit_create')->info('[FLASHPAY] create payment response', [
            'success' => data_get($resp, 'success'),
            'code' => data_get($resp, 'code'),
            'msg' => data_get($resp, 'msg'),
        ]);

        if (! data_get($resp, 'success')) {
            Log::channel('flashpay_deposit_create')->error('[FLASHPAY] create payment failed', [
                'txid' => $txid,
                'resp' => $resp,
            ]);

            return response()->json([
                'success' => false,
                'msg' => (string) data_get($resp, 'msg', 'create payment failed'),
            ]);
        }

        $transaction = (array) data_get($resp, 'data.transaction', []);
        $paymentAccounts = (array) data_get($resp, 'data.paymentAccounts', []);

        $transactionId = (string) data_get($transaction, 'id', '');
        $qrCode = (string) data_get($transaction, 'qrCode', '');
        $qrExpiry = (string) data_get($transaction, 'qrExpiry', '');
        $fee = (float) data_get($transaction, 'fee', 2);
        $netAmount = (float) data_get($transaction, 'netAmount', $amount);
        $payAmount = (float) data_get($transaction, 'amount', $amount);

        if ($qrCode === '') {
            Log::channel('flashpay_deposit_create')->error('[FLASHPAY] qrCode missing in response', [
                'txid' => $txid,
                'transaction' => $transaction,
            ]);

            return response()->json([
                'success' => false,
                'msg' => 'qrCode missing in response',
            ], 500);
        }

        $expiredDate = $qrExpiry !== ''
            ? Carbon::parse($qrExpiry)->setTimezone(config('app.timezone'))
            : now()->addMinutes(30);

        try {
            UpdateBalanceFlashPay::dispatch()->delay(5)->onQueue('topup');

            $this->repository->create([
                'bank_code' => $bankAccount->banks,
                'method' => 1,
                'txid' => $txid,
                'detail' => ($transactionId !== '' ? $transactionId : $txid),
                'amount' => $amountText,
                'payamount' => (string) $payAmount,
                'fee' => (string) $fee,
                'username' => trim((string) $member->user_name),
                'name' => (string) $member->name,
                'url' => $qrCode,
                'qrcode' => $qrCode,
                'status' => 'pending',
                'expired_date' => $expiredDate,
                'user_create' => (string) $member->name,
                'user_update' => (string) $member->name,
            ]);
        } catch (\Throwable $e) {
            Log::channel('flashpay_deposit_create')->error('[FLASHPAY] create check_case failed', [
                'txid' => $txid,
                'error' => $e->getMessage(),
                'transaction' => $transaction,
            ]);

            return response()->json([
                'success' => false,
                'msg' => 'ไม่สามารถสร้างรายการได้',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'msg' => __('app.topup.create'),
            'url' => route('frontend.api.v1.flashpay.index', ['id' => ($transactionId !== '' ? $transactionId : $txid)]),
        ]);

        //        return response()->json([
        //            'success' => true,
        //            'data' => [
        //                'txid' => $txid,
        //                'transaction_id' => $transactionId,
        //                'qrcode' => $qrCode,
        //                'qr_expiry' => $expiredDate->toIso8601String(),
        //                'amount' => $amountText,
        //                'fee' => (string) $fee,
        //                'net_amount' => (string) $netAmount,
        //                'status' => 'pending',
        //                'url' => route('frontend.api.v1.flashpay.index', ['id' => ($transactionId !== '' ? $transactionId : $txid)]),
        //                'target' => 'self', // QR view — no redirect
        //            ],
        //        ]);
    }

    /**
     * POST /admin/{provider}/deposit/callback — FlashPay webhook (payment events)
     *
     * Events: payment.success / payment.failed / payment.expired
     * Security: X-Webhook-Signature (HMAC-SHA256) + mode guard
     * Idempotency: bank_payment dedup by transactionId
     * PAID always wins over FAILED/EXPIRED/CANCELLED (out-of-order)
     */
    public function deposit_callback(Request $request)
    {
        $payload = $request->all();

        Log::channel('flashpay_deposit_callback')->info('[FLASHPAY] Deposit callback', [
            'headers' => [
                'X-Webhook-Event' => $request->header('X-Webhook-Event'),
                'X-Webhook-Signature' => $request->header('X-Webhook-Signature', '(missing)'),
            ],
            'payload' => $payload,
        ]);

        $event = (string) ($payload['event'] ?? '');
        $data = (array) ($payload['data'] ?? []);

        // --- mode guard ---
        if (! str_starts_with($event, 'payment.')) {
            Log::channel('flashpay_deposit_callback')->info('[FLASHPAY] Non-payment event — skip', ['event' => $event]);

            return response()->json(['success' => true]);
        }

        // --- extract identifiers ---
        $orderId = (string) data_get($data, 'orderId', '');
        $transactionId = (string) data_get($data, 'transactionId', '');

        if ($orderId === '') {
            Log::channel('flashpay_deposit_callback')->warning('[FLASHPAY] Callback missing orderId');

            return response()->json(['success' => true]);
        }

        // --- find check_case by txid ---
        $case = $this->repository->findOneWhere(['txid' => $orderId]);

        if (! $case) {
            Log::channel('flashpay_deposit_callback')->warning('[FLASHPAY] check_case not found', [
                'orderId' => $orderId,
            ]);

            return response()->json(['success' => true]);
        }

        // --- verify callback signature ---
        if ((bool) config('flashpay.verify_callback_signature', true)) {
            $api = new FlashPay;
            if (! $api->verifyCallbackSignature($request)) {
                Log::channel('flashpay_deposit_callback')->warning('[FLASHPAY] Invalid signature', [
                    'orderId' => $orderId,
                ]);

                return response()->json(['success' => true]);
            }
        }

        // --- normalize status ---
        $api = new FlashPay;
        $incoming = $api->normalizeStatus((string) data_get($data, 'status', 'pending'));
        $amount = (float) (data_get($data, 'netAmount') ?: data_get($data, 'amount') ?: $case->payamount ?: $case->amount);
        $current = strtolower((string) $case->status);

        Log::channel('flashpay_deposit_callback')->info('[FLASHPAY] Callback processing', [
            'orderId' => $orderId,
            'transactionId' => $transactionId,
            'event' => $event,
            'incoming' => $incoming,
            'current' => $current,
            'amount' => $amount,
        ]);

        // --- out-of-order guard: PAID always wins ---
        if ($incoming === 'completed') {
            if ($current !== 'completed') {
                $this->repository->update(['status' => 'completed'], $case->code);
            }
        } elseif ($incoming === 'cancelled') {
            if ($current === 'pending') {
                $this->repository->update(['status' => 'cancelled'], $case->code);
            }
        } elseif ($incoming === 'failed') {
            if (! in_array($current, ['completed', 'failed'], true)) {
                $this->repository->update(['status' => 'failed'], $case->code);
            }
        } elseif ($incoming === 'expired') {
            if ($current === 'pending') {
                $this->repository->update(['status' => 'expired'], $case->code);
            }
        } elseif ($incoming !== 'pending') {
            if (! in_array($current, ['completed', 'failed'], true)) {
                $this->repository->update(['status' => $incoming], $case->code);
            }
        }

        // --- ไม่ใช่ completed → จบ ---
        if ($incoming !== 'completed') {
            return response()->json(['success' => true]);
        }

        // === PAID: สร้าง bank_payment (idempotent) ===
        UpdateBalanceFlashPay::dispatch()->delay(5)->onQueue('topup');

        $member = $this->memberRepository->findOneWhere(['user_name' => $case->username]);
        if (! $member) {
            Log::channel('flashpay_deposit_callback')->warning('[FLASHPAY] Member not found', [
                'username' => $case->username,
            ]);

            return response()->json(['success' => true]);
        }

        $systemBankCode = (int) config('flashpay.system_bank_code', 318);
        $bankAccount = $this->bankAccountRepository->findOneWhere([
            'banks' => $systemBankCode,
            'bank_type' => 1,
            'enable' => 'Y',
            'status_auto' => 'Y',
        ]);

        if (! $bankAccount) {
            Log::channel('flashpay_deposit_callback')->warning('[FLASHPAY] Bank account not found');

            return response()->json(['success' => true]);
        }

        // Dedup by txid
        $check = $this->bankPaymentRepository->findOneWhere(['txid' => $orderId]);
        if ($check) {
            Log::channel('flashpay_deposit_callback')->info('[FLASHPAY] Duplicate callback ignored (bank_payment exists)', [
                'txid' => $orderId,
            ]);

            return response()->json(['success' => true]);
        }

        $bank = $this->bankRepository->find($bankAccount->banks);
        $detail = ' REF ID : '.($transactionId !== '' ? $transactionId : '-');
        $hash = md5($bankAccount->code.$amount.$detail);
        $datenow = now()->toDateTimeString();

        $this->bankPaymentRepository->create([
            'bank' => strtolower($bank->shortcode.'_'.$bankAccount->acc_no),
            'detail' => $detail.' จำนวน '.$amount,
            'account_code' => $bankAccount->code,
            'autocheck' => 'W',
            'bankstatus' => 1,
            'bank_name' => $bank->shortcode,
            'bank_time' => $datenow,
            'channel' => 'FlashPay',
            'value' => $amount,
            'tx_hash' => $hash,
            'txid' => $orderId,
            'status' => 0,
            'ip_admin' => request()->ip(),
            'member_topup' => $member->code,
            'remark_admin' => '',
            'emp_topup' => 0,
            'user_create' => 'รอระบบเติมอัตโนมัติ ทำรายการฝากเงินโดย FlashPay',
            'create_by' => 'SYSAUTO',
        ]);

        Log::channel('flashpay_deposit_callback')->info('[FLASHPAY] bank_payment created', [
            'orderId' => $orderId,
            'transactionId' => $transactionId,
            'amount' => $amount,
            'member_code' => $member->code,
        ]);

        return response()->json(['success' => true]);
    }

    /**public function withdraw_callback(Request $request)
    {
        $payload = $request->all();

        Log::channel('flashpay_withdraw_callback')->info('[FLASHPAY] Withdraw callback', [
            'headers' => [
                'X-Webhook-Event' => $request->header('X-Webhook-Event'),
                'X-Webhook-Signature' => $request->header('X-Webhook-Signature', '(missing)'),
            ],
            'payload' => $payload,
        ]);

        $event = (string) ($payload['event'] ?? '');
        $data = (array) ($payload['data'] ?? []);

        // --- mode guard: ต้องเป็น withdrawal.* event เท่านั้น ---
        if (! str_starts_with($event, 'withdrawal.')) {
            Log::channel('flashpay_withdraw_callback')->info('[FLASHPAY] Non-withdrawal event — skip', ['event' => $event]);

            return response()->json(['success' => true]);
        }

        // --- verify callback signature ---
        if (config('flashpay.verify_callback_signature', true)) {
            $api = new FlashPay;
            if (! $api->verifyCallbackSignature($request)) {
                Log::channel('flashpay_withdraw_callback')->warning('[FLASHPAY] Invalid withdraw callback signature');

                return response()->json(['success' => true]);
            }
        }

        // FlashPay uses requestRef as the correlation ID (set to txid in PaymentOutFlashPay)
        $requestRef = (string) data_get($data, 'requestRef', '');
        $rawStatus = (string) data_get($data, 'status', '');
        $api = new FlashPay;
        $incoming = $api->normalizeStatus($rawStatus);

        Log::channel('flashpay_withdraw_callback')->info('[FLASHPAY] Withdraw callback processing', [
            'requestRef' => $requestRef,
            'event' => $event,
            'raw_status' => $rawStatus,
            'normalized' => $incoming,
        ]);

        if ($requestRef === '') {
            Log::channel('flashpay_withdraw_callback')->warning('[FLASHPAY] Callback missing requestRef');

            return response()->json(['success' => true]);
        }

        // Update check_case (method=2 = withdraw, created by PaymentOutFlashPay)
        $case = $this->repository->findOneWhere(['txid' => $requestRef]);
        if ($case) {
            $current = strtolower((string) $case->status);
            if ($incoming !== 'pending' && ! in_array($current, ['completed', 'failed'], true)) {
                $this->repository->update(['status' => $incoming], $case->code);
            }
        }

        // ถ้า status ที่ normalize ไม่ได้ → ไม่ต้องทำอะไร
        if ($incoming === 'pending') {
            return response()->json(['success' => true]);
        }

        // หา withdraw record (status_withdraw = A = รอผลจาก Provider)
        $config = $this->getCoreConfig();

        if ($config->seamless === 'Y') {
            $order = app('Gametech\\Payment\\Repositories\\WithdrawSeamlessRepository')
                ->findOneWhere(['txid' => $requestRef, 'status_withdraw' => 'A']);
        } else {
            $order = app('Gametech\\Payment\\Repositories\\WithdrawRepository')
                ->findOneWhere(['txid' => $requestRef, 'status_withdraw' => 'A']);
        }

        if (! $order) {
            Log::channel('flashpay_withdraw_callback')->info('[FLASHPAY] No active withdraw order for callback', [
                'requestRef' => $requestRef,
            ]);

            return response()->json(['success' => true]);
        }

        $amount = (float) $order['amount'];
        $refId = $requestRef;

        // =========================
        // APPROVED → ปิดรายการถอนสำเร็จ
        // =========================
        if ($incoming === 'completed') {
            UpdateBalanceFlashPay::dispatch()->delay(5)->onQueue('topup');

            $order->remark_admin = '[ Ref ID : '.$refId.' ] โอนให้ลูกค้าแล้ว (FlashPay)';
            $order->status = 1;
            $order->status_withdraw = 'C';
            $order->save();

            app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->create([
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
                'refer_code' => $order->code,
                'refer_table' => 'withdraws',
                'remark' => 'รายการแจ้งถอนที่ '.$order->code.' / ไอดีที่ถอน : '.$order->member_user.' จำนวนเงิน '.$amount.' โอนเงินให้ลูกค้าแล้ว FlashPay '.$requestRef,
                'kind' => 'OTHER',
                'amount' => $amount,
                'amount_balance' => 0,
                'withdraw_limit' => 0,
                'withdraw_limit_amount' => 0,
                'method' => 'D',
                'member_code' => $order->member_code,
                'user_name' => $order->member_user,
                'emp_code' => 0,
                'emp_name' => 'SYSTEM',
            ]);

            broadcast(new RealTimeNewMessage(
                'FlashPay '.$requestRef.' โอนเงินให้ลูกค้าแล้ว ID : '.$order->member_user.' จำนวนเงิน '.$amount.' รายการแจ้งถอนที่ '.$order->code,
                [
                    'ui' => 'toast',
                    'as' => 'RealTime.Message.All',
                    'toast' => [
                        'className' => 'gt-toast gt-toast-success',
                        'duration' => 30000,
                        'gravity' => 'top',
                        'position' => 'right',
                        'avatar' => '/assets/admin/icons/alert.webp',
                    ],
                ]
            ));

            Log::channel('flashpay_withdraw_callback')->info('[FLASHPAY] Withdraw APPROVED — completed', [
                'requestRef' => $requestRef,
                'amount' => $amount,
            ]);

            return response()->json(['success' => true]);
        }

        // =========================
        // REJECTED → คืนยอด
        // =========================
        if ($config->seamless === 'Y') {
            $rollbackData = [
                'refer_code' => $order->code,
                'refer_table' => 'withdraws',
                'remark' => 'คืนยอดจากการถอน '.$requestRef.' (FlashPay REJECTED)',
                'kind' => 'ROLLBACK',
                'amount' => $amount,
                'amount_balance' => $order->amount_balance,
                'withdraw_limit' => $order->amount_limit,
                'withdraw_limit_amount' => $order->amount_limit_rate,
                'method' => 'D',
                'member_code' => $order->member_code,
                'emp_code' => 0,
                'emp_name' => 'SYSTEM',
            ];
            $response = app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->setWalletSeamlessWithdraw($rollbackData);
        } else {
            $rollbackData = [
                'refer_code' => $order->code,
                'refer_table' => 'withdraws',
                'remark' => 'คืนยอดจากการถอน '.$requestRef.' (FlashPay REJECTED)',
                'kind' => 'ROLLBACK',
                'amount' => $amount,
                'amount_balance' => $order->amount_balance,
                'withdraw_limit' => $order->amount_limit,
                'withdraw_limit_amount' => $order->amount_limit_rate,
                'pro_code' => $order->pro_code,
                'pro_name' => $order->pro_name,
                'method' => 'D',
                'member_code' => $order->member_code,
                'emp_code' => 0,
                'emp_name' => 'SYSTEM',
            ];
            $response = app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->setWalletSingleWithdraw($rollbackData);
        }

        if ($response) {
            broadcast(new RealTimeNewMessage(
                'FlashPay ยกเลิกรายการแจ้งถอน ของ ID '.$order->member_user.' จำนวนเงิน '.$amount.' Ref ID '.$refId.' ระบบคืนยอดให้ลูกค้าแล้ว (REJECTED)',
                [
                    'ui' => 'toast',
                    'as' => 'RealTime.Message.All',
                    'toast' => [
                        'className' => 'gt-toast gt-toast-error',
                        'duration' => 0,
                        'gravity' => 'top',
                        'position' => 'right',
                        'avatar' => '/assets/admin/icons/alert.webp',
                    ],
                ]
            ));

            $order->remark_admin = '[ Ref ID : '.$refId.' ] โอนไม่สำเร็จ และ ระบบคืนยอดแล้ว (FlashPay REJECTED)';
        } else {
            broadcast(new RealTimeNewMessage(
                'FlashPay ยกเลิกรายการแจ้งถอน ของ ID '.$order->member_user.' จำนวนเงิน '.$amount.' Ref ID '.$refId.' ระบบคืนยอด ให้ลูกค้าไม่ได้ (REJECTED)',
                [
                    'ui' => 'toast',
                    'as' => 'RealTime.Message.All',
                    'toast' => [
                        'className' => 'gt-toast gt-toast-error',
                        'duration' => 0,
                        'gravity' => 'top',
                        'position' => 'right',
                        'avatar' => '/assets/admin/icons/alert.webp',
                    ],
                ]
            ));

            $order->remark_admin = '[ Ref ID : '.$refId.' ] โอนไม่สำเร็จ โปรดคืนยอดให้ลูกค้าเอง ระบบคืนไม่ได้ (FlashPay REJECTED)';
        }

        $order->status_withdraw = 'R';
        $order->status = 2;
        $order->save();

        Log::channel('flashpay_withdraw_callback')->info('[FLASHPAY] Withdraw REJECTED — rolled back', [
            'requestRef' => $requestRef,
            'amount' => $amount,
            'rollback_success' => (bool) $response,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * PATCH /api/{provider}/deposit/expire/{txid} — Mark transaction expired
     */
    public function expire($txid)
    {
        $case = $this->repository->findOneWhere(['txid' => $txid]);

        if ($case && $case->status === 'pending') {
            $this->repository->update([
                'status' => 'expired',
            ], $case->id);
        }

        return response()->json(['success' => true]);
    }

    /**
     * GET /api/{provider}/deposit/status/{txid} — Return current status
     */
    public function checkStatus($txid)
    {
        $case = $this->repository->findOneWhere(['txid' => $txid]);

        if (! $case) {
            return response()->json([
                'success' => false,
                'status' => 'unknown',
                'msg' => 'ไม่พบรายการ',
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => $case->status,
            'txid' => $case->txid,
            'amount' => $case->amount,
            'payamount' => $case->payamount,
            'fee' => $case->fee,
            'qrcode' => $case->qrcode,
            'expired_date' => ! empty($case->expired_date)
                ? Carbon::parse($case->expired_date)->toDateTimeString()
                : null,
        ]);
    }

    /**
     * Map system bank shortcode → FlashPay bank code
     *
     * Uses bank_code_map config (reverse of resolveMemberBankCode)
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

    /**
     * Reverse map: FlashPay bank code → system shortcode
     */
    private function reverseMapBankCode(string $providerCode): string
    {
        $map = (array) config('flashpay.bank_code_map', []);
        $flipped = array_flip($map);

        return $flipped[$providerCode] ?? $providerCode;
    }
}
