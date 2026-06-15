<?php

declare(strict_types=1);

namespace Gametech\Payment\Http\Controllers;

use App\Events\RealTimeNewMessage;
use Carbon\Carbon;
use Gametech\Auto\Jobs\UpdateBalanceWealthPay;
use Gametech\Core\Repositories\CheckCaseRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Libraries\WealthPay;
use Gametech\Payment\Repositories\BankAccountRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\BankRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WealthPayController extends AppBaseController
{
    protected $_config;

    protected $repository;
    protected $memberRepository;
    protected $bankRepository;
    protected $bankAccountRepository;
    protected $bankPaymentRepository;

    public function __construct(
        CheckCaseRepository $repository,
        MemberRepository $memberRepository,
        BankRepository $bankRepository,
        BankPaymentRepository $bankPaymentRepository,
        BankAccountRepository $bankAccountRepository
    ) {
        $this->_config = request('_config');

        $this->repository = $repository;
        $this->memberRepository = $memberRepository;
        $this->bankRepository = $bankRepository;
        $this->bankAccountRepository = $bankAccountRepository;
        $this->bankPaymentRepository = $bankPaymentRepository;
    }

    /**
     * คืนข้อมูลรายการฝากในรูปแบบ JSON (Frontend API v1)
     */
    public function index($id)
    {
        $data = $this->repository->findOneWhere(['detail' => $id]) ?: $this->repository->findOneWhere(['txid' => $id]);

        if (! $data) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบรายการฝากเงิน',
            ], 404);
        }

        $authMember = auth()->guard('customer')->user();
        if ($authMember && (string) $data->username !== (string) $authMember->user_name) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึงรายการนี้',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'request_id' => (string) $id,
                'txid' => (string) ($data->txid ?? ''),
                'status' => (string) ($data->status ?? ''),
                'amount' => (float) ($data->amount ?? 0),
                'payamount' => (float) ($data->payamount ?? 0),
                'upload_url' => $data->url ?? null,
                'expired_date' => ! empty($data->expired_date)
                    ? Carbon::parse($data->expired_date)->toDateTimeString()
                    : null,
            ],
        ]);
    }

    /**
     * Create Flex Payment (Wealthwave Flex API)
     *
     * รับ amount + optional redirect_url, payment_theme
     * member จาก auth
     * ยิง API สำเร็จค่อย create check_case
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

        $systemBankCode = (int) config('wealthpay.system_bank_code', 317);

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

        $min = (float) config('wealthpay.min_deposit', 100);
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

        $txid = 'WDEP'.str_pad((string) $member->code, 6, '0', STR_PAD_LEFT).date('YmdHis');

        $callbackUrl = (string) (config('wealthpay.deposit_callback_url') ?: route('api.wealthpay.deposit.callback'));

        $api = new WealthPay;

        $payload = [
            'merchant_order_id' => $txid,
            'amount' => $amountText,
            'bank' => $memberBankCode,
            'account_name' => $memberAccountName,
            'account_no' => $memberAccountNo,
            'notify_url' => $callbackUrl,
        ];

        if ($request->filled('redirect_url')) {
            $payload['redirect_url'] = $request->input('redirect_url');
        }

        if ($request->filled('payment_theme')) {
            $theme = strtolower(trim($request->input('payment_theme')));
            $validThemes = ['halo', 'pristine', 'blue', 'vault', 'sunset', 'obsidian', 'stack', 'sienna', 'mint', 'pulse'];
            if (in_array($theme, $validThemes, true)) {
                $payload['payment_theme'] = $theme;
            }
        }

        Log::channel('wealthpay_deposit_create')->info('[WEALTHPAY] create flex payment start', [
            'txid' => $txid,
            'member_code' => (int) $member->code,
            'masked_payload' => array_merge($payload, [
                'account_no' => preg_replace('/\d/', '*', substr($memberAccountNo, -4)).substr($memberAccountNo, -4),
                'account_name' => $this->maskName($memberAccountName),
                'notify_url' => $callbackUrl,
            ]),
        ]);

        $resp = $api->createFlexPayment($payload);

        Log::channel('wealthpay_deposit_create')->info('[WEALTHPAY] create flex payment response', [
            'success' => data_get($resp, 'success'),
            'code' => data_get($resp, 'code'),
            'msg' => data_get($resp, 'msg'),
        ]);

        if (! data_get($resp, 'success')) {
            if (data_get($resp, 'conflict') === true) {
                $pendingOrderId = (string) data_get($resp, 'data.data.pending_order_id', '');
                $pendingOrderId = $pendingOrderId !== '' ? $pendingOrderId : (string) data_get($resp, 'data.error.data.pending_order_id', '');

                Log::channel('wealthpay_deposit_create')->warning('[WEALTHPAY] duplicate pending order', [
                    'txid' => $txid,
                    'member_code' => (int) $member->code,
                    'pending_order_id' => $pendingOrderId,
                ]);

                return response()->json([
                    'success' => false,
                    'msg' => 'คุณมีรายการฝากเงินที่ยังดำเนินการอยู่ กรุณาทำรายการเดิมให้เสร็จสิ้นก่อน',
                    'pending_order_id' => $pendingOrderId,
                ]);
            }

            Log::channel('wealthpay_deposit_create')->error('[WEALTHPAY] create flex payment failed', [
                'txid' => $txid,
                'resp' => $resp,
            ]);

            return response()->json([
                'success' => false,
                'msg' => (string) data_get($resp, 'msg', 'create flex payment failed'),
            ]);
        }

        $provider = (array) data_get($resp, 'data.data', []);
        $platformOrderId = (string) data_get($provider, 'platform_order_id', '');
        $paymentUrl = (string) data_get($provider, 'payment_url', '');
        $paymentMethod = (string) data_get($provider, 'payment_method', '');

        if ($paymentUrl === '') {
            Log::channel('wealthpay_deposit_create')->error('[WEALTHPAY] payment_url missing in response', [
                'txid' => $txid,
                'provider' => $provider,
            ]);

            return response()->json([
                'success' => false,
                'msg' => 'payment_url missing in response',
            ], 500);
        }

        $expiredDate = now()->addMinutes(30);

        try {
            UpdateBalanceWealthPay::dispatch()->delay(5)->onQueue('topup');

            $this->repository->create([
                'bank_code' => $bankAccount->banks,
                'method' => 1,
                'txid' => $txid,
                'detail' => ($platformOrderId !== '' ? $platformOrderId : $txid),
                'amount' => $amountText,
                'payamount' => $amountText,
                'username' => trim((string) $member->user_name),
                'name' => (string) $member->name,
                'url' => $paymentUrl,
                'qrcode' => null,
                'status' => 'pending',
                'expired_date' => $expiredDate,
                'user_create' => (string) $member->name,
                'user_update' => (string) $member->name,
            ]);
        } catch (\Throwable $e) {
            Log::channel('wealthpay_deposit_create')->error('[WEALTHPAY] create check_case failed', [
                'txid' => $txid,
                'error' => $e->getMessage(),
                'provider' => $provider,
            ]);

            return response()->json([
                'success' => false,
                'msg' => 'create check_case failed: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'msg' => __('app.topup.create'),
            'url' => $paymentUrl,
            'target' => 'blank',
            'amount' => $amount,
            'platform_order_id' => $platformOrderId,
            'payment_method' => $paymentMethod,
        ]);
    }

    /**
     * Deposit Callback จาก Wealthwave
     *
     * เอกสาร: https://doc-th.wealthwave.tech/?page=payment_callback
     *
     * กฎสำคัญ:
     * - PAID ชนะทุกสถานะเสมอ (แม้จะเคย CANCELLED/FAIL มาก่อน)
     * - Deduplicate ด้วย client_order_id + platform_order_id
     * - ต้อง verify X-Signature ก่อนดำเนินการ
     * - ห้ามสร้างธุรกรรมทางการเงินซ้ำ (idempotent)
     */
    public function deposit_callback(Request $request)
    {
        $payload = $request->all();

        Log::channel('wealthpay_deposit_callback')->info('[WEALTHPAY] Deposit callback', [
            'payload' => $payload,
            'signature_header' => $request->header('X-Signature', '(missing)'),
        ]);

        // 0. Guard: ต้องเป็น PAYMENT callback เท่านั้น (กัน WITHDRAW callback หลุดมาผิด endpoint)
        if (data_get($payload, 'mode') !== 'PAYMENT') {
            return response()->json(['success' => true]);
        }

        // 1. ดึง client_order_id (คือ merchant_order_id ที่เราส่งไปตอน create)
        $clientOrderId = (string) (data_get($payload, 'client_order_id')
            ?? data_get($payload, 'data.client_order_id')
            ?? '');

        if ($clientOrderId === '') {
            Log::channel('wealthpay_deposit_callback')->warning('[WEALTHPAY] Callback missing client_order_id');

            return response()->json(['success' => true]);
        }

        // 2. หา check_case จาก txid (ซึ่งเราใช้เป็น merchant_order_id = client_order_id)
        $case = $this->repository->findOneWhere(['txid' => $clientOrderId]);
        if (! $case) {
            Log::channel('wealthpay_deposit_callback')->warning('[WEALTHPAY] check_case not found', [
                'client_order_id' => $clientOrderId,
            ]);

            return response()->json(['success' => true]);
        }

        // 3. Verify signature (configurable — ปิดชั่วคราวถ้ายังไม่ได้ set secret)
        if ((bool) config('wealthpay.verify_callback_signature', false)) {
            $api = new WealthPay;
            if (! $api->verifyCallbackSignature($request)) {
                Log::channel('wealthpay_deposit_callback')->warning('[WEALTHPAY] Invalid signature', [
                    'client_order_id' => $clientOrderId,
                ]);

                return response()->json(['success' => true]);
            }
        }

        // 4. Normalize สถานะจาก Wealthwave → ภายใน
        //    PAID → completed, FAIL → failed, CANCELLED → cancelled
        $api = new WealthPay;
        $incoming = $api->normalizeStatus((string) data_get($payload, 'status', 'pending'));
        $platformOrderId = (string) data_get($payload, 'platform_order_id', '');
        $amount = (float) (data_get($payload, 'amount') ?: $case->payamount ?: $case->amount);
        $current = strtolower((string) $case->status);

        Log::channel('wealthpay_deposit_callback')->info('[WEALTHPAY] Callback processing', [
            'client_order_id' => $clientOrderId,
            'platform_order_id' => $platformOrderId,
            'incoming' => $incoming,
            'current' => $current,
            'amount' => $amount,
        ]);

        // 5. กฎ PAID ชนะเสมอ (out-of-order handling)
        //    CANCELLED อาจตามด้วย PAID — ต้องยอมให้ PAID override ได้
        if ($incoming === 'completed') {
            // PAID ชนะทุกสถานะ — อัปเดตเสมอ (ยกเว้น completed ซ้ำ)
            if ($current !== 'completed') {
                $this->repository->update(['status' => 'completed'], $case->code);
            }
        } elseif ($incoming === 'cancelled') {
            // CANCELLED: อัปเดตได้เฉพาะถ้ายังเป็น pending อยู่
            // ถ้าเป็น completed/failed/cancelled อยู่แล้ว → ไม่ต้องเปลี่ยน
            if ($current === 'pending') {
                $this->repository->update(['status' => 'cancelled'], $case->code);
            }
        } elseif ($incoming === 'failed') {
            // FAIL: terminal สำหรับกรณีปกติ แต่ PAID override ได้ ( handled ข้างบนแล้ว)
            if (! in_array($current, ['completed', 'failed'], true)) {
                $this->repository->update(['status' => 'failed'], $case->code);
            }
        } elseif ($incoming !== 'pending') {
            // status อื่นๆ → อัปเดตถ้าไม่ใช่ terminal
            if (! in_array($current, ['completed', 'failed'], true)) {
                $this->repository->update(['status' => $incoming], $case->code);
            }
        }

        // 6. ถ้าไม่ใช่ completed → จบ (ยังไม่ต้องสร้าง bank_payment)
        if ($incoming !== 'completed') {
            return response()->json(['success' => true]);
        }

        // 7. === PAID: สร้าง bank_payment (idempotent) ===
        UpdateBalanceWealthPay::dispatch()->delay(5)->onQueue('topup');

        $member = $this->memberRepository->findOneWhere(['user_name' => $case->username]);
        if (! $member) {
            Log::channel('wealthpay_deposit_callback')->warning('[WEALTHPAY] Member not found', [
                'username' => $case->username,
            ]);

            return response()->json(['success' => true]);
        }

        $systemBankCode = (int) config('wealthpay.system_bank_code', 317);
        $bankAccount = $this->bankAccountRepository->findOneWhere([
            'banks' => $systemBankCode,
            'bank_type' => 1,
            'enable' => 'Y',
            'status_auto' => 'Y',
        ]);

        if (! $bankAccount) {
            Log::channel('wealthpay_deposit_callback')->warning('[WEALTHPAY] Bank account not found');

            return response()->json(['success' => true]);
        }

        // Dedup ด้วย txid + platform_order_id
        $check = $this->bankPaymentRepository->findOneWhere(['txid' => $clientOrderId]);
        if ($check) {
            Log::channel('wealthpay_deposit_callback')->info('[WEALTHPAY] Duplicate callback ignored (bank_payment exists)', [
                'txid' => $clientOrderId,
            ]);

            return response()->json(['success' => true]);
        }

        $bank = $this->bankRepository->find($bankAccount->banks);
        $detail = ' REF ID : '.($platformOrderId !== '' ? $platformOrderId : '-');
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
            'channel' => 'WealthPay',
            'value' => $amount,
            'tx_hash' => $hash,
            'txid' => $clientOrderId,
            'status' => 0,
            'ip_admin' => request()->ip(),
            'member_topup' => $member->code,
            'remark_admin' => '',
            'emp_topup' => 0,
            'user_create' => 'รอระบบเติมอัตโนมัติ ทำรายการฝากเงินโดย WealthPay',
            'create_by' => 'SYSAUTO',
        ]);

        Log::channel('wealthpay_deposit_callback')->info('[WEALTHPAY] bank_payment created', [
            'client_order_id' => $clientOrderId,
            'platform_order_id' => $platformOrderId,
            'amount' => $amount,
            'member_code' => $member->code,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Withdraw Callback จาก Wealthwave
     *
     * เอกสาร: https://doc-th.wealthwave.tech/?page=withdraw_callback
     *
     * Payload: merchant_id, platform_order_id, client_order_id, mode="WITHDRAW",
     *          bank, account_no, account_name, amount, status, timestamp
     *
     * Status: มีแค่ 2 ค่า — SUCCESS (โอนสำเร็จ) / FAIL (โอนไม่สำเร็จ)
     * ไม่มี CANCELLED หรือ out-of-order แบบ payment callback
     *
     * Idempotent: deduplicate ด้วย client_order_id (txid)
     * Signature: X-Signature header ต้อง verify ก่อนดำเนินการ
     */
    public function withdraw_callback(Request $request)
    {
        $payload = $request->all();

        Log::channel('wealthpay_withdraw_callback')->info('[WEALTHPAY] Withdraw callback', [
            'payload' => $payload,
            'signature_header' => $request->header('X-Signature', '(missing)'),
        ]);

        // 0. Guard: ต้องเป็น WITHDRAW callback เท่านั้น (กัน PAYMENT callback หลุดมาผิด endpoint)
        if (data_get($payload, 'mode') !== 'WITHDRAW') {
            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        // 1. Verify signature (configurable — เปิดเมื่อตั้ง WEALTHPAY_CALLBACK_SECRET แล้ว)
        if ((bool) config('wealthpay.verify_callback_signature', false)) {
            $api = new WealthPay;
            if (! $api->verifyCallbackSignature($request)) {
                Log::channel('wealthpay_withdraw_callback')->warning('[WEALTHPAY] Invalid withdraw callback signature');

                return response()->json(['code' => 0, 'msg' => 'success']);
            }
        }

        // 2. ดึง client_order_id (คือ txid ที่เราส่งไปตอน PaymentOut)
        $clientOrderId = (string) (data_get($payload, 'client_order_id') ?? data_get($payload, 'data.client_order_id') ?? '');

        if ($clientOrderId === '') {
            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        // 3. Normalize status: SUCCESS → completed, FAIL → failed
        $api = new WealthPay;
        $incoming = $api->normalizeStatus((string) data_get($payload, 'status', ''));
        $platformOrderId = (string) data_get($payload, 'platform_order_id', '');

        Log::channel('wealthpay_withdraw_callback')->info('[WEALTHPAY] Withdraw callback processing', [
            'client_order_id' => $clientOrderId,
            'platform_order_id' => $platformOrderId,
            'raw_status' => data_get($payload, 'status'),
            'normalized' => $incoming,
        ]);

        // 4. Update check_case (ถ้ามี — method=2 ที่สร้างจาก PaymentOutWealthPay)
        $case = $this->repository->findOneWhere(['txid' => $clientOrderId]);
        if ($case) {
            $current = strtolower((string) $case->status);

            // SUCCESS → completed (terminal)
            // FAIL → failed (terminal)
            if ($incoming !== 'pending' && ! in_array($current, ['completed', 'failed'], true)) {
                $this->repository->update(['status' => $incoming], $case->code);
            }
        }

        // 5. ถ้า status ที่ normalize ไม่ได้ → ไม่ต้องทำอะไร
        if ($incoming === 'pending') {
            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        // 6. หา withdraw record (status_withdraw = A = รอผลจาก Provider)
        $config = $this->getCoreConfig();

        if ($config->seamless === 'Y') {
            $order = app('Gametech\\Payment\\Repositories\\WithdrawSeamlessRepository')
                ->findOneWhere(['txid' => $clientOrderId, 'status_withdraw' => 'A']);
        } else {
            $order = app('Gametech\\Payment\\Repositories\\WithdrawRepository')
                ->findOneWhere(['txid' => $clientOrderId, 'status_withdraw' => 'A']);
        }

        if (! $order) {
            Log::channel('wealthpay_withdraw_callback')->info('[WEALTHPAY] No active withdraw order for callback', [
                'client_order_id' => $clientOrderId,
            ]);

            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        $amount = (float) $order['amount'];
        $refId = ($platformOrderId !== '' ? $platformOrderId : '-');

        // =========================
        // SUCCESS → ปิดรายการถอนสำเร็จ
        // =========================
        if ($incoming === 'completed') {
            UpdateBalanceWealthPay::dispatch()->delay(5)->onQueue('topup');

            $order->remark_admin = '[ Ref ID : '.$refId.' ] โอนให้ลูกค้าแล้ว (WealthPay)';
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
                'remark' => 'รายการแจ้งถอนที่ '.$order->code.' / ไอดีที่ถอน : '.$order->member_user.' จำนวนเงิน '.$amount.' โอนเงินให้ลูกค้าแล้ว WealthPay '.$clientOrderId.' [ '.$refId.' ]',
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
                'WealthPay '.$clientOrderId.' โอนเงินให้ลูกค้าแล้ว ID : '.$order->member_user.' จำนวนเงิน '.$amount.' รายการแจ้งถอนที่ '.$order->code,
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

            Log::channel('wealthpay_withdraw_callback')->info('[WEALTHPAY] Withdraw SUCCESS — completed', [
                'client_order_id' => $clientOrderId,
                'platform_order_id' => $platformOrderId,
                'amount' => $amount,
            ]);

            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        // =========================
        // FAIL → คืนยอด
        // =========================
        if ($config->seamless === 'Y') {
            $datanew = [
                'refer_code' => $order->code,
                'refer_table' => 'withdraws',
                'remark' => 'คืนยอดจากการถอน '.$clientOrderId.' (WealthPay FAIL)',
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
            $response = app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->setWalletSeamlessWithdraw($datanew);
        } else {
            $datanew = [
                'refer_code' => $order->code,
                'refer_table' => 'withdraws',
                'remark' => 'คืนยอดจากการถอน '.$clientOrderId.' (WealthPay FAIL)',
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
            $response = app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->setWalletSingleWithdraw($datanew);
        }

        if ($response) {
            broadcast(new RealTimeNewMessage(
                'WealthPay ยกเลิกรายการแจ้งถอน ของ ID '.$order->member_user.' จำนวนเงิน '.$amount.' Ref ID '.$refId.' ระบบคืนยอดให้ลูกค้าแล้ว (FAIL)',
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

            $order->remark_admin = '[ Ref ID : '.$refId.' ] โอนไม่สำเร็จ และ ระบบคืนยอดแล้ว (WealthPay FAIL)';
        } else {
            broadcast(new RealTimeNewMessage(
                'WealthPay ยกเลิกรายการแจ้งถอน ของ ID '.$order->member_user.' จำนวนเงิน '.$amount.' Ref ID '.$refId.' ระบบคืนยอด ให้ลูกค้าไม่ได้ (FAIL)',
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

            $order->remark_admin = '[ Ref ID : '.$refId.' ] โอนไม่สำเร็จ โปรดคืนยอดให้ลูกค้าเอง ระบบคืนไม่ได้ (WealthPay FAIL)';
        }

        $order->status_withdraw = 'R';
        $order->status = 2;
        $order->save();

        Log::channel('wealthpay_withdraw_callback')->info('[WEALTHPAY] Withdraw FAIL — rolled back', [
            'client_order_id' => $clientOrderId,
            'platform_order_id' => $platformOrderId,
            'amount' => $amount,
            'rollback_success' => (bool) $response,
        ]);

        return response()->json(['code' => 0, 'msg' => 'success']);
    }

    /**
     * ตั้งสถานะ expired
     */
    public function expire($txid)
    {
        $case = $this->repository->findOneWhere(['detail' => $txid]);
        if ($case && $case->status !== 'completed') {
            $this->repository->update([
                'status' => 'expired',
            ], $case->code);
        }

        return response()->json(['success' => true]);
    }

    /**
     * เช็คสถานะรายการ
     */
    public function checkStatus($txid)
    {
        $case = $this->repository->findOneWhere(['detail' => $txid]);

        if (! $case) {
            return response()->json(['success' => false, 'status' => 'NOT_FOUND']);
        }

        return response()->json([
            'success' => true,
            'status' => $case->status,
        ]);
    }

    /**
     * Mask ชื่อบัญชีสำหรับ log (เหลือเฉพาะตัวแรกและตัวสุดท้าย)
     */
    private function maskName(string $name): string
    {
        $trimmed = trim($name);
        $len = mb_strlen($trimmed, 'UTF-8');
        if ($len <= 2) {
            return str_repeat('*', $len);
        }

        return mb_substr($trimmed, 0, 1, 'UTF-8').str_repeat('*', $len - 2).mb_substr($trimmed, -1, 1, 'UTF-8');
    }

    /**
     * แปลง bank shortcode จากระบบเรา → Wealthwave bank code
     *
     * Wealthwave Flex API รองรับ: KBANK, BBL, KTB, TTB, SCB, BAY, UOB, CIMB,
     * LH, GSB, KK, CITI, GHB, BAAC, TISCO
     *
     * ระบบเราใช้ shortcode ที่ไม่ตรงกันบางตัว เช่น KKP→KK, LHBANK→LH, GHBANK→GHB
     * ใช้ bank_code_map ใน config จับคู่ ถ้าไม่มีใน map ส่งค่าผ่านตรง
     */
    private function resolveMemberBankCode($member): string
    {
        $map = (array) config('wealthpay.bank_code_map', []);

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
