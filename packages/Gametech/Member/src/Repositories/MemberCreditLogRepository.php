<?php

namespace Gametech\Member\Repositories;

use App\Notifications\RealTimeNotification;
use Gametech\Core\Eloquent\Repository;
use Gametech\Game\Repositories\GameUserEventRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser;
use Gametech\Member\Models\MemberCreditLog;
use Gametech\Promotion\Repositories\PromotionRepository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MemberCreditLogRepository extends Repository
{
    use ActivityLoggerUser;

    protected $promotionRepository;

    private $memberRepository;

    private $gameUserRepository;

    private $gameUserEventRepository;

    public function __construct(
        MemberRepository $memberRepo,
        GameUserRepository $gameUserRepo,
        GameUserEventRepository $gameUserEventRepo,
        PromotionRepository $promotionRepo,
        App $app
    ) {
        $this->memberRepository = $memberRepo;
        $this->gameUserRepository = $gameUserRepo;
        $this->gameUserEventRepository = $gameUserEventRepo;
        $this->promotionRepository = $promotionRepo;
        parent::__construct($app);
    }

    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return MemberCreditLog::class;

    }

    public function setBonus(array $data): bool
    {

        $ip = request()->ip();
        $credit_balance = 0;
        $member_code = $data['member_code'];
        $amount = $data['amount'];
        $method = $data['method'];
        $kind = $data['kind'];
        $remark = $data['remark'];
        $emp_code = $data['emp_code'];
        $emp_name = $data['emp_name'];
        $refer_code = $data['refer_code'];
        $refer_table = $data['refer_table'];

        $member = $this->memberRepository->find($member_code);

        $promotion = DB::table('promotions')->where('id', 'pro_spin')->first();
        if ($promotion) {

            $pro_code = $promotion->code;
            $pro_name = $promotion->name_th;
            $turnpro = $promotion->turnpro;
            $withdraw_limit = $promotion->withdraw_limit;
            $withdraw_limit_rate = $promotion->withdraw_limit_rate;
        } else {
            $pro_code = 0;
            $pro_name = '';
            $turnpro = 0;
            $withdraw_limit = 0;
            $withdraw_limit_rate = 0;
        }

        $game = core()->getGame();
        $game_user = $this->gameUserEventRepository->findOneWhere(['method' => 'BONUS', 'member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
        if (! $game_user) {
            $game_user = $this->gameUserEventRepository->create([
                'game_code' => $game->code,
                'member_code' => $member->code,
                'pro_code' => 0,
                'method' => 'BONUS',
                'user_name' => $member->user_name,
                'amount' => 0,
                'bonus' => 0,
                'turnpro' => 0,
                'amount_balance' => 0,
                'withdraw_limit' => 0,
                'withdraw_limit_rate' => 0,
                'withdraw_limit_amount' => 0,
            ]);
        }

        //        DB::beginTransaction();
        try {

            if ($method == 'D') {
                $game_user->bonus += $amount;
                $member->bonus += $amount;
            } elseif ($method == 'W') {
                $game_user->bonus -= $amount;
                $member->bonus -= $amount;
                if ($game_user->bonus < 0) {
                    return false;
                }
            }

            $game_user->amount = $member->balance;

            $game_user->pro_code = $pro_code;
            $game_user->bill_code = 0;
            $game_user->turnpro = $turnpro;
            $game_user->amount_balance += ($amount * $turnpro);
            $game_user->withdraw_limit += $withdraw_limit;
            $game_user->withdraw_limit_rate = $withdraw_limit_rate;
            $game_user->withdraw_limit_amount += ($amount * $withdraw_limit_rate);

            $game_user->save();

            $member->save();

            $this->create([
                'refer_code' => $refer_code,
                'refer_table' => $refer_table,
                'credit_type' => $method,
                'pro_code' => $pro_code,
                'game_code' => $game->code,
                'amount' => 0,
                'bonus' => $amount,
                'total' => $amount,
                'balance_before' => 0,
                'balance_after' => 0,
                'credit' => 0,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => 0,
                'credit_after' => 0,
                'member_code' => $member_code,
                'user_name' => $member->user_name,
                'kind' => $kind,
                'auto' => 'N',
                'remark' => $remark,
                'emp_code' => $emp_code,
                'ip' => $ip,
                'amount_balance' => $game_user->amount_balance,
                'withdraw_limit' => $game_user->withdraw_limit,
                'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                'user_create' => $emp_name,
                'user_update' => $emp_name,
            ]);

            //            DB::commit();

        } catch (Throwable $e) {
            //            DB::rollBack();
            report($e);

            return false;
        }

        return true;
    }

    public function setBonus_(array $data): bool
    {

        $ip = request()->ip();
        $credit_balance = 0;
        $member_code = $data['member_code'];
        $amount = $data['amount'];
        $method = $data['method'];
        $kind = $data['kind'];
        $remark = $data['remark'];
        $emp_code = $data['emp_code'];
        $emp_name = $data['emp_name'];
        $refer_code = $data['refer_code'];
        $refer_table = $data['refer_table'];

        $member = $this->memberRepository->find($member_code);

        if ($method == 'D') {
            $credit_balance = ($member->credit + $amount);
        } elseif ($method == 'W') {
            $credit_balance = ($member->credit - $amount);
            if ($credit_balance < 0) {
                return false;
            }
        }

        //        DB::beginTransaction();
        try {

            $member->credit += $amount;
            $member->save();

            //            DB::commit();

        } catch (Throwable $e) {
            //            DB::rollBack();
            report($e);

            return false;
        }

        return true;
    }

    public function setWallet(array $data): bool
    {

        $ip = request()->ip();
        $credit_balance = 0;
        $member_code = $data['member_code'];
        $amount = $data['amount'];
        $method = $data['method'];
        $kind = $data['kind'];
        $remark = $data['remark'];
        $emp_code = $data['emp_code'];
        $emp_name = $data['emp_name'];
        $refer_code = $data['refer_code'];
        $refer_table = $data['refer_table'];

        try {
            DB::transaction(function () use (
                $member_code,
                $amount,
                $method,
                $kind,
                $remark,
                $emp_code,
                $emp_name,
                $refer_code,
                $refer_table,
                $ip
            ) {
                $member = $this->memberRepository->query()
                    ->where('code', $member_code)
                    ->lockForUpdate()
                    ->first();

                if (! $member) {
                    throw new \RuntimeException('Member not found');
                }

                $walletBefore = (float) $member->balance;
                if ($method == 'D') {
                    $credit_balance = $walletBefore + (float) $amount;
                } elseif ($method == 'W') {
                    $credit_balance = $walletBefore - (float) $amount;
                    if ($credit_balance < 0) {
                        throw new \RuntimeException('Insufficient balance');
                    }
                } else {
                    throw new \RuntimeException('Invalid method');
                }

                $creditLog = $this->create([
                    'refer_code' => $refer_code,
                    'refer_table' => $refer_table,
                    'credit_type' => $method,
                    'amount' => $amount,
                    'bonus' => 0,
                    'total' => $amount,
                    'balance_before' => $walletBefore,
                    'balance_after' => $credit_balance,
                    'credit' => 0,
                    'credit_bonus' => 0,
                    'credit_total' => 0,
                    'credit_before' => 0,
                    'credit_after' => 0,
                    'member_code' => $member_code,
                    'user_name' => $member->user_name,
                    'kind' => $kind,
                    'auto' => 'N',
                    'remark' => $remark,
                    'emp_code' => $emp_code,
                    'ip' => $ip,
                    'user_create' => $emp_name,
                    'user_update' => $emp_name,
                ]);

                $member->balance = $credit_balance;
                $member->save();

                $this->recordWalletTransaction(
                    (int) $member_code,
                    $method == 'D' ? 'CREDIT' : 'DEBIT',
                    (float) $amount,
                    $walletBefore,
                    (float) $credit_balance,
                    $this->resolveWalletRefTypeForAdjustKind($kind),
                    isset($creditLog->code) ? (int) $creditLog->code : null,
                    (string) $refer_table.':'.(string) $refer_code.':'.(string) $kind.':'.(string) $method.':'.(isset($creditLog->code) ? (string) $creditLog->code : '0'),
                    'Member wallet adjust via setWallet',
                    [
                        'source' => 'MemberCreditLogRepository::setWallet',
                        'kind' => $kind,
                        'remark' => $remark,
                    ],
                    ((int) $emp_code > 0) ? 'admin' : 'system',
                    ((int) $emp_code > 0) ? (int) $emp_code : null
                );
            });
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        //        DB::beginTransaction();
        //        try {
        //
        //            $this->create([
        //                'refer_code' => $refer_code,
        //                'refer_table' => $refer_table,
        //                'credit_type' => $method,
        //                'amount' => $amount,
        //                'bonus' => 0,
        //                'total' => $amount,
        //                'balance_before' => $member->balance,
        //                'balance_after' => $credit_balance,
        //                'credit' => 0,
        //                'credit_bonus' => 0,
        //                'credit_total' => 0,
        //                'credit_before' => 0,
        //                'credit_after' => 0,
        //                'member_code' => $member_code,
        //                'kind' => $kind,
        //                'auto' => 'N',
        //                'remark' => $remark,
        //                'emp_code' => $emp_code,
        //                'ip' => $ip,
        //                'user_create' => $emp_name,
        //                'user_update' => $emp_name
        //            ]);
        //
        //            $member->balance = $credit_balance;
        //            $member->save();
        //
        //            DB::commit();
        //
        //        } catch (Throwable $e) {
        //            DB::rollBack();
        //            report($e);
        //            return false;
        //        }

        return true;
    }

    public function setWalletSeamless_(array $data): bool
    {

        $ip = request()->ip();
        $credit_balance = 0;
        $member_code = $data['member_code'];
        $amount = $data['amount'];
        $method = $data['method'];
        $kind = $data['kind'];
        $remark = $data['remark'];
        $emp_code = $data['emp_code'];
        $emp_name = $data['emp_name'];
        $refer_code = $data['refer_code'];
        $refer_table = $data['refer_table'];

        $member = $this->memberRepository->find($member_code);

        //        $game_user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code , 'enable' => 'Y']);

        if ($method == 'D') {
            $credit_balance = ($member->balance + $amount);
        } elseif ($method == 'W') {
            $credit_balance = ($member->balance - $amount);
            if ($credit_balance < 0) {
                return false;
            }
        }

        $this->create([
            'refer_code' => $refer_code,
            'refer_table' => $refer_table,
            'credit_type' => $method,
            'amount' => $amount,
            'bonus' => 0,
            'total' => $amount,
            'balance_before' => $member->balance,
            'balance_after' => $credit_balance,
            'credit' => 0,
            'credit_bonus' => 0,
            'credit_total' => 0,
            'credit_before' => 0,
            'credit_after' => 0,
            'member_code' => $member_code,
            'user_name' => $member->user_name,
            'kind' => $kind,
            'auto' => 'N',
            'remark' => $remark,
            'emp_code' => $emp_code,
            'ip' => $ip,
            'user_create' => $emp_name,
            'user_update' => $emp_name,
        ]);

        if ($method == 'D') {
            $member->credit += $amount;
            $member->balance += $amount;

        } else {
            $member->credit -= $amount;
            $member->balance -= $amount;
        }

        $member->save();

        return true;
    }

    public function setWalletSeamlessWithdraw(array $data): bool
    {
        $ip = request()->ip();

        $member_code = (int) ($data['member_code'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);

        $amount_balance = (float) ($data['amount_balance'] ?? 0);
        $withdraw_limit = (float) ($data['withdraw_limit'] ?? 0);
        $withdraw_limit_amount = (float) ($data['withdraw_limit_amount'] ?? 0);

        $method = (string) ($data['method'] ?? '');
        $kind = (string) ($data['kind'] ?? '');
        $remark = (string) ($data['remark'] ?? '');

        $emp_code = (int) ($data['emp_code'] ?? 0);
        $emp_name = (string) ($data['emp_name'] ?? '');

        $refer_code = (int) ($data['refer_code'] ?? 0);
        $refer_table = (string) ($data['refer_table'] ?? '');

        $pro_name = (string) ($data['pro_name'] ?? '');
        $pro_code = (int) ($data['pro_code'] ?? 0);

        if ($member_code <= 0 || $refer_code <= 0 || $refer_table === '' || $method === '' || $kind === '') {
            return false;
        }

        // ===== กันซ้ำแบบเร็ว (0.5 วิ) =====
        $idemKey = "mcl:singlewithdraw:{$refer_table}:{$refer_code}:{$kind}:{$method}";
        $lock = Cache::lock($idemKey, 20);

        if (! $lock->get()) {
            // มีคนทำอยู่/เพิ่งทำไป
            return true;
        }

        try {
            // ===== Idempotency check: ถ้ามี log แล้ว ถือว่าเคยทำแล้ว =====
            $logExists = DB::table('members_credit_log')
                ->where('refer_table', $refer_table)
                ->where('refer_code', $refer_code)
                ->where('kind', $kind)
                ->where('credit_type', $method)
                ->where('member_code', $member_code)
                ->exists();

            if ($logExists) {
                return true;
            }

            $member = $this->memberRepository->find($member_code);
            if (! $member) {
                return false;
            }

            $game = core()->getGame();
            $game_user = $this->gameUserRepository->findOneWhere([
                'member_code' => $member->code,
                'enable' => 'Y',
            ]);

            if (! $game_user) {
                return false;
            }

            $game_code = $game->code;
            $user_name = $game_user->user_name;
            $user_code = $game_user->code;

            $money_text = 'จำนวนเงิน '.$amount;

            try {
                $response['ref_id'] = $refer_code.'-'.$kind.'-'.$method.'-'.time();
                $response['before'] = $member->balance;
                $response['after'] = $member->balance + $amount;

                // ===== กันซ้ำรอบสอง (กรณีแปลก ๆ) ก่อน insert จริง =====
                $logExists2 = DB::table('members_credit_log')
                    ->where('refer_table', $refer_table)
                    ->where('refer_code', $refer_code)
                    ->where('kind', $kind)
                    ->where('credit_type', $method)
                    ->where('member_code', $member->code)
                    ->exists();

                $creditLogCode = null;
                if (! $logExists2) {
                    $creditLog = $this->create([
                        'refer_code' => $refer_code,
                        'refer_table' => $refer_table,
                        'credit_type' => $method,
                        'pro_code' => $pro_code,
                        'pro_name' => $pro_name,
                        'amount_balance' => $amount_balance,
                        'withdraw_limit' => $withdraw_limit,
                        'withdraw_limit_amount' => $withdraw_limit_amount,
                        'amount' => $amount,
                        'bonus' => 0,
                        'total' => $amount,
                        'balance_before' => $response['before'],
                        'balance_after' => $response['after'],
                        'credit' => 0,
                        'credit_bonus' => 0,
                        'credit_total' => 0,
                        'credit_before' => $response['before'],
                        'credit_after' => $response['after'],
                        'member_code' => $member->code,
                        'user_name' => $member->user_name,
                        'remark' => 'RefID : '.$response['ref_id'].' '.$remark,
                        'kind' => $kind,
                        'auto' => 'N',
                        'emp_code' => $emp_code,
                        'ip' => $ip,
                        'user_create' => $emp_name,
                        'user_update' => $emp_name,
                    ]);
                    $creditLogCode = isset($creditLog->code) ? (int) $creditLog->code : null;
                }

                // ===== Bill: เช็คก่อนสร้าง (กันซ้ำ) =====
                $billExists = DB::table('bills')
                    ->where('refer_table', $refer_table)
                    ->where('refer_code', $refer_code)
                    ->where('method', $kind)
                    ->where('member_code', $member->code)
                    ->exists();

                if (! $billExists) {
                    app('Gametech\Payment\Repositories\BillRepository')->create([
                        'complete' => 'Y',
                        'enable' => 'Y',
                        'refer_code' => $refer_code,
                        'refer_table' => $refer_table,
                        'ref_id' => $response['ref_id'],
                        'credit_before' => $response['before'],
                        'credit_after' => $response['after'],
                        'member_code' => $member->code,
                        'game_code' => $game_code,
                        'gameuser_code' => $user_code,
                        'pro_code' => $pro_code,
                        'pro_name' => $pro_name,
                        'remark' => $remark,
                        'method' => $kind,
                        'transfer_type' => 1,
                        'amount' => $amount,
                        'balance_before' => $response['before'],
                        'balance_after' => $response['after'],
                        'credit' => $amount,
                        'credit_bonus' => 0,
                        'credit_balance' => $amount,
                        'amount_request' => 0,
                        'amount_limit' => 0,
                        'ip' => $ip,
                        'user_create' => $member['name'],
                        'user_update' => $member['name'],
                    ]);
                }

                // ===== Sync balance ตาม after จากเกม =====
                if ($method === 'D') {
                    $member->balance = $response['after'];
                    $game_user->balance = $response['after'];

                    if ($pro_code > 0) {
                        if ((int) $game_user->pro_code === 0) {
                            $game_user->pro_code = $pro_code;
                            $game_user->amount_balance = $amount_balance;
                            $game_user->withdraw_limit = $withdraw_limit;
                            $game_user->withdraw_limit_amount = $withdraw_limit_amount;
                        } else {
                            $game_user->amount_balance += $amount_balance;
                            $game_user->withdraw_limit_amount += $withdraw_limit_amount;
                        }
                    }
                }

                $member->save();
                $game_user->save();

                if ($creditLogCode !== null) {
                    $this->recordWalletTransaction(
                        (int) $member->code,
                        $method == 'D' ? 'CREDIT' : 'DEBIT',
                        (float) $amount,
                        $response['before'],
                        (float) $response['after'],
                        $this->resolveWalletRefTypeForAdjustKind($kind),
                        $creditLogCode,
                        (string) $refer_table.':'.(string) $refer_code.':'.(string) $kind.':'.(string) $method.':'.(string) $creditLogCode,
                        'Member wallet adjust via setWalletSeamlessWithdraw',
                        [
                            'source' => 'MemberCreditLogRepository::setWalletSeamlessWithdraw',
                            'kind' => $kind,
                            'remark' => $remark,
                        ],
                        ((int) $emp_code > 0) ? 'admin' : 'system',
                        ((int) $emp_code > 0) ? (int) $emp_code : null
                    );
                }

            } catch (Throwable $e) {
                report($e);

                return false;
            }

            Notification::send($member, new RealTimeNotification(Lang::get('app.home.adjust_balance')));

            return true;
        } finally {
            optional($lock)->release();
        }
    }

    public function setWalletSeamlessWithdraw_bk(array $data): bool
    {

        $ip = request()->ip();
        $credit_balance = 0;
        $member_code = $data['member_code'];
        $amount = $data['amount'];
        $amount_balance = $data['amount_balance'];
        $withdraw_limit = $data['withdraw_limit'];
        $withdraw_limit_amount = $data['withdraw_limit_amount'];
        $method = $data['method'];
        $kind = $data['kind'];
        $remark = $data['remark'];
        $emp_code = $data['emp_code'];
        $emp_name = $data['emp_name'];
        $refer_code = $data['refer_code'];
        $refer_table = $data['refer_table'];
        $pro_name = $data['pro_name'];
        $pro_code = $data['pro_code'];

        try {
            DB::transaction(function () use (
                $member_code,
                $amount,
                $amount_balance,
                $withdraw_limit,
                $withdraw_limit_amount,
                $method,
                $kind,
                $remark,
                $emp_code,
                $emp_name,
                $refer_code,
                $refer_table,
                $pro_name,
                $pro_code,
                $ip
            ) {
                $member = $this->memberRepository->query()
                    ->where('code', $member_code)
                    ->lockForUpdate()
                    ->first();
                if (! $member) {
                    throw new \RuntimeException('Member not found');
                }

                $walletBefore = (float) $member->balance;

                $game = core()->getGame();
                $game_user = $this->gameUserRepository->query()
                    ->where('member_code', $member->code)
                    ->where('enable', 'Y')
                    ->lockForUpdate()
                    ->first();
                if (! $game_user) {
                    throw new \RuntimeException('Game user not found');
                }

                $credit_balance = ($method == 'D')
                    ? ($walletBefore + (float) $amount)
                    : ($walletBefore - (float) $amount);

                if ($method == 'W' && $credit_balance < 0) {
                    throw new \RuntimeException('Insufficient balance');
                }

                $creditLog = $this->create([
                    'refer_code' => $refer_code,
                    'refer_table' => $refer_table,
                    'credit_type' => $method,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'amount_balance' => $amount_balance,
                    'withdraw_limit' => $withdraw_limit,
                    'withdraw_limit_amount' => $withdraw_limit_amount,
                    'amount' => $amount,
                    'bonus' => 0,
                    'total' => $amount,
                    'balance_before' => $walletBefore,
                    'balance_after' => $credit_balance,
                    'credit' => 0,
                    'credit_bonus' => 0,
                    'credit_total' => 0,
                    'credit_before' => 0,
                    'credit_after' => 0,
                    'member_code' => $member->code,
                    'user_name' => $member->user_name,
                    'kind' => $kind,
                    'auto' => 'N',
                    'remark' => $remark,
                    'emp_code' => $emp_code,
                    'ip' => $ip,
                    'user_create' => $emp_name,
                    'user_update' => $emp_name,
                ]);

                app('Gametech\Payment\Repositories\BillRepository')->create([
                    'complete' => 'Y',
                    'enable' => 'Y',
                    'refer_code' => $refer_code,
                    'refer_table' => $refer_table,
                    'ref_id' => '',
                    'credit_before' => $walletBefore,
                    'credit_after' => $credit_balance,
                    'member_code' => $member->code,
                    'game_code' => $game->code,
                    'gameuser_code' => $game_user->code,
                    'pro_code' => $pro_code,
                    'pro_name' => $pro_name,
                    'remark' => $remark,
                    'method' => $kind,
                    'transfer_type' => 1,
                    'amount' => $amount,
                    'balance_before' => $walletBefore,
                    'balance_after' => $credit_balance,
                    'credit' => $amount,
                    'credit_bonus' => 0,
                    'credit_balance' => $amount,
                    'amount_request' => 0,
                    'amount_limit' => 0,
                    'ip' => $ip,
                    'user_create' => $member['name'],
                    'user_update' => $member['name'],
                ]);

                $member->balance = $credit_balance;
                if ($method == 'D') {
                    $game_user->pro_code = $pro_code;
                    $game_user->amount_balance += $amount_balance;
                    $game_user->withdraw_limit = $withdraw_limit;
                    $game_user->withdraw_limit_amount += $withdraw_limit_amount;
                }

                $member->save();
                $game_user->save();

                $this->recordWalletTransaction(
                    (int) $member->code,
                    $method == 'D' ? 'CREDIT' : 'DEBIT',
                    (float) $amount,
                    $walletBefore,
                    (float) $credit_balance,
                    $this->resolveWalletRefTypeForAdjustKind($kind),
                    isset($creditLog->code) ? (int) $creditLog->code : null,
                    (string) $refer_table.':'.(string) $refer_code.':'.(string) $kind.':'.(string) $method.':'.(isset($creditLog->code) ? (string) $creditLog->code : '0'),
                    'Member wallet adjust via setWalletSeamlessWithdraw',
                    [
                        'source' => 'MemberCreditLogRepository::setWalletSeamlessWithdraw',
                        'kind' => $kind,
                        'remark' => $remark,
                    ],
                    ((int) $emp_code > 0) ? 'admin' : 'system',
                    ((int) $emp_code > 0) ? (int) $emp_code : null
                );
            });
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        $notifyMember = $this->memberRepository->find($member_code);
        if ($notifyMember) {
            Notification::send($notifyMember, new RealTimeNotification(Lang::get('app.home.adjust_balance')));
        }

        return true;
    }

    public function setWalletSingleWithdraw_(array $data): bool
    {

        $ip = request()->ip();
        $credit_balance = 0;
        $member_code = $data['member_code'];
        $amount = $data['amount'];
        $amount_balance = $data['amount_balance'];
        $withdraw_limit = $data['withdraw_limit'];
        $withdraw_limit_amount = $data['withdraw_limit_amount'];
        $method = $data['method'];
        $kind = $data['kind'];
        $remark = $data['remark'];
        $emp_code = $data['emp_code'];
        $emp_name = $data['emp_name'];
        $refer_code = $data['refer_code'];
        $refer_table = $data['refer_table'];
        $pro_name = $data['pro_name'];
        $pro_code = $data['pro_code'];
        $isDp = $data['isDp'] ?? false;

        $member = $this->memberRepository->find($member_code);

        $game = core()->getGame();
        $game_user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'enable' => 'Y']);
        $game_code = $game->code;
        $user_name = $game_user->user_name;
        $user_code = $game_user->code;
        $game_name = $game->name;
        $game_balance = $game_user->balance;
        $member_code = $member->code;

        if ($method == 'D') {
            $credit_balance = ($member->balance + $amount);
        } elseif ($method == 'W') {
            $credit_balance = ($member->balance - $amount);
            if ($credit_balance < 0) {
                return false;
            }
        }

        $money_text = 'จำนวนเงิน '.$amount;
        //        DB::beginTransaction();
        try {

            $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $amount, false);
            if ($response['success'] === true) {
                ActivityLoggerUser::activity('ฝากเงินเข้าเกม '.$game_name, $money_text.' ระบบทำการฝากเงินเข้าเกมแล้ว', $member_code);

            } else {
                ActivityLoggerUser::activity('ฝากเงินเข้าเกม '.$game_name, $money_text.' ไม่สามารถฝากเงินเข้าเกมได้', $member_code);

                return false;
            }

            $this->create([
                'refer_code' => $refer_code,
                'refer_table' => $refer_table,
                'credit_type' => $method,
                'pro_code' => $pro_code,
                'pro_name' => $pro_name,
                'amount_balance' => $amount_balance,
                'withdraw_limit' => $withdraw_limit,
                'withdraw_limit_amount' => $withdraw_limit_amount,
                'amount' => $amount,
                'bonus' => 0,
                'total' => $amount,
                'balance_before' => $response['before'],
                'balance_after' => $response['after'],
                'credit' => 0,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => $response['before'],
                'credit_after' => $response['after'],
                'member_code' => $member_code,
                'user_name' => $member->user_name,
                'remark' => 'RefID : '.$response['ref_id'].' '.$remark,
                'kind' => $kind,
                'auto' => 'N',
                'emp_code' => $emp_code,
                'ip' => $ip,
                'user_create' => $emp_name,
                'user_update' => $emp_name,
            ]);

            app('Gametech\Payment\Repositories\BillRepository')->create([
                'complete' => 'Y',
                'enable' => 'Y',
                'refer_code' => $refer_code,
                'refer_table' => $refer_table,
                'ref_id' => $response['ref_id'],
                'credit_before' => $response['before'],
                'credit_after' => $response['after'],
                'member_code' => $member_code,
                'game_code' => $game_code,
                'gameuser_code' => $user_code,
                'pro_code' => $pro_code,
                'pro_name' => $pro_name,
                'remark' => $remark,
                'method' => $kind,
                'transfer_type' => 1,
                'amount' => $amount,
                'balance_before' => $response['before'],
                'balance_after' => $response['after'],
                'credit' => $amount,
                'credit_bonus' => 0,
                'credit_balance' => $amount,
                'amount_request' => 0,
                'amount_limit' => 0,
                'ip' => $ip,
                'user_create' => $member['name'],
                'user_update' => $member['name'],
            ]);

            if ($method == 'D') {
                $member->balance = $response['after'];
                $game_user->balance = $response['after'];
                if ($pro_code > 0) {
                    if ($game_user->pro_code == 0) {
                        $game_user->pro_code = $pro_code;
                        $game_user->amount_balance = $amount_balance;
                        $game_user->withdraw_limit = $withdraw_limit;
                        $game_user->withdraw_limit_amount = $withdraw_limit_amount;
                    } else {
                        $game_user->amount_balance += $amount_balance;
                        $game_user->withdraw_limit_amount += $withdraw_limit_amount;
                    }

                }

            } else {
                //                $member->balance -= $amount;
                //                $game_user->balance -= $amount;
            }

            $member->save();
            $game_user->save();

            //            DB::commit();

        } catch (Throwable $e) {
            //            DB::rollBack();
            report($e);

            return false;
        }

        $notifyMember = $this->memberRepository->find($member_code);
        if ($notifyMember) {
            Notification::send($notifyMember, new RealTimeNotification(Lang::get('app.home.adjust_balance')));
        }

        return true;
    }

    public function setWalletSingleWithdraw(array $data): bool
    {
        $ip = request()->ip();

        $member_code = (int) ($data['member_code'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);

        $amount_balance = (float) ($data['amount_balance'] ?? 0);
        $withdraw_limit = (float) ($data['withdraw_limit'] ?? 0);
        $withdraw_limit_amount = (float) ($data['withdraw_limit_amount'] ?? 0);

        $method = (string) ($data['method'] ?? '');
        $kind = (string) ($data['kind'] ?? '');
        $remark = (string) ($data['remark'] ?? '');

        $emp_code = (int) ($data['emp_code'] ?? 0);
        $emp_name = (string) ($data['emp_name'] ?? '');

        $refer_code = (int) ($data['refer_code'] ?? 0);
        $refer_table = (string) ($data['refer_table'] ?? '');

        $pro_name = (string) ($data['pro_name'] ?? '');
        $pro_code = (int) ($data['pro_code'] ?? 0);

        if ($member_code <= 0 || $refer_code <= 0 || $refer_table === '' || $method === '' || $kind === '') {
            return false;
        }

        // ===== กันซ้ำแบบเร็ว (0.5 วิ) =====
        $idemKey = "mcl:singlewithdraw:{$refer_table}:{$refer_code}:{$kind}:{$method}";
        $lock = Cache::lock($idemKey, 20);

        if (! $lock->get()) {
            // มีคนทำอยู่/เพิ่งทำไป
            return true;
        }

        try {
            // ===== Idempotency check: ถ้ามี log แล้ว ถือว่าเคยทำแล้ว =====
            $logExists = DB::table('members_credit_log')
                ->where('refer_table', $refer_table)
                ->where('refer_code', $refer_code)
                ->where('kind', $kind)
                ->where('credit_type', $method)
                ->where('member_code', $member_code)
                ->exists();

            if ($logExists) {
                return true;
            }

            $member = $this->memberRepository->find($member_code);
            if (! $member) {
                return false;
            }

            $game = core()->getGame();
            $game_user = $this->gameUserRepository->findOneWhere([
                'member_code' => $member->code,
                'enable' => 'Y',
            ]);

            if (! $game_user) {
                return false;
            }

            $game_code = $game->code;
            $user_name = $game_user->user_name;
            $user_code = $game_user->code;

            $money_text = 'จำนวนเงิน '.$amount;

            try {
                // ===== Side-effect (ฝากเข้าเกม) =====
                $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $amount, false);

                if (($response['success'] ?? false) === true) {
                    ActivityLoggerUser::activity(
                        'ฝากเงินเข้าเกม '.$game->name,
                        $money_text.' ระบบทำการฝากเงินเข้าเกมแล้ว',
                        $member->code
                    );
                } else {
                    ActivityLoggerUser::activity(
                        'ฝากเงินเข้าเกม '.$game->name,
                        $money_text.' ไม่สามารถฝากเงินเข้าเกมได้',
                        $member->code
                    );

                    return false;
                }

                // ===== กันซ้ำรอบสอง (กรณีแปลก ๆ) ก่อน insert จริง =====
                $logExists2 = DB::table('members_credit_log')
                    ->where('refer_table', $refer_table)
                    ->where('refer_code', $refer_code)
                    ->where('kind', $kind)
                    ->where('credit_type', $method)
                    ->where('member_code', $member->code)
                    ->exists();

                if (! $logExists2) {
                    $this->create([
                        'refer_code' => $refer_code,
                        'refer_table' => $refer_table,
                        'credit_type' => $method,
                        'pro_code' => $pro_code,
                        'pro_name' => $pro_name,
                        'amount_balance' => $amount_balance,
                        'withdraw_limit' => $withdraw_limit,
                        'withdraw_limit_amount' => $withdraw_limit_amount,
                        'amount' => $amount,
                        'bonus' => 0,
                        'total' => $amount,
                        'balance_before' => $response['before'],
                        'balance_after' => $response['after'],
                        'credit' => 0,
                        'credit_bonus' => 0,
                        'credit_total' => 0,
                        'credit_before' => $response['before'],
                        'credit_after' => $response['after'],
                        'member_code' => $member->code,
                        'user_name' => $member->user_name,
                        'remark' => 'RefID : '.$response['ref_id'].' '.$remark,
                        'kind' => $kind,
                        'auto' => 'N',
                        'emp_code' => $emp_code,
                        'ip' => $ip,
                        'user_create' => $emp_name,
                        'user_update' => $emp_name,
                    ]);
                }

                // ===== Bill: เช็คก่อนสร้าง (กันซ้ำ) =====
                $billExists = DB::table('bills')
                    ->where('refer_table', $refer_table)
                    ->where('refer_code', $refer_code)
                    ->where('method', $kind)
                    ->where('member_code', $member->code)
                    ->exists();

                if (! $billExists) {
                    app('Gametech\Payment\Repositories\BillRepository')->create([
                        'complete' => 'Y',
                        'enable' => 'Y',
                        'refer_code' => $refer_code,
                        'refer_table' => $refer_table,
                        'ref_id' => $response['ref_id'],
                        'credit_before' => $response['before'],
                        'credit_after' => $response['after'],
                        'member_code' => $member->code,
                        'game_code' => $game_code,
                        'gameuser_code' => $user_code,
                        'pro_code' => $pro_code,
                        'pro_name' => $pro_name,
                        'remark' => $remark,
                        'method' => $kind,
                        'transfer_type' => 1,
                        'amount' => $amount,
                        'balance_before' => $response['before'],
                        'balance_after' => $response['after'],
                        'credit' => $amount,
                        'credit_bonus' => 0,
                        'credit_balance' => $amount,
                        'amount_request' => 0,
                        'amount_limit' => 0,
                        'ip' => $ip,
                        'user_create' => $member['name'],
                        'user_update' => $member['name'],
                    ]);
                }

                // ===== Sync balance ตาม after จากเกม =====
                if ($method === 'D') {
                    $member->balance = $response['after'];
                    $game_user->balance = $response['after'];

                    if ($pro_code > 0) {
                        if ((int) $game_user->pro_code === 0) {
                            $game_user->pro_code = $pro_code;
                            $game_user->amount_balance = $amount_balance;
                            $game_user->withdraw_limit = $withdraw_limit;
                            $game_user->withdraw_limit_amount = $withdraw_limit_amount;
                        } else {
                            $game_user->amount_balance += $amount_balance;
                            $game_user->withdraw_limit_amount += $withdraw_limit_amount;
                        }
                    }
                }

                $member->save();
                $game_user->save();
            } catch (Throwable $e) {
                report($e);

                return false;
            }

            Notification::send($member, new RealTimeNotification(Lang::get('app.home.adjust_balance')));

            return true;
        } finally {
            optional($lock)->release();
        }
    }

    public function setWalletSingle(array $data): bool
    {

        $ip = request()->ip();
        $credit_balance = 0;
        $member_code = $data['member_code'];
        $amount = $data['amount'];
        $method = $data['method'];
        $kind = $data['kind'];
        $remark = $data['remark'];
        $emp_code = $data['emp_code'];
        $emp_name = $data['emp_name'];
        $refer_code = $data['refer_code'];
        $refer_table = $data['refer_table'];
        $isDp = $data['isDp'] ?? false;

        $member = $this->memberRepository->find($member_code);

        $game = core()->getGame();
        $game_user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
        $game_code = $game->code;
        $user_name = $game_user->user_name;
        $user_code = $game_user->code;
        $game_name = $game->name;
        $game_balance = $game_user->balance;
        $member_code = $member->code;

        if ($method == 'D') {
            $credit_balance = ($member->balance + $amount);
        } elseif ($method == 'W') {
            $credit_balance = ($member->balance - $amount);
            if ($credit_balance < 0) {
                return false;
            }
        }

        $money_text = 'จำนวนเงิน '.$amount;

        if ($method == 'D') {

            $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $amount, false);
            if ($response['success'] === true) {
                ActivityLoggerUser::activity('ฝากเงินเข้าเกม '.$game_name, $money_text.' ระบบทำการฝากเงินเข้าเกมแล้ว', $member_code);

            } else {
                ActivityLoggerUser::activity('ฝากเงินเข้าเกม '.$game_name, $money_text.' ไม่สามารถฝากเงินเข้าเกมได้', $member_code);

                return false;
            }

            //            DB::beginTransaction();
            try {

                $creditLog = $this->create([
                    'refer_code' => $refer_code,
                    'refer_table' => $refer_table,
                    'credit_type' => $method,
                    'amount' => $amount,
                    'bonus' => 0,
                    'total' => $amount,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => 0,
                    'credit_bonus' => 0,
                    'credit_total' => 0,
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member_code,
                    'gameuser_code' => $user_code,
                    'game_code' => $game_code,
                    'user_name' => $member->user_name,
                    'kind' => $kind,
                    'auto' => 'N',
                    'remark' => 'RefID : '.$response['ref_id'].' '.$remark,
                    'emp_code' => $emp_code,
                    'ip' => $ip,
                    'user_create' => $emp_name,
                    'user_update' => $emp_name,
                ]);

                $member->balance = $response['after'];
                $member->save();

                $game_user->balance = $response['after'];
                $game_user->save();

                app('Gametech\Payment\Repositories\BillRepository')->create([
                    'complete' => 'Y',
                    'enable' => 'Y',
                    'refer_code' => $refer_code,
                    'refer_table' => $refer_table,
                    'ref_id' => isset($creditLog->code) ? (string) $creditLog->code : $response['ref_id'],
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member_code,
                    'game_code' => $game_code,
                    'gameuser_code' => $user_code,
                    'pro_code' => 0,
                    'pro_name' => '',
                    'remark' => $remark,
                    'method' => $kind,
                    'transfer_type' => 1,
                    'amount' => $amount,
                    'balance_before' => $response['before'],
                    'balance_after' => $response['after'],
                    'credit' => $amount,
                    'credit_bonus' => 0,
                    'credit_balance' => $amount,
                    'amount_request' => 0,
                    'amount_limit' => 0,
                    'ip' => $ip,
                    'user_create' => $member['name'],
                    'user_update' => $member['name'],
                ]);

                $this->recordWalletTransaction(
                    (int) $member_code,
                    'CREDIT',
                    (float) $amount,
                    (float) $response['before'],
                    (float) $response['after'],
                    $this->resolveWalletRefTypeForAdjustKind($kind),
                    null,
                    (string) $refer_table.':'.(string) $refer_code.':'.(string) $kind.':'.(string) $method.':'.(string) $response['ref_id'],
                    'Member wallet adjust via setWalletSingle',
                    [
                        'source' => 'MemberCreditLogRepository::setWalletSingle',
                        'kind' => $kind,
                        'remark' => $remark,
                    ],
                    ((int) $emp_code > 0) ? 'admin' : 'system',
                    ((int) $emp_code > 0) ? (int) $emp_code : null
                );

                //                DB::commit();

            } catch (Throwable $e) {
                //                DB::rollBack();
                $response = $this->gameUserRepository->UserWithdraw($game_code, $user_name, $amount);
                if ($response['success'] === true) {
                    ActivityLoggerUser::activity('ถอนเงินออกเกม '.$game_name, $money_text.' ระบบทำการถอนเงินออกจากเกมแล้ว');

                } else {
                    ActivityLoggerUser::activity('ถอนเงินออกเกม '.$game_name, $money_text.' ระบบไม่สามารถถอนเงินออกจากเกมได้');
                }
                report($e);

                return false;
            }

        } else {

            $response = $this->gameUserRepository->UserWithdraw($game_code, $user_name, $amount, false);
            if ($response['success'] === true) {
                ActivityLoggerUser::activity('ถอนเงินออกเกม '.$game_name, $money_text.' ระบบทำการถอนเงินออกจากเกมแล้ว');

            } else {
                ActivityLoggerUser::activity('ถอนเงินออกเกม '.$game_name, $money_text.' ไม่สามารถถอนเงินออกจากเกมได้');

                return false;
            }

            //            DB::beginTransaction();
            try {

                $creditLog = $this->create([
                    'refer_code' => $refer_code,
                    'refer_table' => $refer_table,
                    'credit_type' => $method,
                    'amount' => $amount,
                    'bonus' => 0,
                    'total' => $amount,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => 0,
                    'credit_bonus' => 0,
                    'credit_total' => 0,
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member_code,
                    'gameuser_code' => $user_code,
                    'game_code' => $game_code,
                    'user_name' => $member->user_name,
                    'kind' => $kind,
                    'auto' => 'N',
                    'remark' => 'RefID : '.$response['ref_id'].' '.$remark,
                    'emp_code' => $emp_code,
                    'ip' => $ip,
                    'user_create' => $emp_name,
                    'user_update' => $emp_name,
                ]);

                $member->balance = $response['after'];
                $member->save();

                $game_user->balance = $response['after'];
                $game_user->save();

                app('Gametech\Payment\Repositories\BillRepository')->create([
                    'complete' => 'Y',
                    'enable' => 'Y',
                    'refer_code' => $refer_code,
                    'refer_table' => $refer_table,
                    'ref_id' => isset($creditLog->code) ? (string) $creditLog->code : $response['ref_id'],
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member_code,
                    'game_code' => $game_code,
                    'gameuser_code' => $user_code,
                    'pro_code' => 0,
                    'pro_name' => '',
                    'remark' => $remark,
                    'method' => $kind,
                    'transfer_type' => 2,
                    'amount' => $amount,
                    'balance_before' => $response['before'],
                    'balance_after' => $response['after'],
                    'credit' => $amount,
                    'credit_bonus' => 0,
                    'credit_balance' => $amount,
                    'amount_request' => 0,
                    'amount_limit' => 0,
                    'ip' => $ip,
                    'user_create' => $member['name'],
                    'user_update' => $member['name'],
                ]);

                $this->recordWalletTransaction(
                    (int) $member_code,
                    'DEBIT',
                    (float) $amount,
                    (float) $response['before'],
                    (float) $response['after'],
                    $this->resolveWalletRefTypeForAdjustKind($kind),
                    null,
                    (string) $refer_table.':'.(string) $refer_code.':'.(string) $kind.':'.(string) $method.':'.(string) $response['ref_id'],
                    'Member wallet adjust via setWalletSingle',
                    [
                        'source' => 'MemberCreditLogRepository::setWalletSingle',
                        'kind' => $kind,
                        'remark' => $remark,
                    ],
                    ((int) $emp_code > 0) ? 'admin' : 'system',
                    ((int) $emp_code > 0) ? (int) $emp_code : null
                );

                //                DB::commit();

            } catch (Throwable $e) {
                //                DB::rollBack();
                $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $amount, true);
                if ($response['success'] === true) {
                    ActivityLoggerUser::activity('ฝากเงินเข้าเกม '.$game_name, $money_text.' ระบบทำการฝากเงินคืนเข้าเกมแล้ว');

                } else {
                    ActivityLoggerUser::activity('ฝากเงินเข้าเกม '.$game_name, $money_text.' ระบบไม่สามารถฝากเงินคืนเข้าเกมได้');
                }
                report($e);

                return false;
            }

        }

        $notifyMember = $this->memberRepository->find($member_code);
        if ($notifyMember) {
            Notification::send($notifyMember, new RealTimeNotification(Lang::get('app.home.adjust_balance')));
        }

        return true;
    }

    public function setWalletSeamless(array $data): bool
    {

        $ip = request()->ip();
        $credit_balance = 0;
        $member_code = $data['member_code'];
        $amount = $data['amount'];
        $method = $data['method'];
        $kind = $data['kind'];
        $remark = $data['remark'];
        $emp_code = $data['emp_code'];
        $emp_name = $data['emp_name'];
        $refer_code = $data['refer_code'];
        $refer_table = $data['refer_table'];

        try {
            DB::transaction(function () use (
                $member_code,
                $amount,
                $method,
                $kind,
                $remark,
                $emp_code,
                $emp_name,
                $refer_code,
                $refer_table,
                $ip
            ) {
                $member = $this->memberRepository->query()
                    ->where('code', $member_code)
                    ->lockForUpdate()
                    ->first();
                if (! $member) {
                    throw new \RuntimeException('Member not found');
                }

                $game = core()->getGame();
                $game_user = $this->gameUserRepository->query()
                    ->where('member_code', $member->code)
                    ->where('game_code', $game->code)
                    ->where('enable', 'Y')
                    ->lockForUpdate()
                    ->first();
                if (! $game_user) {
                    throw new \RuntimeException('Game user not found');
                }

                $response['before'] = (float) $member->balance;
                if ($method == 'D') {
                    $response['after'] = $response['before'] + (float) $amount;
                    $direction = 'CREDIT';
                    $transferType = 1;
                } elseif ($method == 'W') {
                    $response['after'] = $response['before'] - (float) $amount;
                    if ($response['after'] < 0) {
                        throw new \RuntimeException('Insufficient balance');
                    }
                    $direction = 'DEBIT';
                    $transferType = 2;
                } else {
                    throw new \RuntimeException('Invalid method');
                }
                $response['ref_id'] = '';

                $creditLog = $this->create([
                    'refer_code' => $refer_code,
                    'refer_table' => $refer_table,
                    'credit_type' => $method,
                    'amount' => $amount,
                    'bonus' => 0,
                    'total' => $amount,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => 0,
                    'credit_bonus' => 0,
                    'credit_total' => 0,
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member->code,
                    'gameuser_code' => $game_user->code,
                    'game_code' => $game->code,
                    'user_name' => $member->user_name,
                    'kind' => $kind,
                    'auto' => 'N',
                    'remark' => 'RefID : '.$response['ref_id'].' '.$remark,
                    'emp_code' => $emp_code,
                    'ip' => $ip,
                    'user_create' => $emp_name,
                    'user_update' => $emp_name,
                ]);

                $member->balance = $response['after'];
                $member->save();

                $game_user->balance = $response['after'];
                $game_user->save();

                app('Gametech\Payment\Repositories\BillRepository')->create([
                    'complete' => 'Y',
                    'enable' => 'Y',
                    'refer_code' => $refer_code,
                    'refer_table' => $refer_table,
                    'ref_id' => isset($creditLog->code) ? (string) $creditLog->code : $response['ref_id'],
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member->code,
                    'game_code' => $game->code,
                    'gameuser_code' => $game_user->code,
                    'pro_code' => 0,
                    'pro_name' => '',
                    'remark' => $remark,
                    'method' => $kind,
                    'transfer_type' => $transferType,
                    'amount' => $amount,
                    'balance_before' => $response['before'],
                    'balance_after' => $response['after'],
                    'credit' => $amount,
                    'credit_bonus' => 0,
                    'credit_balance' => $amount,
                    'amount_request' => 0,
                    'amount_limit' => 0,
                    'ip' => $ip,
                    'user_create' => $member['name'],
                    'user_update' => $member['name'],
                ]);

                $this->recordWalletTransaction(
                    (int) $member->code,
                    $direction,
                    (float) $amount,
                    (float) $response['before'],
                    (float) $response['after'],
                    $this->resolveWalletRefTypeForAdjustKind($kind),
                    isset($creditLog->code) ? (int) $creditLog->code : null,
                    (string) $refer_table.':'.(string) $refer_code.':'.(string) $kind.':'.(string) $method.':'.(isset($creditLog->code) ? (string) $creditLog->code : '0'),
                    'Member wallet adjust via setWalletSeamless',
                    [
                        'source' => 'MemberCreditLogRepository::setWalletSeamless',
                        'kind' => $kind,
                        'remark' => $remark,
                    ],
                    ((int) $emp_code > 0) ? 'admin' : 'system',
                    ((int) $emp_code > 0) ? (int) $emp_code : null
                );
            });
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        $notifyMember = $this->memberRepository->find($member_code);
        if ($notifyMember) {
            Notification::send($notifyMember, new RealTimeNotification(Lang::get('app.home.adjust_balance')));
        }

        return true;
    }

    public function setWalletSeamless__(array $data): bool
    {

        $ip = request()->ip();
        $credit_balance = 0;
        $member_code = $data['member_code'];
        $amount = $data['amount'];
        $method = $data['method'];
        $kind = $data['kind'];
        $remark = $data['remark'];
        $emp_code = $data['emp_code'];
        $emp_name = $data['emp_name'];
        $refer_code = $data['refer_code'];
        $refer_table = $data['refer_table'];
        //        $isDp = $data['isDp'];

        $member = $this->memberRepository->find($member_code);

        $game = core()->getGame();
        $game_user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
        $game_code = $game->code;
        $user_name = $game_user->user_name;
        $user_code = $game_user->code;
        $game_name = $game->name;
        $game_balance = $game_user->balance;
        $member_code = $member->code;

        if ($method == 'D') {
            $credit_balance = ($member->balance + $amount);
        } elseif ($method == 'W') {
            $credit_balance = ($member->balance - $amount);
            if ($credit_balance < 0) {
                return false;
            }
        }

        $money_text = 'จำนวนเงิน '.$amount;

        if ($method == 'D') {

            //            DB::beginTransaction();
            try {

                $this->create([
                    'refer_code' => $refer_code,
                    'refer_table' => $refer_table,
                    'credit_type' => $method,
                    'amount' => $amount,
                    'bonus' => 0,
                    'total' => $amount,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => 0,
                    'credit_bonus' => 0,
                    'credit_total' => 0,
                    'credit_before' => $member->balance,
                    'credit_after' => ($member->balance + $amount),
                    'member_code' => $member_code,
                    'gameuser_code' => $user_code,
                    'game_code' => $game_code,
                    'user_name' => $member->user_name,
                    'kind' => $kind,
                    'auto' => 'N',
                    'remark' => $remark,
                    'emp_code' => $emp_code,
                    'ip' => $ip,
                    'user_create' => $emp_name,
                    'user_update' => $emp_name,
                ]);

                app('Gametech\Payment\Repositories\BillRepository')->create([
                    'complete' => 'Y',
                    'enable' => 'Y',
                    'refer_code' => $refer_code,
                    'refer_table' => $refer_table,
                    'ref_id' => '',
                    'credit_before' => $member->balance,
                    'credit_after' => ($member->balance + $amount),
                    'member_code' => $member_code,
                    'game_code' => $game_code,
                    'gameuser_code' => $user_code,
                    'pro_code' => 0,
                    'pro_name' => '',
                    'remark' => $remark,
                    'method' => $kind,
                    'transfer_type' => 1,
                    'amount' => $amount,
                    'balance_before' => $member->balance,
                    'balance_after' => ($member->balance + $amount),
                    'credit' => $amount,
                    'credit_bonus' => 0,
                    'credit_balance' => $amount,
                    'amount_request' => 0,
                    'amount_limit' => 0,
                    'ip' => $ip,
                    'user_create' => $member['name'],
                    'user_update' => $member['name'],
                ]);

                $member->balance += $amount;
                $member->save();

                $game_user->balance = $member->balance;
                $game_user->save();

                //                DB::commit();

            } catch (Throwable $e) {
                //                DB::rollBack();

                report($e);

                return false;
            }

        } else {

            //            DB::beginTransaction();
            try {

                $this->create([
                    'refer_code' => $refer_code,
                    'refer_table' => $refer_table,
                    'credit_type' => $method,
                    'amount' => $amount,
                    'bonus' => 0,
                    'total' => $amount,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => 0,
                    'credit_bonus' => 0,
                    'credit_total' => 0,
                    'credit_before' => $member->balance,
                    'credit_after' => ($member->balance - $amount),
                    'member_code' => $member_code,
                    'gameuser_code' => $user_code,
                    'game_code' => $game_code,
                    'user_name' => $member->user_name,
                    'kind' => $kind,
                    'auto' => 'N',
                    'remark' => $remark,
                    'emp_code' => $emp_code,
                    'ip' => $ip,
                    'user_create' => $emp_name,
                    'user_update' => $emp_name,
                ]);

                app('Gametech\Payment\Repositories\BillRepository')->create([
                    'complete' => 'Y',
                    'enable' => 'Y',
                    'refer_code' => $refer_code,
                    'refer_table' => $refer_table,
                    'ref_id' => '',
                    'credit_before' => $member->balance,
                    'credit_after' => ($member->balance - $amount),
                    'member_code' => $member_code,
                    'game_code' => $game_code,
                    'gameuser_code' => $user_code,
                    'pro_code' => 0,
                    'pro_name' => '',
                    'remark' => $remark,
                    'method' => $kind,
                    'transfer_type' => 2,
                    'amount' => $amount,
                    'balance_before' => $member->balance,
                    'balance_after' => ($member->balance - $amount),
                    'credit' => $amount,
                    'credit_bonus' => 0,
                    'credit_balance' => $amount,
                    'amount_request' => 0,
                    'amount_limit' => 0,
                    'ip' => $ip,
                    'user_create' => $member['name'],
                    'user_update' => $member['name'],
                ]);

                $member->balance -= $amount;
                $member->save();

                $game_user->balance = $member->balance;
                $game_user->save();

                //                DB::commit();

            } catch (Throwable $e) {
                report($e);

                return false;
            }

        }

        Notification::send($member, new RealTimeNotification(Lang::get('app.home.adjust_balance')));

        return true;
    }

    public function tranBonus_(array $data, $id): bool
    {

        $ip = request()->ip();
        $credit_balance = 0;
        $member_code = $data['member_code'];

        $member = $this->memberRepository->find($member_code);
        if (! $member) {
            return false;
        }
        if ($member->credit <= 0) {
            return false;
        }

        $game_user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'enable' => 'Y']);
        if (! $game_user) {
            return false;
        }

        //        DB::beginTransaction();
        try {

            $this->create([
                'refer_code' => 0,
                'refer_table' => '',
                'credit_type' => 'D',
                'amount' => $member->credit,
                'bonus' => 0,
                'total' => $member->credit,
                'balance_before' => $member->balance,
                'balance_after' => ($member->balance + $member->credit),
                'credit' => 0,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => 0,
                'credit_after' => 0,
                'member_code' => $member_code,
                'user_name' => $member->user_name,
                'kind' => 'TRANBONUS',
                'auto' => 'N',
                'remark' => 'โยกโบนัสเข้า กระเป๋าหลัก',
                'emp_code' => 0,
                'ip' => $ip,
                'user_create' => $member->name,
                'user_update' => $member->name,
            ]);

            $member->balance += $member->credit;
            //            $game_user->balance += $member->credit;
            $member->credit -= $member->credit;

            $member->save();
            //            $game_user->save();

            //            DB::commit();

        } catch (Throwable $e) {
            //            DB::rollBack();
            report($e);

            return false;
        }

        return true;
    }

    public function tranBonus(array $data, $id): bool
    {

        $config = $this->getCoreConfig();
        $ip = request()->ip();
        $amount = 0;
        $member_code = $data['member_code'];
        $allowedMethods = ['BONUS', 'FASTSTART', 'CASHBACK', 'IC'];
        $id = strtoupper((string) $id);

        $member = $this->memberRepository->find($member_code);
        //        dd($member);
        if (! $member) {
            return false;
        }

        if (! in_array($id, $allowedMethods, true)) {
            return false;
        }

        $game = core()->getGame();

        $game_event = $this->gameUserEventRepository->findOneWhere(['method' => $id, 'member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
        if (! $game_event) {
            return false;
        }

        $game_user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
        if (! $game_user) {
            return false;
        }

        $promotion = $this->promotionRepository->findOneWhere(['code' => $game_event->pro_code]);

        if ($config->seamless == 'Y') {
            if ($member->balance > $config->pro_reset) {
                return false;
            }
        } else {
            if ($game_user->balance > $config->pro_reset) {
                return false;
            }
        }
        $min = 0;
        if ($promotion && $promotion->amount_min > 0) {
            $min = $promotion->amount_min;
        }

        if ($id == 'BONUS') {
            if ($member->bonus <= 0) {
                return false;
            }
            if ($member->bonus < $min) {
                return false;
            }
            $pro_name = 'วงล้อมหาสนุก';
            $amount = $member->bonus;
            $kind = 'TRANBONUS';
            $msg = 'รับโบนัสวงล้อ เข้ากระเป๋า (โยกเข้าเกม)';
            $member->bonus = 0;

        } elseif ($id == 'FASTSTART') {
            if ($member->faststart <= 0) {
                return false;
            }
            if ($member->faststart < $min) {
                return false;
            }
            $pro_name = 'ค่าแนะนำ';
            $amount = $member->faststart;
            $kind = 'TRANFT';
            $msg = 'รับค่าแนะนำ เข้ากระเป๋า (โยกเข้าเกม)';
            $member->faststart = 0;

        } elseif ($id == 'CASHBACK') {
            if ($member->cashback <= 0) {
                return false;
            }
            if ($member->cashback < $min) {
                return false;
            }
            $pro_name = 'Cashback';
            $amount = $member->cashback;
            $kind = 'TRANCB';
            $msg = 'รับ Cashback เข้ากระเป๋า (โยกเข้าเกม)';
            $member->cashback = 0;

        } elseif ($id == 'IC') {
            if ($member->ic <= 0) {
                return false;
            }

            if ($member->ic < $min) {
                return false;
            }
            $pro_name = 'ยอดเสียเพื่อน';
            $amount = $member->ic;
            $kind = 'TRANIC';
            $msg = 'รับ IC เข้ากระเป๋า (โยกเข้าเกม)';
            $member->ic = 0;

        }
        // apply source-balance mutation inside transaction with row lock

        $game_code = $game->code;
        $user_name = $game_user->user_name;

        $money_text = 'จำนวนเงิน '.$amount;

        if ($config->seamless == 'N') {
            if ($config->multigame_open == 'N') {
                if ($config->freecredit_open == 'Y') {
                    $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $amount, false);
                    //                    dd($response);
                    if ($response['success'] === true) {
                        ActivityLoggerUser::activity('ฝากเงิน '.$id.' เข้าเกม'.$game->name, $money_text.' ระบบทำการฝากเงินเข้าเกมแล้ว');

                    } else {
                        ActivityLoggerUser::activity('ฝากเงิน '.$id.' เข้าเกม'.$game->name, $money_text.' ไม่สามารถฝากเงินเข้าเกมได้');

                        return false;
                    }
                } else {
                    $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $amount, false);
                    //                    dd($response);
                    if ($response['success'] === true) {
                        ActivityLoggerUser::activity('ฝากเงิน '.$id.' เข้าเกม'.$game->name, $money_text.' ระบบทำการฝากเงินเข้าเกมแล้ว');

                    } else {
                        ActivityLoggerUser::activity('ฝากเงิน '.$id.' เข้าเกม'.$game->name, $money_text.' ไม่สามารถฝากเงินเข้าเกมได้');
                        if ($id == 'BONUS') {
                            $member->bonus = $amount;

                        } elseif ($id == 'FASTSTART') {
                            $member->faststart = $amount;

                        } elseif ($id == 'CASHBACK') {

                            $member->cashback = $amount;

                        } elseif ($id == 'IC') {

                            $member->ic = $amount;

                        }
                        $member->save();

                        return false;
                    }

                }
            }
        } else {
            $response['before'] = $member->balance;
            $response['after'] = ($member->balance + $amount);
            $response['ref_id'] = '';
        }

        $total = ($response['before'] + $amount);

        if ($promotion) {
            $turnpro = $promotion->turnpro;
            $withdraw_limit_rate = $promotion->withdraw_limit_rate;
            if ($turnpro > 0) {
                $amount_total = $response['before'] + ($amount * $promotion->turnpro);
            } else {
                $amount_total = 0;
            }
            //            $amount_total = $response['before'] + ($amount * $promotion->turnpro);
            if ($withdraw_limit_rate > 0) {
                $withdraw_limit_amount = $response['before'] + ($amount * $promotion->withdraw_limit_rate);
            } else {
                $withdraw_limit_amount = 0;
            }
        } else {
            //            $amount_total = $response['before'] + ($amount * $game_event->turnpro);
            //            $withdraw_limit_amount = $response['before'] + ($amount * $game_event->withdraw_limit_rate);
            $turnpro = $game_event->turnpro;
            $withdraw_limit_rate = $game_event->withdraw_limit_rate;
            if ($turnpro > 0) {
                $amount_total = $response['before'] + ($amount * $game_event->turnpro);
            } else {
                $amount_total = 0;
            }

            if ($withdraw_limit_rate > 0) {
                $withdraw_limit_amount = $response['before'] + ($amount * $game_event->withdraw_limit_rate);
            } else {
                $withdraw_limit_amount = 0;
            }
        }

        try {
            DB::transaction(function () use (
                $member_code,
                $id,
                $amount,
                &$response,
                $config,
                $game_code,
                $pro_name,
                $kind,
                $msg,
                $ip,
                $amount_total,
                $withdraw_limit_amount,
                $withdraw_limit_rate,
                $turnpro
            ) {
                $member = $this->memberRepository->query()
                    ->where('code', $member_code)
                    ->lockForUpdate()
                    ->first();
                $game_user = $this->gameUserRepository->query()
                    ->where('member_code', $member_code)
                    ->where('game_code', $game_code)
                    ->where('enable', 'Y')
                    ->lockForUpdate()
                    ->first();
                $game_event = $this->gameUserEventRepository->query()
                    ->where('method', $id)
                    ->where('member_code', $member_code)
                    ->where('game_code', $game_code)
                    ->where('enable', 'Y')
                    ->lockForUpdate()
                    ->first();

                if (! $member || ! $game_user || ! $game_event) {
                    throw new \RuntimeException('Missing member/game records');
                }

                if ($config->seamless == 'Y') {
                    $response['before'] = (float) $member->balance;
                    $response['after'] = (float) $member->balance + (float) $amount;
                    $response['ref_id'] = '';
                }

                if ($id == 'BONUS' || $id == 'SPIN') {
                    if ((float) $member->bonus < (float) $amount) {
                        throw new \RuntimeException('Insufficient bonus');
                    }
                    $member->bonus = (float) $member->bonus - (float) $amount;
                } elseif ($id == 'FASTSTART') {
                    if ((float) $member->faststart < (float) $amount) {
                        throw new \RuntimeException('Insufficient faststart');
                    }
                    $member->faststart = (float) $member->faststart - (float) $amount;
                } elseif ($id == 'CASHBACK') {
                    if ((float) $member->cashback < (float) $amount) {
                        throw new \RuntimeException('Insufficient cashback');
                    }
                    $member->cashback = (float) $member->cashback - (float) $amount;
                } elseif ($id == 'IC') {
                    if ((float) $member->ic < (float) $amount) {
                        throw new \RuntimeException('Insufficient ic');
                    }
                    $member->ic = (float) $member->ic - (float) $amount;
                }

                $bill = $this->create([
                    'refer_code' => 0,
                    'refer_table' => '',
                    'credit_type' => 'D',
                    'pro_code' => $game_event->pro_code,
                    'pro_name' => $pro_name,
                    'amount' => 0,
                    'bonus' => $amount,
                    'total' => $amount,
                    'balance_before' => $response['before'],
                    'balance_after' => $response['after'],
                    'credit' => 0,
                    'credit_bonus' => 0,
                    'credit_total' => 0,
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member_code,
                    'gameuser_code' => $game_user->code,
                    'game_code' => $game_code,
                    'user_name' => $member->user_name,
                    'kind' => $kind,
                    'auto' => 'N',
                    'remark' => 'RefID : '.$response['ref_id'].' '.$msg,
                    'emp_code' => 0,
                    'ip' => $ip,
                    'amount_balance' => $amount_total,
                    'withdraw_limit' => $game_event->withdraw_limit,
                    'withdraw_limit_amount' => $withdraw_limit_amount,
                    'user_create' => $member->name,
                    'user_update' => $member->name,
                ]);

                app('Gametech\Payment\Repositories\BillRepository')->create([
                    'complete' => 'Y',
                    'enable' => 'Y',
                    'refer_code' => $bill->code,
                    'refer_table' => 'members_credit_log',
                    'ref_id' => $response['ref_id'],
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member_code,
                    'game_code' => $game_code,
                    'gameuser_code' => $game_user->code,
                    'pro_code' => $game_event->pro_code,
                    'pro_name' => $pro_name,
                    'remark' => $msg,
                    'method' => 'BONUS',
                    'transfer_type' => 1,
                    'amount' => $amount,
                    'balance_before' => $response['before'],
                    'balance_after' => $response['after'],
                    'credit' => 0,
                    'credit_bonus' => $amount,
                    'credit_balance' => $amount,
                    'amount_request' => $amount_total,
                    'amount_limit' => $withdraw_limit_amount,
                    'ip' => $ip,
                    'user_create' => $member['name'],
                    'user_update' => $member['name'],
                ]);

                $member->balance = $response['after'];
                $member->save();

                $game_user->balance = $response['after'];
                $game_user->pro_code = $game_event->pro_code;
                $game_user->bill_code = $game_event->bill_code;
                $game_user->amount = $game_event->amount;
                $game_user->bonus = $game_event->bonus;
                $game_user->turnpro = $turnpro;
                $game_user->amount_balance = $amount_total;
                $game_user->withdraw_limit = $game_event->withdraw_limit;
                $game_user->withdraw_limit_rate = $withdraw_limit_rate;
                $game_user->withdraw_limit_amount = $withdraw_limit_amount;
                $game_user->save();

                $game_event->bonus = 0;
                $game_event->turnpro = 0;
                $game_event->amount_balance = 0;
                $game_event->withdraw_limit = 0;
                $game_event->withdraw_limit_rate = 0;
                $game_event->withdraw_limit_amount = 0;
                $game_event->save();

                $this->recordWalletTransaction(
                    (int) $member_code,
                    'CREDIT',
                    (float) $amount,
                    (float) $response['before'],
                    (float) $response['after'],
                    $this->resolveWalletRefTypeForTranKind($kind),
                    isset($bill->code) ? (int) $bill->code : null,
                    'tranBonus:'.(string) $id.':'.(isset($bill->code) ? (string) $bill->code : '0'),
                    'Transfer bonus to wallet/game via tranBonus',
                    [
                        'source' => 'MemberCreditLogRepository::tranBonus',
                        'event' => $id,
                        'kind' => $kind,
                    ],
                    'system',
                    null
                );
            });
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        return true;
    }

    public function setEvent(array $data): bool
    {

        $ip = request()->ip();

        $member_code = $data['member_code'];
        $amount = $data['amount'];
        $event = $data['event'];
        $remark = $data['remark'];
        $emp_code = $data['emp_code'];
        $emp_name = $data['emp_name'];
        $refer_code = $data['refer_code'];
        $refer_table = $data['refer_table'];

        $member = $this->memberRepository->find($member_code);
        $promotion = DB::table('promotions')->where('code', $event)->first();
        if (! $promotion) {
            return false;

        } else {
            $event_id = $promotion->id;
            if ($event_id === 'pro_cashback') {
                $method = 'CASHBACK';
            } elseif ($event_id === 'pro_spin') {
                $method = 'BONUS';
            } elseif ($event_id === 'pro_transfer') {
                $method = 'FASTSTART';
            } elseif ($event_id === 'pro_ic') {
                $method = 'IC';
            } else {
                return false;
            }
        }

        $game = core()->getGame();
        $game_user = $this->gameUserEventRepository->findOneWhere(['method' => $method, 'member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
        if (! $game_user) {
            $game_user = $this->gameUserEventRepository->create([
                'game_code' => $game->code,
                'member_code' => $member->code,
                'pro_code' => 0,
                'method' => $method,
                'user_name' => $member->user_name,
                'amount' => 0,
                'bonus' => 0,
                'turnpro' => 0,
                'amount_balance' => 0,
                'withdraw_limit' => 0,
                'withdraw_limit_rate' => 0,
                'withdraw_limit_amount' => 0,
            ]);
        }

        $money_text = $remark ? $remark.' ( คืนยอดจากกิจกรรมต่างๆ จำนวนเงิน '.$amount.' กรณีคำนวนผิด ตกหล่น มีปัญหาให้กับลูกค้า )' : '( คืนยอดจากกิจกรรมต่างๆ จำนวนเงิน '.$amount.' กรณีคำนวนผิด ตกหล่น มีปัญหาให้กับลูกค้า )';

        try {

            if ($method === 'CASHBACK') {

                $member->cashback += $amount;
            } elseif ($method === 'BONUS') {

                $member->bonus += $amount;

            } elseif ($method === 'FASTSTART') {

                $member->faststart += $amount;

            } elseif ($method === 'IC') {

                $member->ic += $amount;

            }

            $member->save();

            $this->create([
                'refer_code' => $refer_code,
                'refer_table' => $refer_table,
                'credit_type' => $method,
                'pro_code' => $event,
                'game_code' => $game->code,
                'amount' => 0,
                'bonus' => $amount,
                'total' => $amount,
                'balance_before' => 0,
                'balance_after' => 0,
                'credit' => 0,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => 0,
                'credit_after' => 0,
                'member_code' => $member_code,
                'user_name' => $member->user_name,
                'kind' => $method,
                'auto' => 'N',
                'remark' => $money_text,
                'emp_code' => $emp_code,
                'ip' => $ip,
                'amount_balance' => 0,
                'withdraw_limit' => 0,
                'withdraw_limit_amount' => 0,
                'user_create' => $emp_name,
                'user_update' => $emp_name,
            ]);

            //            DB::commit();

        } catch (Throwable $e) {
            //            DB::rollBack();
            report($e);

            return false;
        }

        return true;
    }

    private function getCoreConfig()
    {
        if (app()->bound('request')) {
            $request = app('request');
            $cacheKey = '_member_credit_log_repo.core_config';

            if ($request->attributes->has($cacheKey)) {
                return $request->attributes->get($cacheKey);
            }

            $config = core()->getConfigData();
            $request->attributes->set($cacheKey, $config);

            return $config;
        }

        return core()->getConfigData();
    }

    private function recordWalletTransaction(
        int $memberId,
        string $direction,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        string $refType,
        ?int $refId,
        string $refCode,
        string $description,
        array $meta = [],
        string $createdByType = 'system',
        ?int $createdById = null
    ): void {
        if ($amount <= 0 || ! Schema::hasTable('wallet_transactions')) {
            return;
        }

        $query = DB::table('wallet_transactions')
            ->where('member_id', $memberId)
            ->where('direction', $direction)
            ->where('ref_type', $refType);

        if ($refId !== null) {
            $query->where('ref_id', $refId);
        } else {
            $query->where('ref_code', $refCode);
        }

        if ($query->exists()) {
            return;
        }

        DB::table('wallet_transactions')->insert([
            'member_id' => $memberId,
            'scope' => 'MEMBER',
            'game_user_id' => null,
            'direction' => $direction,
            'amount' => number_format($amount, 2, '.', ''),
            'balance_before' => number_format($balanceBefore, 2, '.', ''),
            'balance_after' => number_format($balanceAfter, 2, '.', ''),
            'ref_type' => $refType,
            'ref_id' => $refId,
            'ref_code' => $refCode,
            'group_code' => $refType.'_'.($refId !== null ? (string) $refId : $refCode),
            'related_txn_id' => null,
            'status' => 'SUCCESS',
            'description' => $description,
            'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
            'created_by_type' => $createdByType,
            'created_by_id' => $createdById,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function resolveWalletRefTypeForAdjustKind(string $kind): string
    {
        $k = strtoupper(trim($kind));
        if ($k === 'ROLLBACK') {
            return 'ROLLBACK';
        }
        if ($k === 'SETWALLET') {
            return 'SETWALLET';
        }

        return 'ADJUST';
    }

    private function resolveWalletRefTypeForTranKind(string $kind): string
    {
        $k = strtoupper(trim($kind));
        $allow = ['TRANBONUS', 'TRANIC', 'TRANCB', 'TRANFT'];
        if (in_array($k, $allow, true)) {
            return $k;
        }

        return 'TRAN_BONUS';
    }
}
