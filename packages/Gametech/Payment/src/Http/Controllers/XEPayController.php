<?php

namespace Gametech\Payment\Http\Controllers;

use App\Events\RealTimeNewMessage;
use Carbon\Carbon;
use Gametech\Auto\Jobs\UpdateBalanceXEPay;
use Gametech\Core\Repositories\CheckCaseRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Libraries\XEPay;
use Gametech\Payment\Repositories\BankAccountRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\BankRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XEPayController extends AppBaseController
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
        $this->bankPaymentRepository = $bankPaymentRepository;
        $this->bankAccountRepository = $bankAccountRepository;
    }

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

        $view = (string) config('xepay.deposit_view', 'topup.box.onpay_new');

        return view($view, compact('data', 'member', 'banks'));
    }

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

        $systemBankCode = (int) config('xepay.system_bank_code', 314);
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

        $min = (float) config('xepay.min_deposit', 100);
        if ($bankAccount && (float) ($bankAccount->deposit_min ?? 0) > 0) {
            $min = (float) $bankAccount->deposit_min;
        }

        if ((float) $amount < $min) {
            return response()->json([
                'success' => false,
                'msg' => __('app.topup.min_deposit', ['amount' => $min]),
            ]);
        }

        $api = new XEPay();
        $txid = 'XEDEP-' . str_pad((string) $member->code, 6, '0', STR_PAD_LEFT) . '-' . date('YmdHis');
        $providerBankCode = $api->resolveBankCode($member->bank_code ?? '');
        $notifyUrl = (string) (config('xepay.deposit_notify_url') ?: route('api.xepay.deposit.callback', [], true));
        $returnUrl = (string) config('xepay.return_url', '');

        $payload = [
            'merNo' => (string) config('xepay.mer_no'),
            'tradeNo' => trim($txid),
            'cType' => (string) config('xepay.c_type'),
            'playerWalletAddr' => 'TQ9w3Yk9Vn9Zc8XzF2Q6m4K8r7L5p3A1B2',
            'orderAmount' => (string) $amount,
            'playerId' => trim((string) $member->user_name),
            'playerName' => trim((string) $member->name),
            'notifyUrl' => $notifyUrl,
        ];

        if ($returnUrl !== '') {
            $payload['returnUrl'] = $returnUrl;
        }

        $verifyChannelNo = config('xepay.verify_channel_no');
        if ($verifyChannelNo !== null && $verifyChannelNo !== '') {
            $payload['VerifyChannelNo'] = (string) $verifyChannelNo;
        }

        $payload['sign'] = XEPay::signDeposit(
            (string) $payload['merNo'],
            (string) $payload['tradeNo'],
            (string) $payload['orderAmount'],
            (string) config('xepay.api_key')
        );

        Log::channel('xepay_deposit_create')->info('[XEPAY] create deposit start', [
            'txid' => $txid,
            'member_code' => $member->code,
            'amount' => $amount,
        ]);

        $resp = $api->createDeposit($payload);
        if (!data_get($resp, 'success')) {
            Log::channel('xepay_deposit_create')->error('[XEPAY] create deposit failed', [
                'txid' => $txid,
                'resp' => $resp,
            ]);

            return response()->json([
                'success' => false,
                'msg' => (string) data_get($resp, 'msg', 'create deposit failed'),
            ]);
        }

        $provider = (array) data_get($resp, 'data', []);
        $params = (array) data_get($provider, 'Params', []);

        $detail = (string) (data_get($provider, 'oid', '') ?: $txid);
        $payPage = (string) data_get($provider, 'PayPage', '');
        $qrCodeUrl = (string) data_get($params, 'qrcode_url', '');
        $payAmount = (string) (data_get($params, 'money', '') ?: $amount);

        $this->repository->create([
            'bank_code' => $bankAccount->banks,
            'method' => 1,
            'txid' => $txid,
            'detail' => $detail,
            'amount' => $amount,
            'payamount' => $payAmount,
            'username' => trim((string) $member->user_name),
            'name' => (string) $member->name,
            'url' => ($payPage !== '' ? $payPage : null),
            'qrcode' => ($qrCodeUrl !== '' ? $qrCodeUrl : null),
            'status' => 'pending',
            'user_create' => (string) $member->name,
            'user_update' => (string) $member->name,
        ]);

        return response()->json([
            'success' => true,
            'msg' => __('app.topup.create'),
            'url' => route('api.xepay.index', ['id' => $detail]),
            'payment_url' => $payPage,
        ]);
    }

    public function deposit_callback(Request $request)
    {
        $payload = $request->all();
        Log::channel('xepay_deposit_callback')->info('[XEPAY] Deposit callback', $payload);

        $tradeNo = (string) data_get($payload, 'tradeNo', '');
        $topupAmount = (string) data_get($payload, 'topupAmount', '');
        $providerSign = strtolower((string) data_get($payload, 'sign', ''));
        $expected = XEPay::signDepositCallback($tradeNo, $topupAmount, (string) config('xepay.api_key'));

        if ($tradeNo === '' || $providerSign === '' || $providerSign !== strtolower($expected)) {
            Log::channel('xepay_deposit_callback')->warning('[XEPAY] invalid deposit callback sign', [
                'tradeNo' => $tradeNo,
                'payload' => $payload,
            ]);

            return response('FAIL', 400);
        }

        $case = $this->repository->findOneWhere(['txid' => $tradeNo]);
        if (!$case) {
            return response('SUCCESS', 200);
        }

        $tradeStatus = (string) data_get($payload, 'tradeStatus', '0');
        $incoming = 'pending';
        if ($tradeStatus === '1') {
            $incoming = 'completed';
        } elseif ($tradeStatus === '9') {
            $incoming = 'reviewing';
        }

        $current = strtolower((string) $case->status);
        if (!in_array($current, ['completed', 'failed'], true) && $incoming !== 'pending') {
            $this->repository->update(['status' => $incoming], $case->code);
        }

        if ($incoming === 'completed') {
            UpdateBalanceXEPay::dispatch()->delay(5)->onQueue('topup');

            $amount = (float) ($topupAmount !== '' ? $topupAmount : $case->amount);
            $member = $this->memberRepository->findOneWhere(['user_name' => $case->username]);
            $systemBankCode = (int) config('xepay.system_bank_code', 314);
            $bankAccount = $this->bankAccountRepository->findOneWhere([
                'banks' => $systemBankCode,
                'bank_type' => 1,
                'enable' => 'Y',
                'status_auto' => 'Y',
            ]);

            if ($member && $bankAccount) {
                $bank = $this->bankRepository->find($bankAccount->banks);
                $detail = ' REF ID : ' . $tradeNo;
                $hash = md5($bankAccount->code . $amount . $detail);
                $datenow = now()->toDateTimeString();

                $data = [
                    'bank' => strtolower($bank->shortcode . '_' . $bankAccount->acc_no),
                    'detail' => $detail . ' จำนวน ' . $amount,
                    'account_code' => $bankAccount->code,
                    'autocheck' => 'W',
                    'bankstatus' => 1,
                    'bank_name' => $bank->shortcode,
                    'bank_time' => $datenow,
                    'channel' => 'QR',
                    'value' => $amount,
                    'tx_hash' => $hash,
                    'txid' => $tradeNo,
                    'status' => 0,
                    'ip_admin' => request()->ip(),
                    'member_topup' => $member->code,
                    'remark_admin' => '',
                    'emp_topup' => 0,
                    'user_create' => 'รอระบบเติมอัตโนมัติ ทำรายการฝากเงินโดย XEPay',
                    'create_by' => 'SYSAUTO',
                ];

                $check = $this->bankPaymentRepository->findOneWhere(['txid' => $tradeNo]);
                if (!$check) {
                    $this->bankPaymentRepository->create($data);
                }
            }
        }

        return response('SUCCESS', 200);
    }

    public function withdraw_callback(Request $request)
    {
        $config = $this->getCoreConfig();
        $payload = $request->all();

        Log::channel('xepay_withdraw_callback')->info('[XEPAY] Withdraw callback', $payload);

        $tradeNo = (string) data_get($payload, 'tradeNo', '');
        $orderAmount = (string) data_get($payload, 'orderAmount', '');
        $providerSign = strtolower((string) data_get($payload, 'sign', ''));
        $expected = XEPay::signWithdrawCallback($tradeNo, $orderAmount, (string) config('xepay.api_key'));

        if ($tradeNo === '' || $providerSign === '' || $providerSign !== strtolower($expected)) {
            Log::channel('xepay_withdraw_callback')->warning('[XEPAY] invalid withdraw callback sign', [
                'tradeNo' => $tradeNo,
                'payload' => $payload,
            ]);

            return response('FAIL', 400);
        }

        $tradeStatus = (string) data_get($payload, 'tradeStatus', '0');
        $incoming = 'pending';
        if ($tradeStatus === '1') {
            $incoming = 'completed';
        } elseif ($tradeStatus === '-1') {
            $incoming = 'failed';
        }

        $case = $this->repository->findOneWhere(['txid' => $tradeNo]);
        if ($case) {
            $current = strtolower((string) $case->status);
            if (!in_array($current, ['completed', 'failed'], true) && $incoming !== 'pending') {
                $this->repository->update(['status' => $incoming], $case->code);
            }
        }

        if ($incoming === 'pending') {
            return response('SUCCESS', 200);
        }

        if ($config->seamless === 'Y') {
            $data = app('Gametech\\Payment\\Repositories\\WithdrawSeamlessRepository')
                ->findOneWhere(['txid' => $tradeNo, 'status_withdraw' => 'A']);
        } else {
            $data = app('Gametech\\Payment\\Repositories\\WithdrawRepository')
                ->findOneWhere(['txid' => $tradeNo, 'status_withdraw' => 'A']);
        }

        if (!$data) {
            return response('SUCCESS', 200);
        }

        $amount = $data['amount'];
        $message = (string) data_get($payload, 'message', '');

        if ($incoming === 'completed') {
            UpdateBalanceXEPay::dispatch()->delay(5)->onQueue('topup');

            $data->remark_admin = '[ Ref ID : ' . $tradeNo . ' ] โอนให้ลูกค้าแล้ว (XEPay) ' . $message;
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
                'remark' => 'รายการแจ้งถอนที่ ' . $data->code . ' / ไอดีที่ถอน : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' โอนเงินให้ลูกค้าแล้ว XEPay ' . $tradeNo,
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
                'XEPay ' . $tradeNo . ' โอนเงินให้ลูกค้าแล้ว ID : ' . $data->member_user . ' จำนวนเงิน ' . $amount . ' รายการแจ้งถอนที่ ' . $data->code,
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

            return response('SUCCESS', 200);
        }

        if ($incoming === 'failed') {
            if ($config->seamless === 'Y') {
                $rollback = [
                    'refer_code' => $data->code,
                    'refer_table' => 'withdraws',
                    'remark' => 'คืนยอดจากการถอน ' . $tradeNo . ' (XEPay failed)',
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
                $response = app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->setWalletSeamlessWithdraw($rollback);
            } else {
                $rollback = [
                    'refer_code' => $data->code,
                    'refer_table' => 'withdraws',
                    'remark' => 'คืนยอดจากการถอน ' . $tradeNo . ' (XEPay failed)',
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
                $response = app('Gametech\\Member\\Repositories\\MemberCreditLogRepository')->setWalletSingleWithdraw($rollback);
            }

            if ($response) {
                $data->remark_admin = '[ Ref ID : ' . $tradeNo . ' ] ถอนล้มเหลว (XEPay) ' . $message;
                $data->status_withdraw = 'X';
                $data->save();
            }
        }

        return response('SUCCESS', 200);
    }

    public function expire($txid)
    {
        $case = $this->repository->findOneWhere(['detail' => $txid]);
        if ($case && strtolower((string) $case->status) !== 'completed') {
            $this->repository->update([
                'status' => 'expired',
            ], $case->code);
        }

        return response()->json(['success' => true]);
    }

    public function checkStatus($txid)
    {
        $case = $this->repository->findOneWhere(['detail' => $txid]);

        if (!$case) {
            return response()->json(['success' => false, 'status' => 'NOT_FOUND']);
        }

        if ($case->expired_date) {
            try {
                $expired = Carbon::parse($case->expired_date);
                if ($expired->lt(now()) && strtolower((string) $case->status) === 'pending') {
                    $this->repository->update(['status' => 'expired'], $case->code);
                    $case->status = 'expired';
                }
            } catch (\Throwable $e) {
                // ignore parse issue
            }
        }

        return response()->json([
            'success' => true,
            'status' => $case->status,
        ]);
    }
}
