<?php

namespace Gametech\Member\Repositories;

use Gametech\Core\Eloquent\Repository;
use Gametech\Game\Repositories\GameUserEventRepository;
use Gametech\LogAdmin\Http\Traits\ActivityLogger;
use Gametech\Member\Models\MemberIc;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Throwable;

class MemberIcRepository extends Repository
{
    use ActivityLogger;

    private $memberRepository;

    private $memberFreeCreditRepository;

    private $gameUserEventRepository;

    private $memberCreditFreeLogRepository;

    private $memberCreditLogRepository;

    public function __construct(
        MemberRepository $memberRepo,
        MemberFreeCreditRepository $memberFreeCreditRepo,
        GameUserEventRepository $gameUserEventRepo,
        MemberCreditFreeLogRepository $memberCreditFreeLogRepo,
        MemberCreditLogRepository $memberCreditLogRepo,
        App $app
    ) {
        $this->memberRepository = $memberRepo;
        $this->memberFreeCreditRepository = $memberFreeCreditRepo;
        $this->gameUserEventRepository = $gameUserEventRepo;
        $this->memberCreditFreeLogRepository = $memberCreditFreeLogRepo;
        $this->memberCreditLogRepository = $memberCreditLogRepo;
        parent::__construct($app);
    }

    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return MemberIc::class;

    }

    private function getCoreConfig()
    {
        if (app()->bound('request')) {
            $request = app('request');
            $cacheKey = '_member_ic_repo.core_config';

            if ($request->attributes->has($cacheKey)) {
                return $request->attributes->get($cacheKey);
            }

            $config = core()->getConfigData();
            $request->attributes->set($cacheKey, $config);

            return $config;
        }

        return core()->getConfigData();
    }

    public function refill(array $data): bool
    {
        $config = $this->getCoreConfig();
        $code = ($data['code'] ?? 0);
        $member_code = $data['upline_code'];
        $downline_code = $data['member_code'];
        $amount = $data['balance'];
        $cashback = $data['ic'];
        $date_cashback = $data['date_cashback'];
        $sum_deposit = $data['sum_deposit'] ?? 0;
        $sum_withdraw = $data['sum_withdraw'] ?? 0;
        $sum_balance = $data['sum_balance'] ?? 0;
        $ip = $data['ip'];
        $emp_code = $data['emp_code'];
        $emp_name = $data['emp_name'];

        $chk = $this->find($code);
        if ($chk) {
            if ($chk->topupic == 'Y' || $chk->topupic == 'X') {
                return false;
            }
        }

        $promotion = DB::table('promotions')->where('id', 'pro_ic')->first();
        $pro_code = $promotion->code;
        $pro_name = $promotion->id;
        $turnpro = $promotion->turnpro;
        $withdraw_limit = $promotion->withdraw_limit;
        $withdraw_limit_rate = $promotion->withdraw_limit_rate;

        $member = $this->memberRepository->find($member_code);
        if (! $member) {
            return false;
        }

        ActivityLogger::activitie('IC REFER USER : '.$member->user_name, 'เริ่มรายการ IC');

        DB::beginTransaction();
        try {

            if ($chk) {
                $chk->topupic = 'Y';
                $chk->save();
                $code = $chk->code;

            } else {
                $bill = $this->create([
                    'member_code' => $member_code,
                    'downline_code' => $downline_code,
                    'date_cashback' => $date_cashback,
                    'balance' => $amount,
                    'ic' => $cashback,
                    'amount' => $cashback,
                    'topupic' => 'Y',
                    'ip_admin' => $ip,
                    'emp_code' => $emp_code,
                    'date_approve' => now()->toDateTimeString(),
                    'user_create' => $emp_name,
                    'user_update' => $emp_name,
                    'sum_balance' => $sum_balance,
                    'sum_deposit' => $sum_deposit,
                    'sum_withdraw' => $sum_withdraw,
                ]);
                $code = $bill->code;
            }

            if ($config->seamless == 'Y') {

                $game = core()->getGame();
                $game_user = $this->gameUserEventRepository->findOneWhere(['method' => 'IC', 'member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
                if (! $game_user) {
                    $game_user = $this->gameUserEventRepository->create([
                        'game_code' => $game->code,
                        'member_code' => $member->code,
                        'pro_code' => $pro_code,
                        'method' => 'IC',
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

                $game_user->amount = $member->balance_free;
                $game_user->pro_code = $pro_code;
                $game_user->bill_code = $code;
                $game_user->turnpro += $turnpro;
                $game_user->bonus += $cashback;
                $game_user->amount_balance += ($cashback * $turnpro);
                $game_user->withdraw_limit += $withdraw_limit;
                $game_user->withdraw_limit_rate += $withdraw_limit_rate;
                $game_user->withdraw_limit_amount += ($cashback * $withdraw_limit_rate);
                $game_user->save();

                $this->memberCreditFreeLogRepository->create([
                    'ip' => $ip,
                    'credit_type' => 'D',
                    'game_code' => $game->code,
                    'gameuser_code' => $game_user->code,
                    'amount' => $cashback,
                    'bonus' => 0,
                    'total' => $cashback,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => 0,
                    'credit_bonus' => 0,
                    'credit_total' => 0,
                    'credit_before' => 0,
                    'credit_after' => 0,
                    'member_code' => $member->code,
                    'pro_code' => $pro_code,
                    'refer_code' => $code,
                    'refer_table' => 'members_ic',
                    'auto' => 'Y',
                    'remark' => 'ได้รับ IC จากการคำนวนประจำวัน ยอดเครดิตตอนคำนวนคือ '.$amount,
                    'kind' => 'IC',
                    'amount_balance' => $game_user->amount_balance,
                    'withdraw_limit' => $game_user->withdraw_limit,
                    'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                    'user_create' => 'System Auto',
                    'user_update' => 'System Auto',
                ]);

            } else {
                $total = ($member->balance_free + $cashback);

                $this->memberCreditFreeLogRepository->create([
                    'ip' => $ip,
                    'credit_type' => 'D',
                    'game_code' => 0,
                    'gameuser_code' => 0,
                    'amount' => 0,
                    'bonus' => $cashback,
                    'total' => $cashback,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => 0,
                    'credit_bonus' => $cashback,
                    'credit_total' => $cashback,
                    'credit_before' => 0,
                    'credit_after' => 0,
                    'member_code' => $member->code,
                    'pro_code' => $pro_code,
                    'refer_code' => $code,
                    'refer_table' => 'members_ic',
                    'auto' => 'Y',
                    'remark' => 'ยอดเงินเครดิต ตอนคำนวน คือ '.$amount,
                    'kind' => 'IC',
                    'amount_balance' => 0,
                    'withdraw_limit' => 0,
                    'withdraw_limit_amount' => 0,
                    'user_create' => 'System Auto',
                    'user_update' => 'System Auto',
                ]);

                $this->memberFreeCreditRepository->create([
                    'ip' => $ip,
                    'credit_type' => 'D',
                    'credit' => $cashback,
                    'credit_amount' => $cashback,
                    'credit_before' => $member->balance_free,
                    'credit_balance' => $total,
                    'member_code' => $downline_code,
                    'kind' => 'IC',
                    'remark' => 'เพิ่ม IC อ้างอิง record : '.$code,
                    'emp_code' => $emp_code,
                    'user_create' => $emp_name,
                    'user_update' => $emp_name,
                ]);

                $member->balance_free += $cashback;
                $member->save();

                //                $this->memberFreeCreditRepository->create([
                //                    'ip' => $ip,
                //                    'credit_type' => 'D',
                //                    'credit' => $cashback,
                //                    'credit_amount' => $cashback,
                //                    'credit_before' => $member->balance_free,
                //                    'credit_balance' => $total,
                //                    'member_code' => $member_code,
                //                    'kind' => 'IC',
                //                    'remark' => "เติม IC อ้างอิง record : " . $code,
                //                    'emp_code' => $emp_code,
                //                    'user_create' => $emp_name,
                //                    'user_update' => $emp_name,
                //                ]);

                //                $member->balance_free += $cashback;
                //                $member->save();
            }

            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();
            ActivityLogger::activitie('IC REFER USER : '.$member->user_name, 'พบข้อผิดพลาด IC');

            report($e);

            return false;
        }

        ActivityLogger::activitie('IC REFER USER : '.$member->user_name, 'ทำรายการ IC สำเร็จ');

        return true;
    }

    public function Delrefill(array $data): bool
    {

        $code = $data['code'];
        $member_code = $data['upline_code'];
        $downline_code = $data['member_code'];
        $amount = $data['balance'];
        $cashback = $data['ic'];
        $date_cashback = $data['date_cashback'];
        $ip = $data['ip'];
        $emp_code = $data['emp_code'];
        $emp_name = $data['emp_name'];

        $chk = $this->find($code);
        if ($chk) {
            if ($chk->topupic == 'N' || $chk->topupic == 'X') {
                return false;
            }
        }

        $member = $this->memberRepository->find($member_code);
        if (! $member) {
            return false;
        }

        $total = ($member->balance_free - $cashback);

        ActivityLogger::activitie('IC REFER USER : '.$member->user_name, 'เริ่มรายการ ลบ IC');

        DB::beginTransaction();
        try {

            if ($chk) {
                $chk->topupic = 'Y';
                $chk->save();

            } else {
                $bill = $this->create([
                    'member_code' => $member_code,
                    'downline_code' => $downline_code,
                    'date_cashback' => $date_cashback,
                    'balance' => $amount,
                    'ic' => $cashback,
                    'amount' => $cashback,
                    'topupic' => 'Y',
                    'ip_admin' => $ip,
                    'emp_code' => $emp_code,
                    'date_approve' => now()->toDateTimeString(),
                    'user_create' => $emp_name,
                    'user_update' => $emp_name,
                ]);
                $code = $bill->code;
            }

            $this->memberFreeCreditRepository->create([
                'ip' => $ip,
                'credit_type' => 'W',
                'credit' => $cashback,
                'credit_amount' => $cashback,
                'credit_before' => $member->balance_free,
                'credit_balance' => $total,
                'member_code' => $member_code,
                'kind' => 'IC',
                'remark' => 'ลบ IC อ้างอิง record : '.$code,
                'emp_code' => $emp_code,
                'user_create' => $emp_name,
                'user_update' => $emp_name,
            ]);

            $member->balance_free -= $cashback;
            $member->save();
            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();
            ActivityLogger::activitie('IC REFER USER : '.$member->user_name, 'พบข้อผิดพลาด IC');

            report($e);

            return false;
        }

        ActivityLogger::activitie('IC REFER USER : '.$member->user_name, 'ทำรายการ IC สำเร็จ');

        return true;
    }

    public function refillSeamless(array $data): bool
    {
        $config = $this->getCoreConfig();
        $code = $data['code'];
        $member_code = $data['upline_code'];
        $downline_code = $data['member_code'];
        $amount = $data['balance'];
        $icAmount = $data['ic'];
        $date_cashback = $data['date_cashback'];
        $sum_deposit = $data['sum_deposit'] ?? 0;
        $sum_withdraw = $data['sum_withdraw'] ?? 0;
        $sum_balance = $data['sum_balance'] ?? 0;
        $ip = $data['ip'];
        $emp_code = $data['emp_code'];
        $emp_name = $data['emp_name'];

        $chk = $this->find($code);
        if ($chk) {
            if ($chk->topupic == 'Y' || $chk->topupic == 'X') {
                return false;
            }
        }

        $promotion = DB::table('promotions')->where('id', 'pro_ic')->first();
        $pro_code = $promotion->code;
        $pro_name = $promotion->id;
        $turnpro = $promotion->turnpro;
        $withdraw_limit = $promotion->withdraw_limit;
        $withdraw_limit_rate = $promotion->withdraw_limit_rate;

        $member = $this->memberRepository->find($member_code);
        if (! $member) {
            return false;
        }

        if ($config->freecredit_open == 'Y') {
            $total = ($member->balance_free + $icAmount);
        } else {
            $total = ($member->balance + $icAmount);
        }

        ActivityLogger::activitie('IC REFER USER : '.$member->user_name, 'เริ่มรายการ IC');

        DB::beginTransaction();
        try {

            if ($chk) {
                $chk->topupic = 'Y';
                $chk->save();

                $code = $chk->code;

            } else {
                $bill = $this->create([
                    'member_code' => $member_code,
                    'downline_code' => $downline_code,
                    'date_cashback' => $date_cashback,
                    'balance' => $amount,
                    'ic' => $icAmount,
                    'amount' => $icAmount,
                    'topupic' => 'Y',
                    'ip_admin' => $ip,
                    'emp_code' => $emp_code,
                    'date_approve' => now()->toDateTimeString(),
                    'user_create' => $emp_name,
                    'user_update' => $emp_name,
                    'sum_balance' => $sum_balance,
                    'sum_deposit' => $sum_deposit,
                    'sum_withdraw' => $sum_withdraw,
                ]);

                $code = $bill->code;
            }

            if ($config->seamless == 'Y') {

                $game = core()->getGame();
                $game_user = $this->gameUserEventRepository->findOneWhere(['method' => 'IC', 'member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
                if (! $game_user) {
                    $game_user = $this->gameUserEventRepository->create([
                        'game_code' => $game->code,
                        'member_code' => $member->code,
                        'pro_code' => $pro_code,
                        'method' => 'IC',
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
                if ($config->freecredit_open == 'Y') {
                    $game_user->amount = $member->balance_free;
                } else {
                    $game_user->amount = $member->balance;
                }
                $game_user->pro_code = $pro_code;
                $game_user->bill_code = $code;
                $game_user->turnpro = $turnpro;
                $game_user->bonus += $icAmount;
                $game_user->amount_balance += ($icAmount * $turnpro);
                $game_user->withdraw_limit += $withdraw_limit;
                $game_user->withdraw_limit_rate = $withdraw_limit_rate;
                $game_user->withdraw_limit_amount += ($icAmount * $withdraw_limit_rate);
                $game_user->save();

                $member->ic += $icAmount;
                $member->save();

                if ($config->freecredit_open == 'Y') {
                    $this->memberCreditFreeLogRepository->create([
                        'ip' => $ip,
                        'credit_type' => 'D',
                        'game_code' => $game->code,
                        'gameuser_code' => $game_user->code,
                        'amount' => $icAmount,
                        'bonus' => 0,
                        'total' => $icAmount,
                        'balance_before' => 0,
                        'balance_after' => 0,
                        'credit' => 0,
                        'credit_bonus' => 0,
                        'credit_total' => 0,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'member_code' => $member->code,
                        'pro_code' => $pro_code,
                        'refer_code' => $code,
                        'refer_table' => 'members_ic',
                        'auto' => 'Y',
                        'remark' => 'ได้รับยอด IC (รอรับ) สะสมจากการคำนวนประจำวัน รวมฝาก '.$sum_deposit.' ถอน '.$sum_withdraw.' ยอดเงินตอนคำนวน '.$sum_balance.' คิดเป็นยอดคำนวน '.($sum_deposit - $sum_withdraw - $sum_balance),
                        'kind' => 'IC',
                        'amount_balance' => $game_user->amount_balance,
                        'withdraw_limit' => $game_user->withdraw_limit,
                        'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                        'emp_code' => $emp_code,
                        'user_create' => $emp_name,
                        'user_update' => $emp_name,
                    ]);
                } else {
                    $this->memberCreditLogRepository->create([
                        'ip' => $ip,
                        'credit_type' => 'D',
                        'game_code' => $game->code,
                        'gameuser_code' => $game_user->code,
                        'amount' => $icAmount,
                        'bonus' => 0,
                        'total' => $icAmount,
                        'balance_before' => 0,
                        'balance_after' => 0,
                        'credit' => 0,
                        'credit_bonus' => 0,
                        'credit_total' => 0,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'member_code' => $member->code,
                        'pro_code' => $pro_code,
                        'refer_code' => $code,
                        'refer_table' => 'members_ic',
                        'auto' => 'Y',
                        'remark' => 'ได้รับยอด IC (รอรับ) สะสมจากการคำนวนประจำวัน รวมฝาก '.$sum_deposit.' ถอน '.$sum_withdraw.' ยอดเงินตอนคำนวน '.$sum_balance.' คิดเป็นยอดคำนวน '.($sum_deposit - $sum_withdraw - $sum_balance),
                        'kind' => 'IC',
                        'amount_balance' => $game_user->amount_balance,
                        'withdraw_limit' => $game_user->withdraw_limit,
                        'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                        'emp_code' => $emp_code,
                        'user_create' => $emp_name,
                        'user_update' => $emp_name,
                    ]);
                }
            } else {
                if ($config->freecredit_open == 'Y') {
                    $total = ($member->balance_free + $icAmount);
                } else {
                    $total = ($member->balance + $icAmount);
                }

                $member->ic += $icAmount;
                $member->save();

                if ($config->freecredit_open == 'Y') {
                    $this->memberCreditFreeLogRepository->create([
                        'ip' => $ip,
                        'credit_type' => 'D',
                        'game_code' => 1,
                        'gameuser_code' => 0,
                        'amount' => $icAmount,
                        'bonus' => 0,
                        'total' => $icAmount,
                        'balance_before' => 0,
                        'balance_after' => 0,
                        'credit' => 0,
                        'credit_bonus' => 0,
                        'credit_total' => 0,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'member_code' => $member->code,
                        'pro_code' => $pro_code,
                        'refer_code' => $code,
                        'refer_table' => 'members_ic',
                        'auto' => 'Y',
                        'remark' => 'ได้รับยอด IC (รอรับ) สะสมจากการคำนวนประจำวัน รวมฝาก '.$sum_deposit.' ถอน '.$sum_withdraw.' ยอดเงินตอนคำนวน '.$sum_balance.' คิดเป็นยอดคำนวน '.($sum_deposit - $sum_withdraw - $sum_balance),
                        'kind' => 'IC',
                        'amount_balance' => 0,
                        'withdraw_limit' => $withdraw_limit,
                        'withdraw_limit_amount' => $icAmount * $withdraw_limit_rate,
                        'emp_code' => $emp_code,
                        'user_create' => $emp_name,
                        'user_update' => $emp_name,
                    ]);
                } else {
                    $this->memberCreditLogRepository->create([
                        'ip' => $ip,
                        'credit_type' => 'D',
                        'game_code' => 1,
                        'gameuser_code' => 0,
                        'amount' => $icAmount,
                        'bonus' => 0,
                        'total' => $icAmount,
                        'balance_before' => 0,
                        'balance_after' => 0,
                        'credit' => 0,
                        'credit_bonus' => 0,
                        'credit_total' => 0,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'member_code' => $member->code,
                        'pro_code' => $pro_code,
                        'refer_code' => $code,
                        'refer_table' => 'members_ic',
                        'auto' => 'Y',
                        'remark' => 'ได้รับยอด IC (รอรับ) สะสมจากการคำนวนประจำวัน รวมฝาก '.$sum_deposit.' ถอน '.$sum_withdraw.' ยอดเงินตอนคำนวน '.$sum_balance.' คิดเป็นยอดคำนวน '.($sum_deposit - $sum_withdraw - $sum_balance),
                        'kind' => 'IC',
                        'amount_balance' => 0,
                        'withdraw_limit' => $withdraw_limit,
                        'withdraw_limit_amount' => $icAmount * $withdraw_limit_rate,
                        'emp_code' => $emp_code,
                        'user_create' => $emp_name,
                        'user_update' => $emp_name,
                    ]);
                }
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            ActivityLogger::activitie('IC REFER USER : '.$member->user_name, 'พบข้อผิดพลาด IC');
            report($e);

            return false;
        }

        ActivityLogger::activitie('IC REFER USER : '.$member->user_name, 'ทำรายการ IC สำเร็จ');

        return true;
    }

    public function refillSeamlessDirect(array $data): bool
    {
        $config = $this->getCoreConfig();
        $code = $data['code'];
        $member_code = $data['upline_code'];
        $downline_code = $data['member_code'];
        $amount = $data['balance'];
        $icAmount = $data['ic'];
        $date_cashback = $data['date_cashback'];
        $sum_deposit = $data['sum_deposit'] ?? 0;
        $sum_withdraw = $data['sum_withdraw'] ?? 0;
        $sum_balance = $data['sum_balance'] ?? 0;
        $ip = $data['ip'];
        $emp_code = $data['emp_code'];
        $emp_name = $data['emp_name'];

        $chk = $this->find($code);
        if ($chk) {
            if ($chk->topupic == 'Y' || $chk->topupic == 'X') {
                return false;
            }
        }

        $promotion = DB::table('promotions')->where('id', 'pro_ic')->first();
        $pro_code = $promotion->code;
        $pro_name = $promotion->id;
        $turnpro = $promotion->turnpro;
        $withdraw_limit = $promotion->withdraw_limit;
        $withdraw_limit_rate = $promotion->withdraw_limit_rate;

        $member = $this->memberRepository->find($member_code);
        if (! $member) {
            return false;
        }

        if ($config->freecredit_open == 'Y') {
            $total = ($member->balance_free + $icAmount);
        } else {
            $total = ($member->balance + $icAmount);
        }

        ActivityLogger::activitie('IC DIRECT REFER USER : '.$member->user_name, 'เริ่มรายการ IC (wallet)');

        DB::beginTransaction();
        try {

            if ($chk) {
                $chk->topupic = 'Y';
                $chk->save();

                $code = $chk->code;

            } else {
                $bill = $this->create([
                    'member_code' => $member_code,
                    'downline_code' => $downline_code,
                    'date_cashback' => $date_cashback,
                    'balance' => $amount,
                    'ic' => $icAmount,
                    'amount' => $icAmount,
                    'topupic' => 'Y',
                    'ip_admin' => $ip,
                    'emp_code' => $emp_code,
                    'date_approve' => now()->toDateTimeString(),
                    'user_create' => $emp_name,
                    'user_update' => $emp_name,
                    'sum_balance' => $sum_balance,
                    'sum_deposit' => $sum_deposit,
                    'sum_withdraw' => $sum_withdraw,
                ]);

                $code = $bill->code;
            }

            if ($config->seamless == 'Y') {

                $game = core()->getGame();
                $game_user = $this->gameUserEventRepository->findOneWhere(['method' => 'IC', 'member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
                if (! $game_user) {
                    $game_user = $this->gameUserEventRepository->create([
                        'game_code' => $game->code,
                        'member_code' => $member->code,
                        'pro_code' => $pro_code,
                        'method' => 'IC',
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
                if ($config->freecredit_open == 'Y') {
                    $game_user->amount = $member->balance_free;
                } else {
                    $game_user->amount = $member->balance;
                }
                $game_user->pro_code = $pro_code;
                $game_user->bill_code = $code;
                $game_user->turnpro = $turnpro;
                $game_user->bonus += $icAmount;
                $game_user->amount_balance += ($icAmount * $turnpro);
                $game_user->withdraw_limit += $withdraw_limit;
                $game_user->withdraw_limit_rate = $withdraw_limit_rate;
                $game_user->withdraw_limit_amount += ($icAmount * $withdraw_limit_rate);
                $game_user->save();

                $balanceBefore = (float) $member->balance;
                $member->balance += $icAmount;
                $member->save();

                $this->memberCreditLogRepository->appendWalletTransaction(
                    (int) $member->code,
                    'CREDIT',
                    (float) $icAmount,
                    $balanceBefore,
                    (float) $member->balance,
                    'TRANCB',
                    (int) $code,
                    'ic:members_ic:'.$code,
                    'Auto IC refill via refillSeamlessDirect',
                    [
                        'source' => 'MemberIcRepository::refillSeamlessDirect',
                        'event' => 'IC',
                        'freecredit_open' => (string) ($config->freecredit_open ?? 'N'),
                    ],
                    'system',
                    null
                );


                if ($config->freecredit_open == 'Y') {
                    $this->memberCreditFreeLogRepository->create([
                        'ip' => $ip,
                        'credit_type' => 'D',
                        'game_code' => $game->code,
                        'gameuser_code' => $game_user->code,
                        'amount' => $icAmount,
                        'bonus' => 0,
                        'total' => $icAmount,
                        'balance_before' => $balanceBefore,
                        'balance_after' => (float) $member->balance,
                        'credit' => 0,
                        'credit_bonus' => 0,
                        'credit_total' => 0,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'member_code' => $member->code,
                        'pro_code' => $pro_code,
                        'refer_code' => $code,
                        'refer_table' => 'members_ic',
                        'auto' => 'Y',
                        'remark' => 'ได้รับยอด IC (wallet) สะสมจากการคำนวนประจำวัน รวมฝาก '.$sum_deposit.' ถอน '.$sum_withdraw.' ยอดเงินตอนคำนวน '.$sum_balance.' คิดเป็นยอดคำนวน '.($sum_deposit - $sum_withdraw - $sum_balance),
                        'kind' => 'IC',
                        'amount_balance' => $game_user->amount_balance,
                        'withdraw_limit' => $game_user->withdraw_limit,
                        'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                        'emp_code' => $emp_code,
                        'user_create' => $emp_name,
                        'user_update' => $emp_name,
                    ]);
                } else {
                    $this->memberCreditLogRepository->create([
                        'ip' => $ip,
                        'credit_type' => 'D',
                        'game_code' => $game->code,
                        'gameuser_code' => $game_user->code,
                        'amount' => $icAmount,
                        'bonus' => 0,
                        'total' => $icAmount,
                        'balance_before' => $balanceBefore,
                        'balance_after' => (float) $member->balance,
                        'credit' => 0,
                        'credit_bonus' => 0,
                        'credit_total' => 0,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'member_code' => $member->code,
                        'pro_code' => $pro_code,
                        'refer_code' => $code,
                        'refer_table' => 'members_ic',
                        'auto' => 'Y',
                        'remark' => 'ได้รับยอด IC (wallet) สะสมจากการคำนวนประจำวัน รวมฝาก '.$sum_deposit.' ถอน '.$sum_withdraw.' ยอดเงินตอนคำนวน '.$sum_balance.' คิดเป็นยอดคำนวน '.($sum_deposit - $sum_withdraw - $sum_balance),
                        'kind' => 'IC',
                        'amount_balance' => $game_user->amount_balance,
                        'withdraw_limit' => $game_user->withdraw_limit,
                        'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                        'emp_code' => $emp_code,
                        'user_create' => $emp_name,
                        'user_update' => $emp_name,
                    ]);
                }
            } else {
                if ($config->freecredit_open == 'Y') {
                    $total = ($member->balance_free + $icAmount);
                } else {
                    $total = ($member->balance + $icAmount);
                }

                $balanceBefore = (float) $member->balance;
                $member->balance += $icAmount;
                $member->save();

                $this->memberCreditLogRepository->appendWalletTransaction(
                    (int) $member->code,
                    'CREDIT',
                    (float) $icAmount,
                    $balanceBefore,
                    (float) $member->balance,
                    'TRANCB',
                    (int) $code,
                    'ic:members_ic:'.$code,
                    'Auto IC refill via refillSeamlessDirect',
                    [
                        'source' => 'MemberIcRepository::refillSeamlessDirect',
                        'event' => 'IC',
                        'freecredit_open' => (string) ($config->freecredit_open ?? 'N'),
                    ],
                    'system',
                    null
                );


                if ($config->freecredit_open == 'Y') {
                    $this->memberCreditFreeLogRepository->create([
                        'ip' => $ip,
                        'credit_type' => 'D',
                        'game_code' => 1,
                        'gameuser_code' => 0,
                        'amount' => $icAmount,
                        'bonus' => 0,
                        'total' => $icAmount,
                        'balance_before' => $balanceBefore,
                        'balance_after' => (float) $member->balance,
                        'credit' => 0,
                        'credit_bonus' => 0,
                        'credit_total' => 0,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'member_code' => $member->code,
                        'pro_code' => $pro_code,
                        'refer_code' => $code,
                        'refer_table' => 'members_ic',
                        'auto' => 'Y',
                        'remark' => 'ได้รับยอด IC (wallet) สะสมจากการคำนวนประจำวัน รวมฝาก '.$sum_deposit.' ถอน '.$sum_withdraw.' ยอดเงินตอนคำนวน '.$sum_balance.' คิดเป็นยอดคำนวน '.($sum_deposit - $sum_withdraw - $sum_balance),
                        'kind' => 'IC',
                        'amount_balance' => 0,
                        'withdraw_limit' => $withdraw_limit,
                        'withdraw_limit_amount' => $icAmount * $withdraw_limit_rate,
                        'emp_code' => $emp_code,
                        'user_create' => $emp_name,
                        'user_update' => $emp_name,
                    ]);
                } else {
                    $this->memberCreditLogRepository->create([
                        'ip' => $ip,
                        'credit_type' => 'D',
                        'game_code' => 1,
                        'gameuser_code' => 0,
                        'amount' => $icAmount,
                        'bonus' => 0,
                        'total' => $icAmount,
                        'balance_before' => $balanceBefore,
                        'balance_after' => (float) $member->balance,
                        'credit' => 0,
                        'credit_bonus' => 0,
                        'credit_total' => 0,
                        'credit_before' => 0,
                        'credit_after' => 0,
                        'member_code' => $member->code,
                        'pro_code' => $pro_code,
                        'refer_code' => $code,
                        'refer_table' => 'members_ic',
                        'auto' => 'Y',
                        'remark' => 'ได้รับยอด IC (wallet) สะสมจากการคำนวนประจำวัน รวมฝาก '.$sum_deposit.' ถอน '.$sum_withdraw.' ยอดเงินตอนคำนวน '.$sum_balance.' คิดเป็นยอดคำนวน '.($sum_deposit - $sum_withdraw - $sum_balance),
                        'kind' => 'IC',
                        'amount_balance' => 0,
                        'withdraw_limit' => $withdraw_limit,
                        'withdraw_limit_amount' => $icAmount * $withdraw_limit_rate,
                        'emp_code' => $emp_code,
                        'user_create' => $emp_name,
                        'user_update' => $emp_name,
                    ]);
                }
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            ActivityLogger::activitie('IC DIRECT REFER USER : '.$member->user_name, 'พบข้อผิดพลาด IC (wallet)');
            report($e);

            return false;
        }

        ActivityLogger::activitie('IC DIRECT REFER USER : '.$member->user_name, 'ทำรายการ IC (wallet) สำเร็จ');

        return true;
    }
}
