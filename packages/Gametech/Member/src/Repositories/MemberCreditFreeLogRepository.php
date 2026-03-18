<?php

namespace Gametech\Member\Repositories;

use Gametech\Core\Eloquent\Repository;
use Gametech\Game\Repositories\GameUserEventRepository;
use Gametech\Game\Repositories\GameUserFreeRepository;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Throwable;

class MemberCreditFreeLogRepository extends Repository
{
    use ActivityLoggerUser;

    private $memberRepository;

    private $gameUserFreeRepository;

    private $gameUserEventRepository;

    public function __construct
    (
        MemberRepository        $memberRepo,
        GameUserFreeRepository  $gameUserFreeRepo,
        GameUserEventRepository $gameUserEventRepo,
        App                     $app
    )
    {
        $this->memberRepository = $memberRepo;

        $this->gameUserFreeRepository = $gameUserFreeRepo;

        $this->gameUserEventRepository = $gameUserEventRepo;

        parent::__construct($app);
    }

    /**
     * Specify Model class name
     *
     * @return string
     */
    function model(): string
    {
        return \Gametech\Member\Models\MemberCreditFreeLog::class;

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
            $turnpro = $promotion->turnpro;
            $withdraw_limit = $promotion->withdraw_limit;
            $withdraw_limit_rate = $promotion->withdraw_limit_rate;
        } else {
            $pro_code = 0;
            $turnpro = 0;
            $withdraw_limit = 0;
            $withdraw_limit_rate = 0;
        }

        $game = core()->getGame();
        $game_user = $this->gameUserEventRepository->findOneWhere(['method' => 'BONUS', 'member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
        if (!$game_user) {
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

            $game_user->amount = $member->balance_free;

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
                'amount' => $amount,
                'bonus' => 0,
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
                'user_update' => $emp_name
            ]);


//            DB::commit();

        } catch (Throwable $e) {
//            DB::rollBack();
            report($e);
            return false;
        }

        return true;
    }

    public function setWallet_(array $data): bool
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

            $credit_balance = ($member->balance_free + $amount);
            $member->balance_free += $amount;

        } elseif ($method == 'W') {
            $credit_balance = ($member->balance_free - $amount);
            $member->balance_free -= $amount;

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
            'balance_before' => $member->balance_free,
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
            'user_update' => $emp_name
        ]);

//        $member->balance = $credit_balance;
        $member->save();

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

        $member = $this->memberRepository->find($member_code);

        if ($method == 'D') {

            $credit_balance = ($member->balance_free + $amount);
//            $member->balance_free += $amount;

        } elseif ($method == 'W') {
            $credit_balance = ($member->balance_free - $amount);
//            $member->balance_free -= $amount;

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
            'balance_before' => $member->balance_free,
            'balance_after' => $credit_balance,
            'credit' => 0,
            'credit_bonus' => 0,
            'credit_total' => 0,
            'credit_before' => $member->balance_free,
            'credit_after' => $credit_balance,
            'member_code' => $member_code,
            'user_name' => $member->user_name,
            'kind' => $kind,
            'auto' => 'N',
            'remark' => $remark,
            'emp_code' => $emp_code,
            'ip' => $ip,
            'user_create' => $emp_name,
            'user_update' => $emp_name
        ]);

        if ($method == 'D') {
            $member->balance_free += $amount;

        } else {
            $member->balance_free -= $amount;
        }
//        $member->balance = $credit_balance;
        $member->save();

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

        $member = $this->memberRepository->find($member_code);

//        $game_user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code , 'enable' => 'Y']);


        if ($method == 'D') {
            $credit_balance = ($member->balance_free + $amount);
        } elseif ($method == 'W') {
            $credit_balance = ($member->balance_free - $amount);
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
            'balance_before' => $member->balance_free,
            'balance_after' => $credit_balance,
            'credit' => 0,
            'credit_bonus' => 0,
            'credit_total' => 0,
            'credit_before' => $member->balance_free,
            'credit_after' => $credit_balance,
            'member_code' => $member_code,
            'user_name' => $member->user_name,
            'kind' => $kind,
            'auto' => 'N',
            'remark' => $remark,
            'emp_code' => $emp_code,
            'ip' => $ip,
            'user_create' => $emp_name,
            'user_update' => $emp_name
        ]);

        if ($method == 'D') {
            $member->balance_free += $amount;

        } else {
            $member->balance_free -= $amount;
        }

        $member->save();

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
//            if($method == 'D'){
//                $member->balance += $amount;
////                $game_user->balance += $amount;
//            }else{
//                $member->balance -= $amount;
////                $game_user->balance -= $amount;
//            }
//
//            $member->save();
////            $game_user->save();
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

    public function setWalletSeamlessWithdraw(array $data): bool
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

        $member = $this->memberRepository->find($member_code);

        $game_user = $this->gameUserFreeRepository->findOneWhere(['member_code' => $member->code, 'enable' => 'Y']);


        if ($method == 'D') {
            $credit_balance = ($member->balance_free + $amount);
        } elseif ($method == 'W') {
            $credit_balance = ($member->balance_free - $amount);
            if ($credit_balance < 0) {
                return false;
            }
        }

        DB::beginTransaction();
        try {

            $this->create([
                'refer_code' => $refer_code,
                'refer_table' => $refer_table,
                'credit_type' => $method,
                'amount' => $amount,
                'bonus' => 0,
                'total' => $amount,
                'balance_before' => $member->balance_free,
                'balance_after' => $credit_balance,
                'credit' => 0,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => $member->balance_free,
                'credit_after' => $credit_balance,
                'member_code' => $member_code,
                'user_name' => $member->user_name,
                'kind' => $kind,
                'auto' => 'N',
                'remark' => $remark,
                'emp_code' => $emp_code,
                'ip' => $ip,
                'user_create' => $emp_name,
                'user_update' => $emp_name
            ]);

            if ($method == 'D') {
                $member->balance_free += $amount;
//                $game_user->balance += $amount;
                $game_user->amount_balance += $amount_balance;
                $game_user->withdraw_limit = $withdraw_limit;
                $game_user->withdraw_limit_amount += $withdraw_limit_amount;
            } else {
                $member->balance_free -= $amount;
//                $game_user->balance -= $amount;
            }

            $member->save();
            $game_user->save();

            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            return false;
        }

        return true;
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

        $member = $this->memberRepository->find($member_code);

        $game = core()->getGame();
        $game_user = $this->gameUserFreeRepository->findOneWhere(['member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
        $game_code = $game->code;
        $user_name = $game_user->user_name;
        $user_code = $game_user->code;
        $game_name = $game->name;
        $game_balance = $game_user->balance;
        $member_code = $member->code;

        if ($method == 'D') {
            $credit_balance = ($member->balance_free + $amount);
        } elseif ($method == 'W') {
            $credit_balance = ($member->balance_free - $amount);
            if ($credit_balance < 0) {
                return false;
            }
        }

//        dd($credit_balance);

        $money_text = 'จำนวนเงิน ' . $amount;

        if ($method == 'D') {

            $response = $this->gameUserFreeRepository->UserDeposit($game_code, $user_name, $amount, false);
//            dd($response);
            if ($response['success'] === true) {
                ActivityLoggerUser::activity('ฝากเงินเข้าเกม ' . $game_name, $money_text . ' ระบบทำการฝากเงินเข้าเกมแล้ว');

            } else {
                ActivityLoggerUser::activity('ฝากเงินเข้าเกม ' . $game_name, $money_text . ' ไม่สามารถฝากเงินเข้าเกมได้');
                return false;
            }


            DB::beginTransaction();
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
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member_code,
                    'user_name' => $member->user_name,
                    'kind' => $kind,
                    'auto' => 'N',
                    'remark' => $remark,
                    'emp_code' => $emp_code,
                    'ip' => $ip,
                    'user_create' => $emp_name,
                    'user_update' => $emp_name
                ]);

                $member->balance_free = $response['after'];
                $member->save();

                $game_user->balance = $response['after'];
                $game_user->save();

                DB::commit();

            } catch (Throwable $e) {
                DB::rollBack();
                $response = $this->gameUserFreeRepository->UserWithdraw($game_code, $user_name, $amount);
                if ($response['success'] === true) {
                    ActivityLoggerUser::activity('ฝากเงินเข้าเกม ' . $game_name, $money_text . ' ระบบทำการถอนเงินออกจากเกมแล้ว');


                } else {
                    ActivityLoggerUser::activity('ฝากเงินเข้าเกม ' . $game_name, $money_text . ' ระบบไม่สามารถถอนเงินออกจากเกมได้ จึงไม่ได้หักยอดเงินออก');
                }
                report($e);
                return false;
            }

        } else {

            $response = $this->gameUserFreeRepository->UserWithdraw($game_code, $user_name, $amount, false);
            if ($response['success'] === true) {
                ActivityLoggerUser::activity('ถอนเงินออกเกม ' . $game_name, $money_text . ' ระบบทำการฝากเงินเข้าเกมแล้ว');

            } else {
                ActivityLoggerUser::activity('ถอนเงินออกเกม ' . $game_name, $money_text . ' ไม่สามารถฝากเงินเข้าเกมได้');
                return false;
            }


            DB::beginTransaction();
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
                    'credit_before' => $response['before'],
                    'credit_after' => $response['after'],
                    'member_code' => $member_code,
                    'user_name' => $member->user_name,
                    'kind' => $kind,
                    'auto' => 'N',
                    'remark' => $remark,
                    'emp_code' => $emp_code,
                    'ip' => $ip,
                    'user_create' => $emp_name,
                    'user_update' => $emp_name
                ]);

                $member->balance_free = $response['after'];
                $member->save();

                $game_user->balance = $response['after'];
                $game_user->save();

                DB::commit();

            } catch (Throwable $e) {
                DB::rollBack();
                $response = $this->gameUserFreeRepository->UserDeposit($game_code, $user_name, $amount);
                if ($response['success'] === true) {
                    ActivityLoggerUser::activity('ถอนเงินออกเกม ' . $game_name, $money_text . ' ระบบทำการฝากเงินคืนเข้าเกมแล้ว');

                } else {
                    ActivityLoggerUser::activity('ถอนเงินออกเกม ' . $game_name, $money_text . ' ระบบไม่สามารถฝากเงินคืนเข้าเกมได้ จึงไม่ได้หักยอดเงินออก');
                }
                report($e);
                return false;
            }

        }


        return true;
    }

    public function tranBonus(array $data, $id): bool
    {

        $config = core()->getConfigData();
        $ip = request()->ip();
        $credit_balance = 0;
        $member_code = $data['member_code'];


        $member = $this->memberRepository->find($member_code);
//        dd($member);
        if (!$member) {
            return false;
        }

        if ($member->balance_free > $config->pro_reset) {
            return false;
        }


        if ($id == 'BONUS') {
            if ($member->bonus <= 0) {
                return false;
            }
            $amount = $member->bonus;
            $kind = 'TRANBONUS';
            $msg = 'โยกโบนัส เข้ากระเป๋าฟรี';
            $member->bonus = 0;
        } elseif ($id == 'FASTSTART') {
            if ($member->faststart <= 0) {
                return false;
            }
            $amount = $member->faststart;
            $kind = 'TRANFT';
            $msg = 'โยกค่าแนะนำ เข้ากระเป๋าฟรี';
            $member->faststart = 0;
        } elseif ($id == 'CASHBACK') {
            if ($member->cashback <= 0) {
                return false;
            }
            $amount = $member->cashback;
            $kind = 'TRANCB';
            $msg = 'โยก Cashback เข้ากระเป๋าฟรี';
            $member->cashback = 0;
        } elseif ($id == 'IC') {
            if ($member->ic <= 0) {
                return false;
            }
            $amount = $member->ic;
            $kind = 'TRANIC';
            $msg = 'โยก IC เข้ากระเป๋าฟรี';
            $member->ic = 0;
        }

        $game = core()->getGame();

        $game_event = $this->gameUserEventRepository->findOneWhere(['method' => $id, 'member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
        if (!$game_event) {
            return false;
        }

        $game_user = $this->gameUserFreeRepository->findOneWhere(['member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
        if (!$game_user) {
            return false;
        }
//        dd($game_event);

        $game_code = $game->code;
        $user_name = $game_user->user_name;

        $total = ($member->balance_free + $amount);
        $amount_total = ($total * $game_event->turnpro);
        $withdraw_limit_amount = ($total * $game_event->withdraw_limit_rate);

        $money_text = 'จำนวนเงิน ' . $amount;

        if ($config->seamless == 'N') {
            if ($config->multigame_open == 'N') {
                if ($config->freecredit_open == 'Y') {
                    $response = $this->gameUserFreeRepository->UserDeposit($game_code, $user_name, $amount, false);
//                    dd($response);
                    if ($response['success'] === true) {
                        ActivityLoggerUser::activity('ฝากเงินฟรีเครดิตเข้าเกม ' . $game->name, $money_text . ' ระบบทำการฝากเงินเข้าเกมแล้ว');

                    } else {
                        ActivityLoggerUser::activity('ฝากเงินฟรีเครดิตเข้าเกม ' . $game->name, $money_text . ' ไม่สามารถฝากเงินเข้าเกมได้');

                        return false;
                    }
                }
            }
        }


//        dd('here');
//        DB::beginTransaction();
        try {

            $this->create([
                'refer_code' => 0,
                'refer_table' => '',
                'credit_type' => 'D',
                'amount' => $amount,
                'bonus' => 0,
                'total' => $amount,
                'balance_before' => $member->balance_free,
                'balance_after' => ($member->balance_free + $amount),
                'credit' => 0,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => $member->balance_free,
                'credit_after' => ($member->balance_free + $amount),
                'member_code' => $member_code,
                'user_name' => $member->user_name,
                'kind' => $kind,
                'auto' => 'N',
                'remark' => $msg,
                'emp_code' => 0,
                'ip' => $ip,
                'amount_balance' => $amount_total,
                'withdraw_limit' => $game_event->withdraw_limit,
                'withdraw_limit_amount' => $withdraw_limit_amount,
                'user_create' => $member->name,
                'user_update' => $member->name
            ]);

            $member->balance_free += $amount;
//            $game_user->balance += $member->credit;
//            $member->credit -= $member->credit;

            $member->save();


            $game_user->turnpro = $game_event->turnpro;
            $game_user->amount_balance = $amount_total;
            $game_user->withdraw_limit = $game_event->withdraw_limit;
            $game_user->withdraw_limit_rate = $game_event->withdraw_limit_rate;
            $game_user->withdraw_limit_amount = $withdraw_limit_amount;
            $game_user->save();

            $game_event->bonus = 0;
            $game_event->turnpro = 0;
            $game_event->amount_balance = 0;
            $game_event->withdraw_limit = 0;
            $game_event->withdraw_limit_rate = 0;
            $game_event->withdraw_limit_amount = 0;
            $game_event->save();

//            DB::commit();

        } catch (Throwable $e) {
//            DB::rollBack();
            report($e);
            return false;
        }

        return true;
    }
}
