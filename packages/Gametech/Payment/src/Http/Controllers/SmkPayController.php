<?php

namespace Gametech\Payment\Http\Controllers;

use App\Events\RealTimeMessage;
use App\Events\RealTimeNewMessage;
use Carbon\Carbon;
use Gametech\Auto\Jobs\UpdateBalanceSmkPay;
use Gametech\Core\Repositories\CheckCaseRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Libraries\SmkPay;
use Gametech\Payment\Models\PaymentProviderAccount;
use Gametech\Payment\Repositories\BankAccountRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\BankRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmkPayController extends AppBaseController
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
     * หน้าแสดง QR/สถานะ (เหมือน OnPay)
     */
    public function index($id)
    {
        $data = $this->repository->findOneWhere(['detail' => $id]);

        $banks = [];
        try {
            $banks = $this->bankRepository->all();
        } catch (\Throwable $e) {
            // ignore
        }

        $member = null;
        if ($data && !empty($data->username)) {
            $member = $this->memberRepository->findOneWhere([
                'user_name' => $data->username,
            ]);
        }

        $view = (string) config('smkpay.deposit_view', 'topup.box.onpay_new');

        return view($view, compact('data', 'member', 'banks'));
    }

    /**
     * Create Payin (OnPay pattern)
     * - รับแค่ amount
     * - member จาก auth
     * - ยิง API สำเร็จค่อย create check_case
     */
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
        ]);

        $member = auth()->guard('customer')->user();
        if (!$member) {
            return response()->json([
                'success' => false,
                'msg' => 'unauthenticated',
            ], 401);
        }

        $amount = number_format((float) $request->input('amount'), 2, '.', '');

        // bank กลาง (เหมือน OnPay)
        $systemBankCode = (int) config('smkpay.system_bank_code', 310);

        $bankAccount = $this->bankAccountRepository->findOneWhere([
            'banks' => $systemBankCode,
            'bank_type' => 1,
            'enable' => 'Y',
            'status_auto' => 'Y',
        ]);

        if (!$bankAccount) {
            return response()->json([
                'success' => false,
                'msg' => __('app.topup.fail'),
            ]);
        }

        /**
         * ✅ Sync limit จาก SMKPay -> อัปเดต bank_accounts.deposit_min
         * - ถ้า deposit_min ใน DB = 0 ให้ใช้ config fallback
         * - ถ้า API ให้ min_deposit > 0 ให้ update DB และใช้ค่านั้นเป็นตัวเชคขั้นต่ำ
         */
       $min = 0;

        try {
            $limitApi = new SmkPay();
            $limits = $limitApi->getLimits(['THB']);

            if (data_get($limits, 'success')) {
                $items = (array) data_get($limits, 'data.data', []);
                if (empty($items)) {
                    // บาง implementation return array ตรง ๆ ที่ data
                    $items = (array) data_get($limits, 'data', []);
                }

                $minFromApi = 0.0;
                foreach ($items as $row) {
                    $code = (string) data_get($row, 'currency_code', '');
                    if ($code === 'THB') {
                        $minFromApi = (float) data_get($row, 'min_deposit', 0);
                        break;
                    }
                }

                if ($minFromApi > 0) {
                    // update DB (กัน repository signature ไม่ตรง เลยมี fallback save model)
                    try {
                        $this->bankAccountRepository->update(['deposit_min' => $minFromApi ,'user_update' => 'API GET LIMIT'], $bankAccount->code);
                    } catch (\Throwable $e) {
                        try {
                            $bankAccount->deposit_min = $minFromApi;
                            $bankAccount->save();
                        } catch (\Throwable $e2) {
                            // ignore db update fail แต่ยังใช้ค่าจาก API เชคขั้นต่ำได้
                        }
                    }

                    $min = $minFromApi;
                }
            }
        } catch (\Throwable $e) {
            // ถ้าเชค limit ล้ม ให้ fallback ไปใช้ config
            Log::channel('smkpay_deposit_create')->warning('[SMKPAY] get limits failed', [
                'error' => $e->getMessage(),
            ]);
        }

//        if ($min <= 0) {
//            try {
//                $limitApi = new SmkPay();
//                $limits = $limitApi->getLimits(['THB']);
//
//                if (data_get($limits, 'success')) {
//                    $items = (array) data_get($limits, 'data.data', []);
//                    if (empty($items)) {
//                        // บาง implementation return array ตรง ๆ ที่ data
//                        $items = (array) data_get($limits, 'data', []);
//                    }
//
//                    $minFromApi = 0.0;
//                    foreach ($items as $row) {
//                        $code = (string) data_get($row, 'currency_code', '');
//                        if ($code === 'THB') {
//                            $minFromApi = (float) data_get($row, 'min_deposit', 0);
//                            break;
//                        }
//                    }
//
//                    if ($minFromApi > 0) {
//                        // update DB (กัน repository signature ไม่ตรง เลยมี fallback save model)
//                        try {
//                            $this->bankAccountRepository->update(['deposit_min' => $minFromApi], $bankAccount->code);
//                        } catch (\Throwable $e) {
//                            try {
//                                $bankAccount->deposit_min = $minFromApi;
//                                $bankAccount->save();
//                            } catch (\Throwable $e2) {
//                                // ignore db update fail แต่ยังใช้ค่าจาก API เชคขั้นต่ำได้
//                            }
//                        }
//
//                        $min = $minFromApi;
//                    }
//                }
//            } catch (\Throwable $e) {
//                // ถ้าเชค limit ล้ม ให้ fallback ไปใช้ config
//                Log::channel('smkpay_deposit_create')->warning('[SMKPAY] get limits failed', [
//                    'error' => $e->getMessage(),
//                ]);
//            }
//        }

        if ($min <= 0) {
            $min = (float) config('smkpay.min_deposit', 100);
        }

        if ((float) $amount < $min) {
            return response()->json([
                'success' => false,
                'msg' => __('app.topup.min_deposit', ['amount' => $min]),
            ]);
        }

        // ✅ เชค → เติม (ensure) → เชคซ้ำ → ค่อยสร้าง payin
        /** @var PaymentProviderAccount|null $ppa */
        $ppa = PaymentProviderAccount::query()
            ->where('provider', 'smkpay')
            ->where('member_code', (int) $member->code)
            ->first();

        if (!$ppa || empty($ppa->customer_id) || empty($ppa->customer_account_id)) {
            $sync = $this->ensureProviderAccountUpToDate($member);
            if (!data_get($sync, 'success')) {
                return response()->json([
                    'success' => false,
                    'msg' => data_get($sync, 'msg', 'prepare provider account failed'),
                ]);
            }

            // ✅ re-check หลัง ensure เสร็จ (ยึด DB เป็น source of truth)
            $ppa = PaymentProviderAccount::query()
                ->where('provider', 'smkpay')
                ->where('member_code', (int) $member->code)
                ->first();
        }

        if (!$ppa || empty($ppa->customer_id) || empty($ppa->customer_account_id)) {
            Log::channel('smkpay_deposit_create')->error('[SMKPAY] provider account not ready (after ensure)', [
                'member_code' => (int) $member->code,
                'ppa_exists' => (bool) $ppa,
                'customer_id' => $ppa ? $ppa->customer_id : null,
                'customer_account_id' => $ppa ? $ppa->customer_account_id : null,
                'meta' => $ppa ? $ppa->meta : null,
            ]);

            return response()->json([
                'success' => false,
                'msg' => 'provider account not ready',
            ]);
        }

        // txid ภายในระบบ (merchant_ref_id)
        $txid = 'SDEP-' . str_pad((string) $member->code, 6, '0', STR_PAD_LEFT) . '-' . date('YmdHis');

        Log::channel('smkpay_deposit_create')->info('[SMKPAY] create payin start', [
            'txid' => $txid,
            'member_code' => $member->code,
            'username' => $member->user_name,
        ]);



        $api = new SmkPay();

        /**
         * ✅ PAYIN payload: ตาม Prepare เท่านั้น
         * (ไม่ใส่ transaction_type / callback_url เอง)
         */
        $payload = [
            'currency_code' => (string) ($ppa->currency_code ?: 'THB'),
            'customer_account_id' => (string) $ppa->customer_account_id,
            'customer_id' => (string) $ppa->customer_id,
            'merchant_ref_id' => trim($txid),
            'notes' => 'Payin for deposit',
            'receiving_type' => 'QR',
            'request_amount' => (string) $amount,
        ];

        $resp = $api->createPayin($payload);

        if (!data_get($resp, 'success')) {
            $providerCode = (string) (data_get($resp, 'data.code', '') ?: '');
            $providerMessage = (string) (data_get($resp, 'data.message', '') ?: data_get($resp, 'msg', 'create payin failed'));
            $httpCode = (int) data_get($resp, 'http_code', 0);

            Log::channel('smkpay_deposit_create')->error('[SMKPAY] create payin failed', [
                'txid' => $txid,
                'http_code' => $httpCode,
                'provider_code' => $providerCode,
                'resp' => $resp,
            ]);

            // ✅ map provider maintenance-ish errors -> app.topup.maintenance
            if (in_array($providerCode, ['FALLBACK_PROVIDERS_EXHAUSTED', 'VALIDATION_ERROR'], true) || $httpCode === 503) {
                return response()->json([
                    'success' => false,
                    'msg' => __('app.topup.maintenance'),
                ]);
            }

            return response()->json([
                'success' => false,
                'msg' => ($providerMessage !== '' ? $providerMessage : 'create payin failed'),
            ]);
        }


        /**
         * response จริงตาม log:
         * {
         *   success: true,
         *   code: "SUCCESS",
         *   message: "...",
         *   data: {...payin...},
         *   meta: { request_id, timestamp }
         * }
         */
        $provider = (array) data_get($resp, 'data.data', []);
        $meta = (array) data_get($resp, 'data.meta', []);

        // ✅ mapping ตามที่คุณสั่ง
        $qrImageBase64 = (string) data_get($provider, 'receiving_qr_image_base64', '');
        $qrString = (string) data_get($provider, 'receiving_qr_string', '');
        $transferAmount = data_get($provider, 'transfer_amount');
        $requestId = (string) data_get($provider, 'id', '');

        $payAmount = $amount;
        if ($transferAmount !== null && $transferAmount !== '') {
            $payAmount = (string) $transferAmount;
        }

        $expiredAt = data_get($provider, 'expired_at');
        $expiredDate = is_numeric($expiredAt)
            ? Carbon::createFromTimestamp((int) $expiredAt)->setTimezone('Asia/Bangkok')
            : null;

        /**
         * ✅ create check_case หลัง API สำเร็จ
         * - ห้ามใส่ meta (กัน schema/repository mismatch)
         */
        try {
            UpdateBalanceSmkPay::dispatch()->delay(5)->onQueue('topup');
            $this->repository->create([
                'bank_code' => $bankAccount->banks,
                'method' => 1,
                'txid' => $txid,

                // ✅ detail = request_id
                'detail' => ($requestId !== '' ? $requestId : $txid),

                'amount' => $amount,

                // ✅ payamount = transfer_amount
                'payamount' => $payAmount,

                'username' => trim((string) $member->user_name),
                'name' => (string) $member->name,

                // ✅ url = receiving_qr_string
                'url' => ($qrString !== '' ? $qrString : null),

                // ✅ qrcode = receiving_qr_image_base64
                'qrcode' => ($qrImageBase64 !== '' ? $qrImageBase64 : null),

                'status' => 'pending',
                'expired_date' => $expiredDate,

                'user_create' => (string) $member->name,
                'user_update' => (string) $member->name,
            ]);
        } catch (\Throwable $e) {
            Log::channel('smkpay_deposit_create')->error('[SMKPAY] create check_case failed', [
                'txid' => $txid,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 2000),
                'provider' => $provider,
                'meta' => $meta,
            ]);

            return response()->json([
                'success' => false,
                'msg' => 'create check_case failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'msg' => __('app.topup.create'),
            'url' => route('api.smkpay.index', ['id' => ($requestId !== '' ? $requestId : $txid)]),
        ]);
    }


    /**
     * Callback status rules:
     * - expired แล้ว "ห้ามกลับไป pending" (รับได้แค่ success -> completed)
     * - terminal: completed/failed/rejected/refunded ห้ามเปลี่ยน
     */
    public function deposit_callback(Request $request)
    {
        $payload = $request->all();

        Log::channel('smkpay_deposit_callback')->info('[SMKPAY] Deposit callback', $payload);

        $merchantRef = data_get($payload, 'merchant_ref_id')
            ?? data_get($payload, 'merchant_reference')
            ?? data_get($payload, 'data.merchant_ref_id')
            ?? data_get($payload, 'data.merchant_reference')
            ?? null;

        $id = data_get($payload, 'id')
            ?? data_get($payload, 'merchant_reference')
            ?? data_get($payload, 'data.id')
            ?? data_get($payload, 'data.merchant_reference')
            ?? null;

        if (!$merchantRef) {
            return response()->json(['success' => true]);
        }

        $case = $this->repository->findOneWhere(['txid' => $merchantRef]);
        if (!$case) {
            return response()->json(['success' => true]);
        }

        $providerStatus = strtolower((string) (data_get($payload, 'status') ?? data_get($payload, 'data.status') ?? 'pending'));

        // normalize
        $incoming = 'pending';
        if (in_array($providerStatus, ['pending', 'processing', 'pending_review', 'in_review'], true)) {
            $incoming = 'pending';
        } elseif ($providerStatus === 'success') {
            $incoming = 'completed';
        } elseif ($providerStatus === 'expired') {
            $incoming = 'expired';
        } elseif ($providerStatus === 'failed') {
            $incoming = 'failed';
        } elseif ($providerStatus === 'rejected') {
            $incoming = 'rejected';
        } elseif ($providerStatus === 'refunded') {
            $incoming = 'refunded';
        }

        $current = strtolower((string) $case->status);

        // terminal: ห้ามเปลี่ยน
        if (in_array($current, ['completed', 'failed', 'rejected', 'refunded'], true)) {
            return response()->json(['success' => true]);
        }

        // expired: revive ได้แค่ completed เท่านั้น (ห้าม pending)
        if ($current === 'expired') {
            if ($incoming === 'completed') {
                $this->repository->update(['status' => 'completed'], $case->code);
            }
//            return response()->json(['success' => true]);
        }

        // pending: update ได้ทุกอย่าง
        if ($current === 'pending' && $incoming !== 'pending') {
            $this->repository->update(['status' => $incoming], $case->code);
        }

        if($incoming === 'completed'){
            UpdateBalanceSmkPay::dispatch()->delay(5)->onQueue('topup');
            $amount = $case->amount;
            $member = $this->memberRepository->findOneWhere(['user_name' => $case->username]);

            // bank กลาง (เหมือน OnPay)
            $systemBankCode = (int) config('smkpay.system_bank_code', 310);

            $bank_account = $this->bankAccountRepository->findOneWhere([
                'banks' => $systemBankCode, 'bank_type' => 1, 'enable' => 'Y', 'status_auto' => 'Y',
            ]);

            $bank = $this->bankRepository->find($bank_account->banks);
            $detail = ' REF ID : ' . $id;
            $hash = md5($bank_account->code . $amount . $detail);
            $datenow = now()->toDateTimeString();
            $data = [
                'bank' => strtolower($bank->shortcode . '_' . $bank_account->acc_no),
                'detail' => $detail . ' จำนวน ' . $amount,
                'account_code' => $bank_account->code,
                'autocheck' => 'W',
                'bankstatus' => 1,
                'bank_name' => $bank->shortcode,
                'bank_time' => $datenow,
                'channel' => 'QR',
                'value' => $amount,
                'tx_hash' => $hash,
                'txid' => $merchantRef,
                'status' => 0,
                'ip_admin' => request()->ip(),
                'member_topup' => $member->code,
                'remark_admin' => '',
                'emp_topup' => 0,
                'user_create' => 'รอระบบเติมอัตโนมัติ ทำรายการฝากเงินโดย SmkPay QR',
                'create_by' => 'SYSAUTO',
            ];

            $check = $this->bankPaymentRepository->findOneWhere(['txid' => $merchantRef]);
            if (!$check) {
                $this->bankPaymentRepository->create($data);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Withdraw (Payout) Callback
     *
     * Status Referance (จากเอกสาร SMK):
     * - pending | processing | pending_review | in_review => ไม่ต้องทำอะไร
     * - success => ปรับสถานะรายการที่เกี่ยวข้อง (โอนสำเร็จ)
     * - failed | rejected | expired | refunded => ปรับสถานะ + คืนยอด
     */
    public function withdraw_callback(Request $request)
    {
        $config = $this->getCoreConfig();
        $message = $request->all();

        Log::channel('smkpay_withdraw_callback')->info('[SMKPAY] Withdraw callback', $message);

        // --- guard: บาง provider ส่ง key ไม่เหมือนกัน
        $transactionType = strtolower((string) (data_get($message, 'transaction_type') ?? data_get($message, 'type') ?? ''));

        // ถ้าไม่ใช่ payout ก็ไม่แตะอะไร (กัน callback อื่นปน)
        if ($transactionType !== '' && $transactionType !== 'payout') {
            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        $merchantRef = data_get($message, 'merchant_ref_id')
            ?? data_get($message, 'merchant_reference')
            ?? data_get($message, 'data.merchant_ref_id')
            ?? data_get($message, 'data.merchant_reference')
            ?? null;

        if (!$merchantRef) {
            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        $refId = (string) (data_get($message, 'id')
            ?? data_get($message, 'data.id')
            ?? '');

        $providerStatus = strtolower((string) (data_get($message, 'status') ?? data_get($message, 'data.status') ?? 'pending'));

        // normalize สำหรับ check_case.status ให้คล้าย deposit_callback
        $incoming = 'pending';
        if (in_array($providerStatus, ['pending', 'processing', 'pending_review', 'in_review'], true)) {
            $incoming = 'pending';
        } elseif ($providerStatus === 'success') {
            $incoming = 'completed';
        } elseif ($providerStatus === 'expired') {
            $incoming = 'expired';
        } elseif ($providerStatus === 'failed') {
            $incoming = 'failed';
        } elseif ($providerStatus === 'rejected') {
            $incoming = 'rejected';
        } elseif ($providerStatus === 'refunded') {
            $incoming = 'refunded';
        }

        // --- update check_case ถ้ามี
        $case = $this->repository->findOneWhere(['txid' => $merchantRef]);
        if ($case) {
            $current = strtolower((string) $case->status);

            // terminal: completed/failed/rejected/refunded ห้ามเปลี่ยน
            if (!in_array($current, ['completed', 'failed', 'rejected', 'refunded'], true)) {
                // expired: revive ได้แค่ completed
                if ($current === 'expired') {
                    if ($incoming === 'completed') {
                        $this->repository->update(['status' => 'completed'], $case->code);
                    }
                } else {
                    if ($incoming !== 'pending') {
                        $this->repository->update(['status' => $incoming], $case->code);
                    }
                }
            }
        }

        // --- pending-like => ไม่ทำอะไรเพิ่มเติม
        if ($incoming === 'pending') {
            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        // --- หา withdraw ที่กำลังรอผล provider (status_withdraw = A)
        if ($config->seamless === 'Y') {
            $data = app('Gametech\\Payment\\Repositories\\WithdrawSeamlessRepository')
                ->findOneWhere(['txid' => $merchantRef, 'status_withdraw' => 'A']);
        } else {
            $data = app('Gametech\\Payment\\Repositories\\WithdrawRepository')
                ->findOneWhere(['txid' => $merchantRef, 'status_withdraw' => 'A']);
        }

        if (!$data) {
            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        $amount = $data['amount'];

        // =========================
        // SUCCESS => ปิดรายการถอน
        // =========================
        if ($incoming === 'completed') {
            UpdateBalanceSmkPay::dispatch()->delay(5)->onQueue('topup');

            $data->remark_admin = '[ Ref ID : ' . ($refId !== '' ? $refId : '-') . ' ] โอนให้ลูกค้าแล้ว (SMKPay)';
            $data->status_withdraw = 'C';
            $data->save();

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
                'refer_code' => $data->code,
                'refer_table' => 'withdraws',
                'remark' => 'รายการแจ้งถอนที่ ' . $data->code . ' / ไอดีที่ถอน : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' โอนเงินให้ลูกค้าแล้ว SMKPay ' . $merchantRef . ' [ ' . ($refId !== '' ? $refId : '-') . ' ]',
                'kind' => 'OTHER',
                'amount' => $amount,
                'amount_balance' => 0,
                'withdraw_limit' => 0,
                'withdraw_limit_amount' => 0,
                'method' => 'D',
                'member_code' => $data->member_code,
                'user_name' => $data->member_user,
                'emp_code' => 0,
                'emp_name' => 'SYSTEM',
            ]);

            broadcast(new RealTimeNewMessage(
                'SMKPay ' . $merchantRef . ' โอนเงินให้ลูกค้าแล้ว ID : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' รายการแจ้งถอนที่ ' . $data->code,
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

            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        // =========================
        // FAIL/REJECT/EXPIRED/REFUND => คืนยอด
        // =========================
        if (in_array($incoming, ['failed', 'rejected', 'expired', 'refunded'], true)) {
            if ($config->seamless === 'Y') {
                $datanew = [
                    'refer_code' => $data->code,
                    'refer_table' => 'withdraws',
                    'remark' => 'คืนยอดจากการถอน ' . $merchantRef . ' (SMKPay ' . $incoming . ')',
                    'kind' => 'ROLLBACK',
                    'amount' => $amount,
                    'amount_balance' => $data->amount_balance,
                    'withdraw_limit' => $data->amount_limit,
                    'withdraw_limit_amount' => $data->amount_limit_rate,
                    'method' => 'D',
                    'member_code' => $data->member_code,
                    'emp_code' => 0,
                    'emp_name' => 'SYSTEM',
                ];
                $response = app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->setWalletSeamlessWithdraw($datanew);
            } else {
                $datanew = [
                    'refer_code' => $data->code,
                    'refer_table' => 'withdraws',
                    'remark' => 'คืนยอดจากการถอน ' . $merchantRef . ' (SMKPay ' . $incoming . ')',
                    'kind' => 'ROLLBACK',
                    'amount' => $amount,
                    'amount_balance' => $data->amount_balance,
                    'withdraw_limit' => $data->amount_limit,
                    'withdraw_limit_amount' => $data->amount_limit_rate,
                    'pro_code' => $data->pro_code,
                    'pro_name' => $data->pro_name,
                    'method' => 'D',
                    'member_code' => $data->member_code,
                    'emp_code' => 0,
                    'emp_name' => 'SYSTEM',
                ];

                $response = app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->setWalletSingleWithdraw($datanew);
            }

            if ($response) {
                broadcast(new RealTimeNewMessage(
                    'SMKPay ยกเลิกรายการแจ้งถอน ของ ID ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . ($refId !== '' ? $refId : '-') . ' ระบบคืนยอดให้ลูกค้าแล้ว (' . $incoming . ')',
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

                $data->remark_admin = '[ Ref ID : ' . ($refId !== '' ? $refId : '-') . ' ] โอนไม่สำเร็จ และ ระบบคืนยอดแล้ว (SMKPay ' . $incoming . ')';
            } else {
                broadcast(new RealTimeNewMessage(
                    'SMKPay ยกเลิกรายการแจ้งถอน ของ ID ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' Ref ID ' . ($refId !== '' ? $refId : '-') . ' ระบบคืนยอด ให้ลูกค้าไม่ได้ (' . $incoming . ')',
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

                $data->remark_admin = '[ Ref ID : ' . ($refId !== '' ? $refId : '-') . ' ] โอนไม่สำเร็จ โปรดคืนยอดให้ลูกค้าเอง ระบบคืนไม่ได้ (SMKPay ' . $incoming . ')';
            }

            $data->status_withdraw = 'R';
            $data->status = 2;
            $data->save();

            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        // fallback (กรณีมี status ใหม่ในอนาคต)
        broadcast(new RealTimeMessage(
            'SMKPay ' . $merchantRef . ' สถานะ ' . ucfirst($providerStatus) . ' รายการถอน ID : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' รายการแจ้งถอนที่ ' . $data->code
        ));

        return response()->json(['code' => 0, 'msg' => 'success']);
    }

    /**
     * สร้าง/หา + sync customer/account ตาม flow ในเอกสาร (Prepare)
     * - email = phone@gmail.com (ตามที่คุณกำหนด)
     * - เก็บ account_id ลง customer_account_id (ตาม model ล่าสุด)
     */
    private function ensureProviderAccountUpToDate($member): array
    {
        $provider = 'smkpay';
        $currency = 'THB';

        $phoneRaw = trim((string) ($member->user_name ?? ''));
        $email = $phoneRaw . '@gmail.com';
        $phoneE164 = $this->toThaiE164($phoneRaw);

        $bankAccountNo = trim((string) ($member->acc_no ?? ''));
        $bankCode = (int) ($member->bank_code ?? 0);
        $accountHolderName = trim((string) ($member->name ?? ''));

        if ($phoneRaw === '' || $bankAccountNo === '' || $bankCode === 0) {
            return ['success' => false, 'msg' => 'member missing phone/bank info'];
        }

        $bank = $this->bankRepository->find($bankCode);
        $platform = $bank ? strtoupper((string) $bank->shortcode) : null;

        if (empty($platform)) {
            return ['success' => false, 'msg' => 'cannot resolve bank platform'];
        }

        $api = new SmkPay();

        /** @var PaymentProviderAccount|null $ppa */
        $ppa = PaymentProviderAccount::query()
            ->where('provider', $provider)
            ->where('member_code', (int) $member->code)
            ->first();

        // ✅ ถ้าไม่มี record: insert pending (ids ว่าง) ก่อน แล้วค่อยยิงเติมทีละค่า
        if (!$ppa) {
            try {
                $ppa = new PaymentProviderAccount();
                $ppa->fill([
                    'provider' => $provider,
                    'member_code' => (int) $member->code,
                    'currency_code' => $currency,

                    'name' => $accountHolderName,
                    'phone_number' => $phoneE164,

                    'bank_code' => $bankCode,
                    'bank_account_number' => $bankAccountNo,
                    'bank_account_name' => $accountHolderName,

                    'account_identifier' => $bankAccountNo,
                    'account_platform' => $platform,

                    'customer_id' => null,
                    'customer_account_id' => null,

                    'sync_hash' => null,
                    'last_synced_at' => null,
                ]);

                $meta = (array) ($ppa->meta ?? []);
                $meta['prepare'] = [
                    'step' => 'insert_pending',
                    'success' => true,
                    'ts' => now()->toDateTimeString(),
                ];
                $meta['snapshot'] = [
                    'phone' => $phoneE164,
                    'email' => $email,
                    'bank_account_number' => $bankAccountNo,
                    'bank_account_name' => $accountHolderName,
                    'account_platform' => $platform,
                    'bank_code' => $bankCode,
                    'updated_at' => now()->toDateTimeString(),
                ];
                $ppa->meta = $meta;

                $ppa->save();
            } catch (\Illuminate\Database\QueryException $e) {
                // ถ้าชน unique (มีอีก request insert ไปก่อน) -> re-fetch
                $ppa = PaymentProviderAccount::query()
                    ->where('provider', $provider)
                    ->where('member_code', (int) $member->code)
                    ->first();

                if (!$ppa) {
                    return ['success' => false, 'msg' => 'insert pending failed: ' . $e->getMessage()];
                }
            }
        }

        // --- เก็บค่าเดิมก่อน (กัน bug hash เช็คตัวเอง + ใช้เทียบ)
        $oldHash = (string) ($ppa->sync_hash ?? '');

        $oldCustomerId = (string) ($ppa->customer_id ?? '');
        $oldAccountId = (string) ($ppa->customer_account_id ?? '');

        $oldName = (string) ($ppa->name ?? '');
        $oldPhone = (string) ($ppa->phone_number ?? '');
        $oldEmail = (string) data_get($ppa->meta, 'snapshot.email', '');

        $oldBankCode = (int) ($ppa->bank_code ?? 0);
        $oldBankAccountNo = (string) ($ppa->bank_account_number ?? '');
        $oldPlatform = (string) ($ppa->account_platform ?? '');

        // --- hash ใหม่จากข้อมูลปัจจุบัน (members)
        $newHash = sha1(implode('|', [
            $provider,
            (int) $member->code,
            $email,
            $phoneE164,
            $currency,
            $bankCode,
            $bankAccountNo,
            $accountHolderName,
            $platform,
        ]));

        // --- อัปเดตฟิลด์ให้ตรง members เสมอ (แต่ยังไม่ set sync_hash จนกว่าจะจบ)
        $ppa->fill([
            'currency_code' => $currency,

            'name' => $accountHolderName,
            'phone_number' => $phoneE164,

            'bank_code' => $bankCode,
            'bank_account_number' => $bankAccountNo,
            'bank_account_name' => $accountHolderName,

            'account_identifier' => $bankAccountNo,
            'account_platform' => $platform,
        ]);

        $needsCustomerPatch = !empty($oldCustomerId)
            && (($oldPhone !== $phoneE164) || ($oldName !== $accountHolderName) || ($oldEmail !== $email));

        $needsAccountPatch = !empty($oldCustomerId) && !empty($oldAccountId)
            && (($oldBankCode !== $bankCode) || ($oldBankAccountNo !== $bankAccountNo) || ($oldPlatform !== $platform));

        // ✅ ถ้าครบ + hash เดิมตรง + ไม่ต้อง PATCH -> ไม่ต้องยิง API
        if (!empty($oldCustomerId) && !empty($oldAccountId) && $oldHash !== '' && $oldHash === $newHash && !$needsCustomerPatch && !$needsAccountPatch) {
            $meta = (array) ($ppa->meta ?? []);
            $meta['prepare'] = [
                'step' => 'already_ready',
                'success' => true,
                'ts' => now()->toDateTimeString(),
            ];
            $meta['snapshot'] = [
                'phone' => $phoneE164,
                'email' => $email,
                'bank_account_number' => $bankAccountNo,
                'bank_account_name' => $accountHolderName,
                'account_platform' => $platform,
                'bank_code' => $bankCode,
                'updated_at' => now()->toDateTimeString(),
            ];
            $ppa->meta = $meta;

            $ppa->sync_hash = $newHash;
            $ppa->last_synced_at = now();
            $ppa->save();

            return ['success' => true, 'account' => $ppa];
        }

        // =========================
        // 1) Create / Patch Customer
        // =========================
        $customerId = $oldCustomerId;

        if (empty($customerId)) {
            $custCreate = $api->createCustomer([
                'email' => $email,
                'name' => ($accountHolderName !== '' ? $accountHolderName : $phoneRaw),
                'notes' => 'Created by merchant system',
                'phone_number' => $phoneE164,
            ]);

            if (!data_get($custCreate, 'success')) {
                $meta = (array) ($ppa->meta ?? []);
                $meta['prepare'] = [
                    'step' => 'create_customer',
                    'success' => false,
                    'ts' => now()->toDateTimeString(),
                    'response' => data_get($custCreate, 'data'),
                ];
                $ppa->meta = $meta;
                $ppa->save();

                return ['success' => false, 'msg' => 'create customer failed'];
            }

            $customerId = (string) data_get($custCreate, 'data.data.customer_id', '');
            if ($customerId === '') {
                return ['success' => false, 'msg' => 'customer_id missing after create customer'];
            }

            $ppa->customer_id = $customerId;

            $meta = (array) ($ppa->meta ?? []);
            $meta['prepare'] = [
                'step' => 'create_customer',
                'success' => true,
                'ts' => now()->toDateTimeString(),
                'response' => data_get($custCreate, 'data'),
            ];
            $ppa->meta = $meta;
            $ppa->save();

            // create ใหม่แล้ว ไม่ต้อง patch customer ต่อ
            $needsCustomerPatch = false;
        } elseif ($needsCustomerPatch) {
            $custPatch = $api->updateCustomer($customerId, [
                'account_holder_name' => ($accountHolderName !== '' ? $accountHolderName : $phoneRaw),
                'email' => $email,
                'notes' => 'Updated by merchant system',
                'phone_number' => $phoneE164,
                'status' => 'normal',
            ]);

            if (!data_get($custPatch, 'success')) {
                $meta = (array) ($ppa->meta ?? []);
                $meta['prepare'] = [
                    'step' => 'patch_customer',
                    'success' => false,
                    'ts' => now()->toDateTimeString(),
                    'response' => data_get($custPatch, 'data'),
                ];
                $ppa->meta = $meta;
                $ppa->save();

                return ['success' => false, 'msg' => 'update customer failed'];
            }

            $meta = (array) ($ppa->meta ?? []);
            $meta['prepare'] = [
                'step' => 'patch_customer',
                'success' => true,
                'ts' => now()->toDateTimeString(),
                'response' => data_get($custPatch, 'data'),
            ];
            $ppa->meta = $meta;
            $ppa->save();
        }

        // =========================
        // 2) Create / Patch Account
        // =========================
        $accountId = $oldAccountId;

        if (empty($accountId)) {
            $accCreate = $api->createCustomerAccount($customerId, [
                'account_holder_name' => ($accountHolderName !== '' ? $accountHolderName : $phoneRaw),
                'account_identifier' => $bankAccountNo,
                'account_platform' => $platform,
                'currency_code' => $currency,
            ]);

            if (!data_get($accCreate, 'success')) {
                // fallback: บางเคส provider บอก duplicate/conflict -> ลอง GET account เพื่อดึง id เดิม
                $accCheck = $api->getCustomerAccount($bankAccountNo, $platform, $currency);

                if (data_get($accCheck, 'success')) {
                    $accCustomerId = (string) data_get($accCheck, 'data.data.customer_id', '');
                    $checkedAccountId = (string) data_get($accCheck, 'data.data.account_id', '');

                    if ($accCustomerId !== '' && $checkedAccountId !== '') {
                        $ppa->customer_id = $accCustomerId;
                        $ppa->customer_account_id = $checkedAccountId;

                        $meta = (array) ($ppa->meta ?? []);
                        $meta['prepare'] = [
                            'step' => 'get_account_fallback',
                            'success' => true,
                            'ts' => now()->toDateTimeString(),
                            'response' => data_get($accCheck, 'data'),
                        ];
                        $ppa->meta = $meta;
                        $ppa->save();

                        $customerId = $accCustomerId;
                        $accountId = $checkedAccountId;
                    }
                }

                if (empty($accountId)) {
                    $meta = (array) ($ppa->meta ?? []);
                    $meta['prepare'] = [
                        'step' => 'create_account',
                        'success' => false,
                        'ts' => now()->toDateTimeString(),
                        'response' => data_get($accCreate, 'data'),
                    ];
                    $ppa->meta = $meta;
                    $ppa->save();

                    return ['success' => false, 'msg' => 'create customer account failed'];
                }
            } else {
                $accountId = (string) data_get($accCreate, 'data.data.account_id', '');
                if ($accountId === '') {
                    return ['success' => false, 'msg' => 'account_id missing after create account'];
                }

                $ppa->customer_id = $customerId;
                $ppa->customer_account_id = $accountId;

                $meta = (array) ($ppa->meta ?? []);
                $meta['prepare'] = [
                    'step' => 'create_account',
                    'success' => true,
                    'ts' => now()->toDateTimeString(),
                    'response' => data_get($accCreate, 'data'),
                ];
                $ppa->meta = $meta;
                $ppa->save();
            }
        } elseif ($needsAccountPatch) {
            $payload = [
                'account_holder_name' => ($accountHolderName !== '' ? $accountHolderName : $phoneRaw),
                'account_identifier' => $bankAccountNo,
                'notes' => 'Updated by merchant system',
            ];

            // ถ้า platform เปลี่ยน: พยายามส่งไปด้วย (ถ้า provider ไม่รองรับ จะ fallback create ใหม่)
            if ($oldPlatform !== $platform) {
                $payload['account_platform'] = $platform;
            }

            $accPatch = $api->updateCustomerAccount($customerId, $accountId, $payload);

            if (!data_get($accPatch, 'success')) {
                // fallback: ถ้า PATCH ไม่ผ่าน -> ลองสร้าง account ใหม่
                $accCreate2 = $api->createCustomerAccount($customerId, [
                    'account_holder_name' => ($accountHolderName !== '' ? $accountHolderName : $phoneRaw),
                    'account_identifier' => $bankAccountNo,
                    'account_platform' => $platform,
                    'currency_code' => $currency,
                ]);

                if (!data_get($accCreate2, 'success')) {
                    $meta = (array) ($ppa->meta ?? []);
                    $meta['prepare'] = [
                        'step' => 'patch_account',
                        'success' => false,
                        'ts' => now()->toDateTimeString(),
                        'response' => [
                            'patch' => data_get($accPatch, 'data'),
                            'create_fallback' => data_get($accCreate2, 'data'),
                        ],
                    ];
                    $ppa->meta = $meta;
                    $ppa->save();

                    return ['success' => false, 'msg' => 'update customer account failed'];
                }

                $newAccountId = (string) data_get($accCreate2, 'data.data.account_id', '');
                if ($newAccountId === '') {
                    return ['success' => false, 'msg' => 'account_id missing after create account fallback'];
                }

                $ppa->customer_account_id = $newAccountId;

                $meta = (array) ($ppa->meta ?? []);
                $meta['prepare'] = [
                    'step' => 'create_account_fallback',
                    'success' => true,
                    'ts' => now()->toDateTimeString(),
                    'response' => data_get($accCreate2, 'data'),
                ];
                $ppa->meta = $meta;
                $ppa->save();

                $accountId = $newAccountId;
            } else {
                $meta = (array) ($ppa->meta ?? []);
                $meta['prepare'] = [
                    'step' => 'patch_account',
                    'success' => true,
                    'ts' => now()->toDateTimeString(),
                    'response' => data_get($accPatch, 'data'),
                ];
                $ppa->meta = $meta;
                $ppa->save();
            }
        }

        if (empty($ppa->customer_id) || empty($ppa->customer_account_id)) {
            return ['success' => false, 'msg' => 'provider account not ready after ensure'];
        }

        // --- snapshot + sync
        $meta = (array) ($ppa->meta ?? []);
        $meta['prepare'] = [
            'step' => 'ready',
            'success' => true,
            'ts' => now()->toDateTimeString(),
        ];
        $meta['snapshot'] = [
            'phone' => $phoneE164,
            'email' => $email,
            'bank_account_number' => $bankAccountNo,
            'bank_account_name' => $accountHolderName,
            'account_platform' => $platform,
            'bank_code' => $bankCode,
            'updated_at' => now()->toDateTimeString(),
        ];
        $ppa->meta = $meta;

        $ppa->sync_hash = $newHash;
        $ppa->last_synced_at = now();
        $ppa->save();

        return ['success' => true, 'account' => $ppa];
    }


    /**
     * expire (คงไว้ตามไฟล์ล่าสุด)
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
     * checkStatus (คงไว้ตามไฟล์ล่าสุด)
     */
    public function checkStatus($txid)
    {
        $case = $this->repository->findOneWhere(['detail' => $txid]);

        if (!$case) {
            return response()->json(['success' => false, 'status' => 'NOT_FOUND']);
        }

        return response()->json([
            'success' => true,
            'status' => $case->status,
        ]);
    }

    private function toThaiE164(string $phone): string
    {
        $p = trim($phone);
        if ($p === '') {
            return '';
        }

        if (str_starts_with($p, '+')) {
            return $p;
        }

        $digits = preg_replace('/\D+/', '', $p) ?: '';

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return '+66' . substr($digits, 1);
        }

        if (str_starts_with($digits, '66')) {
            return '+' . $digits;
        }

        return $p;
    }
}
