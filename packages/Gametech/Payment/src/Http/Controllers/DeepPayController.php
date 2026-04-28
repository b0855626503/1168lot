<?php

declare(strict_types=1);

namespace Gametech\Payment\Http\Controllers;

use App\Events\RealTimeNewMessage;
use Carbon\Carbon;
use Gametech\Auto\Jobs\UpdateBalanceDeepPay;
use Gametech\Core\Repositories\CheckCaseRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Libraries\DeepPay;
use Gametech\Payment\Repositories\BankAccountRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\BankRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeepPayController extends AppBaseController
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
        $data = $this->repository->findOneWhere(['detail' => $id]) ?: $this->repository->findOneWhere(['txid' => $id]);

        if (! $data) {
            return response()->json(['success' => false, 'message' => 'ไม่พบรายการฝากเงิน'], 404);
        }

        $authMember = auth()->guard('customer')->user();
        if ($authMember && (string) $data->username !== (string) $authMember->user_name) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึงรายการนี้'], 403);
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
                'expired_date' => ! empty($data->expired_date) ? Carbon::parse($data->expired_date)->toDateTimeString() : null,
            ],
        ]);
    }

    public function deposit(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:1']);

        $member = auth()->guard('customer')->user();
        if (! $member) {
            return response()->json(['success' => false, 'msg' => 'unauthenticated'], 401);
        }

        $api = new DeepPay();
        $amount = (float) $request->input('amount');
        $amountText = number_format($amount, 2, '.', '');

        $systemBankCode = (int) config('deeppay.system_bank_code', 313);
        $bankAccount = $this->bankAccountRepository->findOneWhere([
            'banks' => $systemBankCode,
            'bank_type' => 1,
            'enable' => 'Y',
            'status_auto' => 'Y',
        ]);

        if (! $bankAccount) {
            return response()->json(['success' => false, 'msg' => __('app.topup.fail')]);
        }

        $min = $this->getDepositMin($api);
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
            return response()->json(['success' => false, 'msg' => 'ข้อมูลบัญชีสมาชิกไม่ครบถ้วน']);
        }

        $orderId = 'DDEP-'.str_pad((string) $member->code, 6, '0', STR_PAD_LEFT).'-'.date('YmdHis');

        $amountList = $api->amountList([
            'member_code' => (string) $member->code,
            'dep_amount' => $amountText,
            'bank_code' => $memberBankCode,
            'account_no' => $memberAccountNo,
        ]);

        if (! data_get($amountList, 'success')) {
            Log::channel('deeppay_deposit_create')->error('[DEEPPAY] amount_list failed', [
                'member_code' => (int) $member->code,
                'amount' => $amountText,
                'resp' => $amountList,
            ]);

            return response()->json(['success' => false, 'msg' => (string) data_get($amountList, 'msg', 'amount_list failed')]);
        }

        $selected = $this->selectP2pToken((array) data_get($amountList, 'data.data.p2p', []), $amount);
        if (! $selected) {
            return response()->json(['success' => false, 'msg' => 'ไม่พบรายการฝาก P2P ที่พร้อมใช้งาน']);
        }

        $selectedAmount = (float) data_get($selected, 'amount', $amount);
        $callbackUrl = (string) (config('deeppay.deposit_callback_url') ?: route('api.deeppay.deposit.callback'));

        $payload = [
            'member_code' => (string) $member->user_name,
            'currency' => (string) config('deeppay.currency', 'THB'),
            'order_id' => $orderId,
            'hash' => $api->hashOrderId($orderId),
            'token' => (string) data_get($selected, 'token'),
            'amount' => number_format($selectedAmount, 2, '.', ''),
            'bank_code' => $memberBankCode,
            'bank_account_no' => $memberAccountNo,
            'bank_account_name' => $memberAccountName,
            'callback_url' => $callbackUrl,
        ];

        Log::channel('deeppay_deposit_create')->info('[DEEPPAY] create deposit start', ['payload' => $payload]);

        $resp = $api->deposit($payload);

        Log::channel('deeppay_deposit_create')->info('[DEEPPAY] create deposit response', ['response' => $resp]);

        if (! data_get($resp, 'success')) {
            Log::channel('deeppay_deposit_create')->error('[DEEPPAY] create deposit failed', [
                'txid' => $orderId,
                'resp' => $resp,
            ]);

            return response()->json(['success' => false, 'msg' => (string) data_get($resp, 'msg', 'create deposit failed')]);
        }

        $provider = (array) data_get($resp, 'data.data', []);
        $destUrl = (string) data_get($provider, 'dest_url', '');
        $txnNo = (string) data_get($provider, 'txn_no', '');
        $providerAmount = (float) data_get($provider, 'amount', $selectedAmount);
        $expiredDate = now()->addMinutes(30);

        try {
            UpdateBalanceDeepPay::dispatch()->delay(5)->onQueue('topup');

            $this->repository->create([
                'bank_code' => $bankAccount->banks,
                'method' => 1,
                'txid' => $orderId,
                'detail' => $txnNo !== '' ? $txnNo : $orderId,
                'amount' => number_format($amount, 2, '.', ''),
                'payamount' => number_format($providerAmount, 2, '.', ''),
                'username' => trim((string) $member->user_name),
                'name' => (string) $member->name,
                'url' => $destUrl !== '' ? $destUrl : null,
                'qrcode' => null,
                'status' => 'pending',
                'expired_date' => $expiredDate,
                'user_create' => (string) $member->name,
                'user_update' => (string) $member->name,
            ]);
        } catch (\Throwable $e) {
            Log::channel('deeppay_deposit_create')->error('[DEEPPAY] create check_case failed', [
                'txid' => $orderId,
                'error' => $e->getMessage(),
                'provider' => $provider,
            ]);

            return response()->json(['success' => false, 'msg' => 'create check_case failed: '.$e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'msg' => __('app.topup.create'),
            'url' => $destUrl,
            'target' => 'blank',
            'amount' => $providerAmount,
        ]);
    }

    public function deposit_callback(Request $request)
    {
        $payload = $request->all();
        Log::channel('deeppay_deposit_callback')->info('[DEEPPAY] Deposit callback', $payload);

        $orderId = (string) data_get($payload, 'order_id', '');
        if ($orderId === '') {
            return response()->json(['success' => true]);
        }

        $case = $this->repository->findOneWhere(['txid' => $orderId]);
        if (! $case) {
            return response()->json(['success' => true]);
        }

        $api = new DeepPay();
        $txnNo = (string) data_get($payload, 'txn_no', '');
        $incoming = $api->normalizeStatus((string) data_get($payload, 'status', 'pending'));

        if ((bool) config('deeppay.verify_callback_via_transaction', true) && $txnNo !== '') {
            $verify = $api->depositTransaction($txnNo, $orderId);
            if (data_get($verify, 'success')) {
                $incoming = $api->normalizeStatus((string) data_get($verify, 'data.data.status', $incoming));
                $payload = array_merge($payload, (array) data_get($verify, 'data.data', []));
            } else {
                Log::channel('deeppay_deposit_callback')->warning('[DEEPPAY] callback verify failed', [
                    'order_id' => $orderId,
                    'txn_no' => $txnNo,
                    'verify' => $verify,
                ]);
            }
        }

        $current = strtolower((string) $case->status);
        if (in_array($current, ['completed', 'failed', 'rejected', 'refunded'], true)) {
            return response()->json(['success' => true]);
        }

        if ($incoming !== 'pending') {
            $this->repository->update(['status' => $incoming], $case->code);
        }

        if ($incoming !== 'completed') {
            return response()->json(['success' => true]);
        }

        UpdateBalanceDeepPay::dispatch()->delay(5)->onQueue('topup');

        $amount = (float) (data_get($payload, 'amount') ?: $case->payamount ?: $case->amount);
        $member = $this->memberRepository->findOneWhere(['user_name' => $case->username]);
        if (! $member) {
            return response()->json(['success' => true]);
        }

        $systemBankCode = (int) config('deeppay.system_bank_code', 313);
        $bankAccount = $this->bankAccountRepository->findOneWhere([
            'banks' => $systemBankCode,
            'bank_type' => 1,
            'enable' => 'Y',
            'status_auto' => 'Y',
        ]);

        if (! $bankAccount) {
            return response()->json(['success' => true]);
        }

        $check = $this->bankPaymentRepository->findOneWhere(['txid' => $orderId]);
        if ($check) {
            return response()->json(['success' => true]);
        }

        $bank = $this->bankRepository->find($bankAccount->banks);
        $detail = ' REF ID : '.($txnNo !== '' ? $txnNo : data_get($payload, 'ref_id', '-'));
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
            'channel' => 'DeepPay',
            'value' => $amount,
            'tx_hash' => $hash,
            'txid' => $orderId,
            'status' => 0,
            'ip_admin' => request()->ip(),
            'member_topup' => $member->code,
            'remark_admin' => '',
            'emp_topup' => 0,
            'user_create' => 'รอระบบเติมอัตโนมัติ ทำรายการฝากเงินโดย DeepPay',
            'create_by' => 'SYSAUTO',
        ]);

        return response()->json(['success' => true]);
    }

    public function withdraw_callback(Request $request)
    {
        $payload = $request->all();
        Log::channel('deeppay_withdraw_callback')->info('[DEEPPAY] Withdraw callback', $payload);

        $orderId = (string) data_get($payload, 'order_id', '');
        if ($orderId === '') {
            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        $api = new DeepPay();
        $txnNo = (string) data_get($payload, 'txn_no', '');
        $incoming = $api->normalizeStatus((string) data_get($payload, 'status', 'pending'));

        if ((bool) config('deeppay.verify_callback_via_transaction', true) && $txnNo !== '') {
            $verify = $api->withdrawTransaction($txnNo, $orderId);
            if (data_get($verify, 'success')) {
                $incoming = $api->normalizeStatus((string) data_get($verify, 'data.data.status', $incoming));
                $payload = array_merge($payload, (array) data_get($verify, 'data.data', []));
            } else {
                Log::channel('deeppay_withdraw_callback')->warning('[DEEPPAY] withdraw callback verify failed', [
                    'order_id' => $orderId,
                    'txn_no' => $txnNo,
                    'verify' => $verify,
                ]);
            }
        }

        $case = $this->repository->findOneWhere(['txid' => $orderId]);
        if ($case) {
            $current = strtolower((string) $case->status);

            if (! in_array($current, ['completed', 'failed', 'reject', 'rejected', 'refunded'], true) && $incoming !== 'pending') {
                $this->repository->update(['status' => $incoming], $case->code);
            }
        }

        if ($incoming === 'pending') {
            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        $config = $this->getCoreConfig();

        if ($config->seamless === 'Y') {
            $order = app('Gametech\\Payment\\Repositories\\WithdrawRepository')->findOneWhere([
                'txid' => $orderId,
                'status_withdraw' => 'A',
            ]);
        } else {
            $order = app('Gametech\\Payment\\Repositories\\WithdrawRepository')->findOneWhere([
                'txid' => $orderId,
                'status_withdraw' => 'A',
            ]);
        }

        if (! $order) {
            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        $amount = (float) $order['amount'];
        $refund = (float) data_get($payload, 'refund', 0);
        $transfer = (float) data_get($payload, 'transfer', data_get($payload, 'amount', 0));

        if ($incoming === 'completed' && $refund <= 0) {
            UpdateBalanceDeepPay::dispatch()->delay(5)->onQueue('topup');

            $order->remark_admin = '[ Ref ID : '.($txnNo !== '' ? $txnNo : '-').' ] โอนให้ลูกค้าแล้ว (DeepPay)';
            $order->status = 1;
            $order->status_withdraw = 'C';
            $order->save();

            $bill = app('Gametech\\Payment\\Repositories\\BillRepository')->findOneWhere([
                'refer_code' => $order->code,
                'refer_table' => 'withdraws',
                'method' => 'WITHDRAW',
            ]);

            if ($bill) {
                $bill->complete = 'Y';
                $bill->save();
            }

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
                'remark' => 'รายการแจ้งถอนที่ '.$order->code.' / ไอดีที่ถอน : '.$order->member_user.' จำนวนเงิน '.$amount.' โอนเงินให้ลูกค้าแล้ว DeepPay '.$orderId.' [ '.($txnNo !== '' ? $txnNo : '-').' ]',
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
                'DeepPay '.$orderId.' โอนเงินให้ลูกค้าแล้ว ID : '.$order->member_user.' จำนวนเงิน '.$transfer.' รายการแจ้งถอนที่ '.$order->code,
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

        $datanew = [
            'refer_code' => $order->code,
            'refer_table' => 'withdraws',
            'remark' => 'คืนยอดจากการถอน '.$txnNo.' (DeepPay '.$incoming.')',
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

        if ($response) {
            $order->remark_admin = '[ Ref ID : '.($txnNo !== '' ? $txnNo : '-').' ] DeepPay '.$incoming.' transfer='.$transfer.' refund='.$refund.' ระบบคืนยอดให้ลูกค้าแล้ว';
            $order->status = 2;
            $order->status_withdraw = 'R';
            $order->save();

            $bill = app('Gametech\\Payment\\Repositories\\BillRepository')->findOneWhere([
                'refer_code' => $order->code,
                'refer_table' => 'withdraws',
                'method' => 'WITHDRAW',
            ]);

            if ($bill) {
                $bill->complete = 'R';
                $bill->save();
            }

            broadcast(new RealTimeNewMessage(
                'DeepPay ยกเลิกรายการแจ้งถอน ของ ID '.$order->member_user.' จำนวนเงิน '.$amount.' Ref ID '.($txnNo !== '' ? $txnNo : '-').' ระบบคืนยอดให้ลูกค้าแล้ว ('.$incoming.')',
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

            return response()->json(['code' => 0, 'msg' => 'success']);
        }

        Log::channel('deeppay_withdraw_callback')->error('[DEEPPAY] withdraw rollback failed', [
            'order_id' => $orderId,
            'withdraw_code' => $order->code,
            'member_user' => $order->member_user,
            'amount' => $amount,
            'incoming' => $incoming,
            'refund' => $refund,
            'transfer' => $transfer,
            'payload' => $payload,
        ]);

        $order->remark_admin = '[ Ref ID : '.($txnNo !== '' ? $txnNo : '-').' ] DeepPay '.$incoming.' transfer='.$transfer.' refund='.$refund.' คืนยอดไม่สำเร็จ ต้องตรวจสอบ';
        $order->save();

        return response()->json(['code' => 0, 'msg' => 'success']);
    }

    private function getDepositMin(DeepPay $api): float
    {
        $min = (float) config('deeppay.min_deposit', 100);

        try {
            $balance = $api->balance((string) config('deeppay.currency', 'THB'));
            $providerMin = (float) data_get($balance, 'data.data.min_transfer', 0);
            if ($providerMin > 0) {
                return $providerMin;
            }
        } catch (\Throwable $e) {
            Log::channel('deeppay_deposit_create')->warning('[DEEPPAY] get min_transfer failed', ['error' => $e->getMessage()]);
        }

        return $min;
    }

    private function selectP2pToken(array $items, float $requestedAmount): ?array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            if ((string) data_get($item, 'token', '') === '') {
                continue;
            }

            $normalized[] = $item;
        }

        foreach ($normalized as $item) {
            if (abs((float) data_get($item, 'amount', 0) - $requestedAmount) < 0.01) {
                return $item;
            }
        }

        return $normalized[0] ?? null;
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
