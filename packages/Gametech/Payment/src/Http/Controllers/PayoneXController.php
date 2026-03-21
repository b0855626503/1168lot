<?php

namespace Gametech\Payment\Http\Controllers;

use App\Events\RealTimeNewMessage;
use Carbon\Carbon;
use Gametech\Auto\Jobs\UpdateBalancePayoneX;
use Gametech\Core\Repositories\CheckCaseRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Libraries\PayoneX;
use Gametech\Payment\Models\PaymentProviderAccount;
use Gametech\Payment\Repositories\BankAccountRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\BankRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayoneXController extends AppBaseController
{
    protected $_config;

    protected $repository;
    protected $memberRepository;
    protected $bankRepository;
    protected $bankAccountRepository;
    protected $bankPaymentRepository;

    protected $token;
    protected $customer_uuid;

    public function __construct(
        CheckCaseRepository   $repository,
        MemberRepository      $memberRepository,
        BankAccountRepository $bankAccountRepository,
        BankPaymentRepository $bankPaymentRepository,
        BankRepository        $bankRepository
    ) {
        $this->_config = request('_config');

        $this->repository = $repository;
        $this->memberRepository = $memberRepository;
        $this->bankRepository = $bankRepository;
        $this->bankAccountRepository = $bankAccountRepository;
        $this->bankPaymentRepository = $bankPaymentRepository;
    }

    public function index($id)
    {
        $banks = $this->bankRepository->findWhere(['enable' => 'Y'])->pluck('name_th', 'shortcode')->toArray();
        $data = $this->repository->findOneWhere(['detail' => $id]);
        $member = $this->memberRepository->findOneWhere(['user_name' => $data->username]);

        return view('topup.box.payonex_new', compact('data', 'member', 'banks'));
    }

    public function indextest($id)
    {
        $data = $this->repository->findOneWhere(['detail' => $id]);
        $member = $this->memberRepository->findOneWhere(['user_name' => $data->username]);

        return view('topup.box.payonex_new', compact('data', 'member'));
    }

    /**
     * โหมด auth:
     * - single: auth ใหม่ทุกครั้ง (1 token ต่อ 1 รายการ)  [default]
     * - db: cache token ลง bank_accounts.device_id + expired_date แล้ว reuse จนหมดอายุ
     *
     * ตั้งค่า:
     *   config/payonex.php -> 'auth_mode' => 'single' | 'db'
     * หรือ env:
     *   PAYONEX_AUTH_MODE=single|db (ถ้า config คุณผูก env ไว้)
     */
    protected function resolveAuthMode(): string
    {
        $mode = (string) config('payonex.auth_mode', 'single');
        $mode = strtolower(trim($mode));
        return in_array($mode, ['single', 'db'], true) ? $mode : 'single';
    }

    /**
     * TTL ของ token ในโหมด db (ชั่วโมง) - default 24
     */
    protected function resolveDbTokenTtlHours(): int
    {
        $h = (int) config('payonex.db_token_ttl_hours', 24);
        return $h > 0 ? $h : 24;
    }

    /**
     * ทำ auth แล้ว set $this->token ให้พร้อมใช้งาน
     * - โหมด single: ยิง authenticate ใหม่เสมอ
     * - โหมด db: reuse token ใน bank_account ถ้ายังไม่หมดอายุ
     *
     * @return bool success
     */
    public function auth(): bool
    {
        $api = new PayoneX();
        $authUrl = config('payonex.api_url') . '/authenticate';

        $mode = $this->resolveAuthMode();
        $this->token = null;

        // -------------------------
        // MODE: single (1 token : 1 รายการ)
        // -------------------------
        if ($mode === 'single') {
            $result = $api->auth($authUrl);
            if (($result['success'] ?? false) === true && !empty($result['token'])) {
                $this->token = (string) $result['token'];
                return true;
            }

            Log::channel('payonex_auth')->error('PayoneX auth failed (single mode)', [
                'mode' => $mode,
                'url' => $authUrl,
                'result' => $result,
            ]);

            return false;
        }

        // -------------------------
        // MODE: db (cache token in bank_accounts)
        // -------------------------
        $bank_account = $this->bankAccountRepository->findOneWhere([
            'banks' => 307,
            'bank_type' => 1,
            'enable' => 'Y',
            'status_auto' => 'Y',
        ]);

        if (!$bank_account) {
            Log::channel('payonex_auth')->error('PayoneX auth failed: bank_account not found (db mode)', [
                'mode' => $mode,
            ]);
            return false;
        }

        $now = now();
        $dbToken = (string) ($bank_account->device_id ?? '');
        $dbExpired = $bank_account->expired_date ? Carbon::parse($bank_account->expired_date) : null;

        // ใช้ token ใน DB ถ้ายังไม่หมดอายุ
        if ($dbToken !== '' && $dbExpired && $dbExpired->greaterThan($now)) {
            $this->token = $dbToken;
            return true;
        }

        // ไม่มี token หรือหมดอายุ -> auth ใหม่แล้วเก็บลง DB
        $result = $api->auth($authUrl);
        if (($result['success'] ?? false) === true && !empty($result['token'])) {
            $token = (string) $result['token'];

            $bank_account->device_id = $token;
            $bank_account->expired_date = $now->copy()->addHours($this->resolveDbTokenTtlHours())->toDateTimeString();
            $bank_account->save();

            $this->token = $token;
            return true;
        }

        Log::channel('payonex_auth')->error('PayoneX auth failed (db mode)', [
            'mode' => $mode,
            'url' => $authUrl,
            'result' => $result,
        ]);

        return false;
    }

    public function deposit(Request $request)
    {
        $api = new PayoneX();

        // ✅ auth ตามโหมดที่ตั้ง
        if (!$this->auth() || !$this->token) {
            return response()->json([
                'success' => false,
                'msg' => __('app.status.error'),
            ]);
        }

        $request->validate([
            'amount' => 'required|numeric',
        ]);

        $member = auth()->guard('customer')->user();

        Log::channel('payonex_deposit_create')->info('เริ่มสร้างรายการฝาก', [
            'debug' => 'start',
            'auth_mode' => $this->resolveAuthMode(),
        ]);

        $bank_account = $this->bankAccountRepository->findOneWhere([
            'banks' => 307,
            'bank_type' => 1,
            'enable' => 'Y',
            'status_auto' => 'Y',
        ]);

        if (!$bank_account) {
            return response()->json([
                'success' => false,
                'msg' => __('app.topup.fail'),
            ]);
        }

        $amount = (float) $request->input('amount');
        $amount = number_format($amount, 2, '.', '');

        $min_deposit = config('payonex.min_deposit', 100);
        if ($amount < $min_deposit) {
            return response()->json([
                'success' => false,
                'msg' => __('app.topup.min_deposit', ['amount' => $min_deposit]),
            ]);
        }

        $transactionId = 'PDEP-' . str_pad($member->code, 6, '0', STR_PAD_LEFT) . '-' . date('YmdHis');

        $bankAccountNumber = $member->acc_no;
        $bankName = $api->Banks($member->bank_code);

        if ($bankName === false) {
            return response()->json([
                'success' => false,
                'msg' => __('app.topup.wrong_bank'),
            ]);
        }

        // ✅ ย้ายจาก payments_customer -> payment_provider_accounts
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

        $url = config('payonex.api_url') . '/transactions/deposit/request';
        $response = $api->create($url, $param, $this->token);

        if (($response['success'] ?? false) === true) {
            // ✅ qrExpireTime เป็น milliseconds (13 หลัก) -> ต้อง /1000
            $qrExpireMs = (int) data_get($response, 'data.qrExpireTime', 0);
            $qrExpireAt = $qrExpireMs > 0
                ? Carbon::createFromTimestamp((int) floor($qrExpireMs / 1000))->timezone('Asia/Bangkok')
                : now()->addMinutes(20)->timezone('Asia/Bangkok');

            $this->repository->create([
                'bank_code' => $bank_account->banks,
                'method' => 1,
                'txid' => $transactionId,
                'amount' => $amount,
                'payamount' => data_get($response, 'data.transferAmount'),
                'username' => trim((string) $member->user_name),
                'name' => $member->name,
                'detail' => data_get($response, 'data.uuid'),
                'url' => data_get($response, 'data.link'),
                'qrcode' => data_get($response, 'data.qrCode'),
                'status' => 'PENDING',
                'expired_date' => $qrExpireAt->toDateTimeString(),
                'user_create' => $member->name,
                'user_update' => $member->name,
            ]);

            return response()->json([
                'success' => true,
                'url' => route('api.payonex.index', ['id' => data_get($response, 'data.uuid')]),
                'qr' => data_get($response, 'data.qrCode'),
                'txid' => $transactionId,
                'uuid' => $transactionId,
                'msg' => __('app.topup.create'),
            ]);
        }

        return response()->json([
            'success' => false,
            'msg' => (string) ($response['msg'] ?? __('app.topup.fail')),
        ]);
    }

    public function callback(Request $request)
    {
        UpdateBalancePayoneX::dispatch()->onQueue('topup');
        $message = $request->all();

        if (($message['type'] ?? '') === 'deposit') {
            return $this->deposit_callback($message);
        }

        return $this->withdraw_callback($message);
    }

    public function deposit_callback($message)
    {
        $datenow = now()->toDateTimeString();

        Log::channel('payonex_deposit_callback')->info('Callback การฝาก', $message);

        $refId = $message['uuid'] ?? '';
        $transactionId = $message['referenceId'] ?? '';
        $status = $message['status'] ?? '';

        $case = $this->repository->findOneWhere(['txid' => $transactionId]);
        if ($case) {
            $this->repository->update(['status' => $status], $case->code);
        }

        if ($status === 'SUCCESS' && $case) {
            $amount = $message['amount'] ?? 0;
            $payAmount = $amount;

            $member = $this->memberRepository->findOneWhere(['user_name' => $case->username]);
            $bank_account = $this->bankAccountRepository->findOneWhere(['banks' => 307]);
            $bank = $this->bankRepository->find($bank_account->banks);

            if ($member && $bank_account) {
                $transferBank = $bank_account->banks;
                $transferBankAccountNumber = $bank_account->acc_no;
                $transferBankAccount = $bank_account->acc_name;
                $charge = 0;
                $txid = $transactionId;

                $detail = '[ Ref No :' . $refId . ' ]';
                $hash = md5($bank_account->code . $amount . $detail);
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
                    'txid' => $txid,
                    'status' => 0,
                    'ip_admin' => request()->ip(),
                    'member_topup' => $member->code,
                    'remark_admin' => '',
                    'emp_topup' => 0,
                    'user_create' => 'รอระบบเติมอัตโนมัติ ทำรายการฝากเงินโดย PayoneX QR',
                    'create_by' => 'SYSAUTO',
                ];

                $check = $this->bankPaymentRepository->findOneWhere(['txid' => $txid]);
                if (!$check) {
                    $this->bankPaymentRepository->create($data);
                }

            }
        }

        return response()->json(['success' => true]);
    }

    public function withdraw_callback($message)
    {
        $config = $this->getCoreConfig();
        $datenow = now()->toDateTimeString();

        Log::channel('payonex_withdraw_callback')->info('Callback การฝาก', $message);

        $refId = $message['uuid'] ?? '';
        $transactionId = $message['referenceId'] ?? '';
        $status = $message['status'] ?? '';

        $case = $this->repository->findOneWhere(['txid' => $transactionId]);
        if ($case) {
            $this->repository->update(['status' => $status], $case->code);
        }

        if ($config->seamless == 'Y') {
            $data = app('Gametech\Payment\Repositories\WithdrawSeamlessRepository')->findOneWhere(['txid' => $transactionId, 'status_withdraw' => 'A']);
        } else {
            $data = app('Gametech\Payment\Repositories\WithdrawRepository')->findOneWhere(['txid' => $transactionId, 'status_withdraw' => 'A']);
        }

        $amount = $data['amount'] ?? 0;

        if ($status === 'SUCCESS') {
            $data->remark_admin = '[ Ref No :' . $refId . ' ] โอนให้ลุกค้าแล้ว ';
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
                'remark' => 'รายการแจ้งถอนที่ ' . $data->code . ' / ไอดีที่ถอน : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' โอนเงินให้ลูกค้าแล้ว PayoneX ' . $transactionId . ' [ ' . ($refId !== '' ? $refId : '-') . ' ]',
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
                'PayoneX ' . $transactionId . ' โอนเงินให้ลูกค้าแล้ว ID : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' รายการแจ้งถอนที่ ' . $data->code,
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
        } else {
            if ($config->seamless == 'Y') {
                $datanew = [
                    'refer_code' => $data->code,
                    'refer_bank' => $data->bank_code,
                    'refer_acc' => $data->bank_account,
                    'refer_bank_number' => $data->bank_number,
                    'member_code' => $data->member_code,
                    'member_user' => $data->member_user,
                    'amount' => $data->amount,
                    'status_withdraw' => 'N',
                    'withdraw_type' => $data->withdraw_type,
                    'remark_admin' => $data->remark_admin,
                    'status' => $data->status,
                    'admin_code' => $data->admin_code,
                    'txid' => $data->txid,
                    'created_at' => $datenow,
                    'updated_at' => $datenow,
                ];
                app('Gametech\Payment\Repositories\WithdrawSeamlessNewRepository')->create($datanew);
            } else {
                $datanew = [
                    'refer_code' => $data->code,
                    'refer_bank' => $data->bank_code,
                    'refer_acc' => $data->bank_account,
                    'refer_bank_number' => $data->bank_number,
                    'member_code' => $data->member_code,
                    'member_user' => $data->member_user,
                    'amount' => $data->amount,
                    'status_withdraw' => 'N',
                    'withdraw_type' => $data->withdraw_type,
                    'remark_admin' => $data->remark_admin,
                    'status' => $data->status,
                    'admin_code' => $data->admin_code,
                    'txid' => $data->txid,
                    'created_at' => $datenow,
                    'updated_at' => $datenow,
                ];
                app('Gametech\Payment\Repositories\WithdrawNewRepository')->create($datanew);
            }

            $data->remark_admin = '[ Ref No :' . $refId . ' ] โอนเงินไม่สำเร็จ ';
            $data->status_withdraw = 'F';
            $data->save();

            broadcast(new RealTimeNewMessage(
                'PayoneX ' . $transactionId . ' โอนเงินให้ลูกค้าไม่สำเร็จ ID : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' รายการแจ้งถอนที่ ' . $data->code,
                [
                    'ui' => 'toast',
                    'as' => 'RealTime.Message.All',
                    'toast' => [
                        'className' => 'gt-toast gt-toast-danger',
                        'duration' => 30000,
                        'gravity' => 'top',
                        'position' => 'right',
                        'avatar' => '/assets/admin/icons/alert.webp',
                    ],
                ]
            ));
        }

        return response()->json(['success' => true]);
    }

    public function expire($txid)
    {
        $case = $this->repository->findOneWhere(['detail' => $txid]);
        if ($case && $case->status !== 'SUCCESS') {
            $this->repository->update([
                'status' => 'EXPIRED',
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
}
