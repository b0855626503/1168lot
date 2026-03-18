<?php

namespace Gametech\Payment\Repositories;

use Gametech\Core\Eloquent\Repository;
use Gametech\Game\Repositories\GameUserEventRepository;
use Gametech\Game\Repositories\GameUserFreeRepository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\LogAdmin\Http\Traits\ActivityLogger;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser;
use Gametech\Member\Repositories\MemberCreditFreeLogRepository;
use Gametech\Member\Repositories\MemberCreditLogRepository;
use Gametech\Member\Repositories\MemberPromotionLogRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Promotion\Repositories\PromotionRepository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Throwable;

class PaymentPromotionRepository extends Repository
{
    use ActivityLogger;

    use ActivityLoggerUser;

    private $memberRepository;

    private $memberCreditLogRepository;

    private $memberCreditFreeLogRepository;

    private $promotionRepository;

    private $gameUserRepository;

    private $gameUserEventRepository;

    private $gameUserFreeRepository;

    private $memberPromotionLogRepository;


    public function __construct
    (
        MemberRepository              $memberRepo,
        MemberCreditLogRepository     $memberCreditLogRepo,
        MemberCreditFreeLogRepository $memberCreditFreeLogRepo,
        MemberPromotionLogRepository  $memberPromotionLogRepo,
        PromotionRepository           $promotionRepo,
        GameUserRepository            $gameUserRepo,
        GameUserFreeRepository        $gameUserFreeRepo,
        GameUserEventRepository       $gameUserEventRepo,

        App                           $app
    )
    {
        $this->memberRepository = $memberRepo;

        $this->memberCreditLogRepository = $memberCreditLogRepo;

        $this->memberCreditFreeLogRepository = $memberCreditFreeLogRepo;

        $this->promotionRepository = $promotionRepo;

        $this->gameUserRepository = $gameUserRepo;

        $this->gameUserEventRepository = $gameUserEventRepo;

        $this->gameUserFreeRepository = $gameUserFreeRepo;


        $this->memberPromotionLogRepository = $memberPromotionLogRepo;

        parent::__construct($app);
    }

    public function checkFastStart($amount, $user_topup_code, $payment_code = 0): bool
    {
        $datenow = now()->toDateTimeString();
        $ip = request()->ip();

        $chk = $this->promotionRepository->findOneByField('id', 'pro_faststart');
        if ($chk->enable != 'Y' || $chk->active != 'Y' || $chk->use_auto != 'Y') {
            return false;
        }

        $user_topup = $this->memberRepository->find($user_topup_code);
        if (empty($user_topup)) {
            return false;
        }



//        $expire = Carbon::parse($user_topup->date_regis)->addMonth();
//        $today = now()->toDateString();
//        if ($today > $expire) {
//            return false;
//        }


        $upline_code = $user_topup->upline_code;
        $downline_code = $user_topup_code;
        ActivityLoggerUser::activity('FASTSTART NORMAL REFER ID : ' . $user_topup->user_name, 'เริ่มรายการ FASTSTART ให้กับ UPLINE CODE : ' . $upline_code);


        if ($upline_code > 0) {
            $cnt = $this->findOneWhere(['member_code' => $upline_code, 'downline_code' => $downline_code, 'pro_code' => $chk->code]);
            if (empty($cnt)) {

                if($user_topup->count_deposit >= 1){
                    ActivityLoggerUser::activity('FASTSTART NORMAL REFER ID : ' . $user_topup->user_name, 'เติมไม่ใช่ครั้งแรก !! ');
                    return false;
                }

                ActivityLoggerUser::activity('FASTSTART NORMAL REFER ID : ' . $user_topup->user_name, 'เติมครั้งแรก ' . $amount . ' บาท !! กำลังเชคโบนัสให้กับ UPLINE CODE : ' . $upline_code);
                $promotion = $this->promotionRepository->checkPromotionId("pro_faststart", $amount, $datenow);
                ActivityLoggerUser::activity('FASTSTART NORMAL REFER ID : ' . $user_topup->user_name, 'คำนวนจากเติม ' . $amount . ' บาท !! ได้โบนัส ' . $promotion['bonus'] . ' ' . json_encode($promotion));

                $bonus = $promotion['bonus'];
                $total = $promotion['total'];
                if ($bonus > 0) {

                    $member = $this->memberRepository->find($upline_code);
                    if (empty($member)) {
                        return false;
                    }

                    $credit_before = $member['balance'];
                    $credit_after = ($credit_before + $bonus);

                    ActivityLoggerUser::activity('FASTSTART NORMAL REFER ID : ' . $user_topup->user_name, 'มอบโบนัส FASTSTART ให้กับ User : ' . $member->user_name);


                    DB::beginTransaction();
                    try {

                        $chknew = $this->findOneWhere(['member_code' => $upline_code, 'downline_code' => $downline_code, 'pro_code' => $chk->code]);
                        if ($chknew) {
                            ActivityLoggerUser::activity('FASTSTART NORMAL REFER ID : ' . $user_topup->user_name, 'ไม่สามารถทำรายการ FASTSTART เนื่องจาก log ซ้ำ ให้กับ User : ' . $member->user_name);
                            DB::rollBack();
                            return false;
                        }

                        $this->create([
                            'ip' => $ip,
                            'pro_code' => $chk->code,
                            'amount' => $amount,
                            'credit' => $amount,
                            'credit_bonus' => $bonus,
                            'credit_before' => $credit_before,
                            'credit_after' => $credit_after,
                            'credit_balance' => $total,
                            'member_code' => $upline_code,
                            'downline_code' => $downline_code,
                            'remark' => 'Refer From User : ' . $user_topup->user_name . ' Deposit ID : ' . $payment_code . ' ' . $promotion['type'],
                            'user_create' => "System Auto",
                            'user_update' => "System Auto"
                        ]);


                        $this->memberCreditLogRepository->create([

                            'ip' => $ip,
                            'credit_type' => 'D',
                            'amount' => $amount,
                            'bonus' => $bonus,
                            'total' => $bonus,
                            'balance_before' => $credit_before,
                            'balance_after' => $credit_after,
                            'credit' => 0,
                            'credit_bonus' => 0,
                            'credit_total' => 0,
                            'credit_before' => 0,
                            'credit_after' => 0,
                            'member_code' => $upline_code,
                            'pro_code' => $chk->code,
                            'refer_code' => $payment_code,
                            'refer_table' => 'banks_payment',
                            'auto' => 'Y',
                            'remark' => 'Refer From User : ' . $user_topup->user_name . ' Deposit ID : ' . $payment_code . ' ' . $promotion['type'],
                            'kind' => 'FASTSTART',
                            'user_create' => "System Auto",
                            'user_update' => "System Auto"
                        ]);

                        $member->balance += $bonus;
                        $member->save();
                        DB::commit();

                    } catch (Throwable $e) {

                        DB::rollBack();
                        ActivityLoggerUser::activity('FASTSTART NORMAL REFER ID : ' . $user_topup->user_name, 'ไม่สามารถทำรายการ FASTSTART ให้กับ User : ' . $member->user_name);
                        report($e);
                        return false;
                    }

                    ActivityLoggerUser::activity('FASTSTART NORMAL REFER ID : ' . $user_topup->user_name, 'ทำรายการ FASTSTART ให้กับ User : ' . $member->user_name . ' สำเร็จ');

                } else {

                    ActivityLoggerUser::activity('FASTSTART NORMAL REFER ID : ' . $user_topup->user_name, 'โบนัสคำนวนได้ ' . $bonus . ' ?? อดโบนัสให้กับ UPLINE CODE : ' . $upline_code);

                }
            } else {
                ActivityLoggerUser::activity('FASTSTART NORMAL REFER ID : ' . $user_topup->user_name, 'ไม่ใช่ครั้งแรก !! อดโบนัสให้กับ UPLINE CODE : ' . $upline_code);
            }

        } else {

            ActivityLoggerUser::activity('FASTSTART NORMAL REFER ID : ' . $user_topup->user_name, 'จบการทำงาน UPLINE CODE : ' . $upline_code);
        }


        return true;


    }

    public function checkFastStartSingle_($amount, $user_topup_code, $payment_code = 0): bool
    {
        $datenow = now()->toDateTimeString();
        $ip = request()->ip();

        $chk = $this->promotionRepository->findOneByField('id', 'pro_faststart');
        if (empty($chk)) {
            return true;
        }
        if ($chk->enable != 'Y' || $chk->active != 'Y' || $chk->use_auto != 'Y') {

            return false;
        }


        $user_topup = $this->memberRepository->find($user_topup_code);
        if (empty($user_topup)) {
            return false;
        }

        $upline_code = $user_topup->upline_code;
        $downline_code = $user_topup_code;
        ActivityLoggerUser::activity('FASTSTART S REFER ID : ' . $user_topup->user_name, 'เริ่มรายการ FASTSTART ให้กับ UPLINE CODE : ' . $upline_code);


        if ($upline_code > 0) {
            $cnt = $this->findOneWhere(['member_code' => $upline_code, 'downline_code' => $downline_code, 'pro_code' => $chk->code]);
            if (empty($cnt)) {
                ActivityLoggerUser::activity('FASTSTART S REFER ID : ' . $user_topup->user_name, 'เติมครั้งแรก ' . $amount . ' บาท !! กำลังเชคโบนัสให้กับ UPLINE CODE : ' . $upline_code);
                $promotion = $this->promotionRepository->checkPromotionId("pro_faststart", $amount, $datenow);
                $bonus = $promotion['bonus'];
                $total = $promotion['total'];
                if ($bonus > 0) {

                    $member = $this->memberRepository->find($upline_code);
                    if (empty($member)) {
                        return false;
                    }

                    $game = core()->getGame();
                    $game_user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
                    $game_code = $game->code;
                    $user_name = $game_user->user_name;
                    $game_name = $game->name;
                    $pro_name = $promotion['pro_name'];

                    $credit_before = $game_user['balance'];
                    $credit_after = ($credit_before + $bonus);

                    $money_text = 'โบนัส ' . $bonus . 'จากโปร ' . $pro_name;

                    ActivityLoggerUser::activity('FASTSTART S REFER ID : ' . $user_topup->user_name, 'มอบโบนัส FASTSTART ให้กับ User : ' . $member->user_name);


                    $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $bonus, false);
                    if ($response['success'] !== true) {
                        ActivityLoggerUser::activity('FASTSTART S REFER ID : ' . $user_topup->user_name . ' ฝากเงินเข้าเกม ' . $game_name, $money_text . ' ไม่สามารถฝากเงินเข้าเกมได้');

                        return false;
                    } else {
                        ActivityLoggerUser::activity('FASTSTART S REFER ID : ' . $user_topup->user_name . ' ฝากเงินเข้าเกม ' . $game_name, $money_text . ' ระบบทำการฝากเงินเข้าเกมแล้ว');
                    }


                    DB::beginTransaction();
                    try {

                        $this->create([
                            'ip' => $ip,
                            'pro_code' => $chk->code,
                            'amount' => $amount,
                            'credit' => $amount,
                            'credit_bonus' => $bonus,
                            'credit_before' => $credit_before,
                            'credit_after' => $credit_after,
                            'credit_balance' => $total,
                            'member_code' => $upline_code,
                            'downline_code' => $downline_code,
                            'remark' => 'Refer From User : ' . $user_topup->user_name . ' Deposit ID : ' . $payment_code . ' ' . $promotion['type'],
                            'user_create' => "System Auto",
                            'user_update' => "System Auto"
                        ]);

                        $this->memberCreditLogRepository->create([
                            'ip' => $ip,
                            'credit_type' => 'D',
                            'game_code' => $game->code,
                            'gameuser_code' => $game_user->code,
                            'amount' => $amount,
                            'bonus' => $bonus,
                            'total' => $bonus,
                            'balance_before' => 0,
                            'balance_after' => 0,
                            'credit' => 0,
                            'credit_bonus' => 0,
                            'credit_total' => 0,
                            'credit_before' => $credit_before,
                            'credit_after' => $credit_after,
                            'member_code' => $upline_code,
                            'pro_code' => $chk->code,
                            'refer_code' => $payment_code,
                            'refer_table' => 'banks_payment',
                            'auto' => 'Y',
                            'remark' => 'Refer From User : ' . $user_topup->user_name . ' Deposit ID : ' . $payment_code . ' ' . $promotion['type'],
                            'kind' => 'FASTSTART',
                            'user_create' => "System Auto",
                            'user_update' => "System Auto"
                        ]);

                        $member->balance += $bonus;
                        $member->save();

                        $game_user->balance += $bonus;
                        $game_user->save();


                        DB::commit();
                    } catch (Throwable $e) {
                        DB::rollBack();
                        ActivityLoggerUser::activity('FASTSTART S REFER ID : ' . $user_topup->user_name, 'ไม่สามารถทำรายการ FASTSTART ให้กับ User : ' . $member->user_name);

                        $response = $this->gameUserRepository->UserWithdraw($game_code, $user_name, $bonus);
                        if ($response['success'] === true) {
                            ActivityLoggerUser::activity('FASTSTART S REFER ID : ' . $user_topup->user_name . ' ฝากเงินเข้าเกม ' . $game_name, $money_text . ' ระบบทำการถอนเงินออกจากเกมแล้ว');


                        } else {
                            ActivityLoggerUser::activity('FASTSTART S REFER ID : ' . $user_topup->user_name . ' ฝากเงินเข้าเกม ' . $game_name, $money_text . ' ระบบไม่สามารถถอนเงินออกจากเกมได้ จึงไม่ได้หักยอดเงินออก');
                        }

                        report($e);
                        return false;
                    }

                    ActivityLogger::activitie('FASTSTART S REFER ID : ' . $user_topup->user_name, 'ทำรายการ FASTSTART ให้กับ User : ' . $member->user_name . ' สำเร็จ');

                } else {

                    ActivityLogger::activitie('FASTSTART S REFER ID : ' . $user_topup->user_name, 'โบนัสคำนวนได้ ' . $bonus . ' ?? อดโบนัสให้กับ UPLINE CODE : ' . $upline_code);

                }
            } else {
                ActivityLogger::activitie('FASTSTART S REFER ID : ' . $user_topup->user_name, 'ไม่ใช่ครั้งแรก !! อดโบนัสให้กับ UPLINE CODE : ' . $upline_code);
            }

        } else {

            ActivityLogger::activitie('FASTSTART S REFER ID : ' . $user_topup->user_name, 'จบการทำงาน UPLINE CODE : ' . $upline_code);
        }


        return true;


    }

    public function checkFastStartSingle($amount, $user_topup_code, $payment_code = 0): bool
    {
        $datenow = now()->toDateTimeString();
        $ip = request()->ip();
        $config = core()->getConfigData();

        $chk = $this->promotionRepository->findOneByField('id', 'pro_faststart');
        if (empty($chk)) {
            return true;
        }
        if ($chk->enable != 'Y' || $chk->active != 'Y') {

            return false;
        }


        $user_topup = $this->memberRepository->find($user_topup_code);
        if (empty($user_topup)) {
            return false;
        }

        $upline_code = $user_topup->upline_code;
        $downline_code = $user_topup_code;
        ActivityLoggerUser::activity('FASTSTART REFER ID : ' . $user_topup->user_name, 'เริ่มรายการ FASTSTART ให้กับ UPLINE CODE : ' . $upline_code);


        if ($upline_code > 0) {
            $cnt = $this->findOneWhere(['member_code' => $upline_code, 'downline_code' => $downline_code, 'pro_code' => $chk->code]);
            if (empty($cnt)) {
                ActivityLoggerUser::activity('FASTSTART REFER ID : ' . $user_topup->user_name, 'เติมครั้งแรก ' . $amount . ' บาท !! กำลังเชคโบนัสให้กับ UPLINE CODE : ' . $upline_code);
                $promotion = $this->promotionRepository->checkPromotionId("pro_faststart", $amount, $datenow);
                $bonus = $promotion['bonus'];
                $total = $promotion['total'];
                $pro_code = $promotion['pro_code'];
                $pro_name = $promotion['pro_name'];
                $turnpro = $promotion['turnpro'];
                $withdraw_limit = $promotion['withdraw_limit'];
                $withdraw_limit_rate = $promotion['withdraw_limit_rate'];


                if ($bonus > 0) {

                    $member = $this->memberRepository->find($upline_code);
                    if (empty($member)) {
                        return false;
                    }

                    $money_text = 'โบนัส ' . $bonus . 'จากโปร ' . $pro_name;

                    ActivityLoggerUser::activity('FASTSTART REFER ID : ' . $user_topup->user_name, 'มอบโบนัส FASTSTART ให้กับ User : ' . $member->user_name);

                    $game = core()->getGame();
                    $game_user = $this->gameUserEventRepository->findOneWhere(['method' => 'FASTSTART', 'member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
                    if (!$game_user) {
                        $game_user = $this->gameUserEventRepository->create([
                            'game_code' => $game->code,
                            'member_code' => $member->code,
                            'pro_code' => $pro_code,
                            'method' => 'FASTSTART',
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

                    $game_code = $game->code;
                    $user_name = $game_user->user_name;
                    $game_name = $game->name;
                    $user_code = $game_user->code;

                    DB::beginTransaction();
                    try {


                        if ($chk->use_auto == 'Y') {

                            if ($config->freecredit_open == 'Y') {
                                $bill = $this->create([
                                    'ip' => $ip,
                                    'pro_code' => $chk->code,
                                    'amount' => $amount,
                                    'credit' => $amount,
                                    'credit_bonus' => $bonus,
                                    'credit_before' => 0,
                                    'credit_after' => 0,
                                    'credit_balance' => $total,
                                    'member_code' => $upline_code,
                                    'downline_code' => $downline_code,
                                    'remark' => 'ได้รับค่าแนะนำ จาก : ' . $user_topup->user_name . ' รายการฝากที่ : ' . $payment_code . ' ' . $promotion['type'],
                                    'user_create' => "System Auto",
                                    'user_update' => "System Auto"
                                ]);

                                $game_user->amount = $member->balance_free;
                                $game_user->pro_code = $pro_code;
                                $game_user->bill_code = $bill->code;
                                $game_user->turnpro = $turnpro;
                                $game_user->bonus += $bonus;
                                $game_user->amount_balance += ($bonus * $turnpro);
                                $game_user->withdraw_limit += $withdraw_limit;
                                $game_user->withdraw_limit_rate = $withdraw_limit_rate;
                                $game_user->withdraw_limit_amount += ($bonus * $withdraw_limit_rate);
                                $game_user->save();

                                $member->faststart += $bonus;
                                $member->save();

                                $this->memberCreditFreeLogRepository->create([
                                    'ip' => $ip,
                                    'credit_type' => 'D',
                                    'game_code' => $game->code,
                                    'gameuser_code' => $game_user->code,
                                    'amount' => $bonus,
                                    'bonus' => 0,
                                    'total' => $bonus,
                                    'balance_before' => 0,
                                    'balance_after' => 0,
                                    'credit' => 0,
                                    'credit_bonus' => 0,
                                    'credit_total' => 0,
                                    'credit_before' => 0,
                                    'credit_after' => 0,
                                    'member_code' => $upline_code,
                                    'pro_code' => $chk->code,
                                    'refer_code' => $payment_code,
                                    'refer_table' => 'banks_payment',
                                    'auto' => 'Y',
                                    'remark' => 'จาก : ' . $user_topup->user_name . ' รายการฝากที่ : ' . $payment_code . ' ' . $promotion['type'],
                                    'kind' => 'FASTSTART',
                                    'amount_balance' => $game_user->amount_balance,
                                    'withdraw_limit' => $game_user->withdraw_limit,
                                    'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                                    'user_create' => "System Auto",
                                    'user_update' => "System Auto"
                                ]);


                                $this->memberPromotionLogRepository->create([
                                    'date_start' => now()->toDateString(),
                                    'bill_code' => $bill->code,
                                    'member_code' => $member->code,
                                    'game_code' => $game_code,
                                    'game_name' => $game_name,
                                    'gameuser_code' => $user_code,
                                    'pro_code' => $pro_code,
                                    'pro_name' => $pro_name,
                                    'turnpro' => $turnpro,
                                    'balance' => $member->balance_free,
                                    'amount' => 0,
                                    'bonus' => $bonus,
                                    'amount_balance' => ($bonus * $turnpro),
                                    'total_amount_balance' => ((0) + ($bonus * $turnpro)),
                                    'withdraw_limit' => $withdraw_limit,
                                    'withdraw_limit_rate' => $withdraw_limit_rate,
                                    'complete' => 'N',
                                    'enable' => 'Y',
                                    'user_create' => $member['name'],
                                    'user_update' => $member['name']
                                ]);
                            } else {

                                $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'enable' => 'Y']);
                                if (!$gameuser) {
                                    return false;
                                }

                                $bill = $this->create([
                                    'ip' => $ip,
                                    'pro_code' => $chk->code,
                                    'amount' => $amount,
                                    'credit' => $amount,
                                    'credit_bonus' => $bonus,
                                    'credit_before' => 0,
                                    'credit_after' => 0,
                                    'credit_balance' => $total,
                                    'member_code' => $upline_code,
                                    'downline_code' => $downline_code,
                                    'remark' => 'ได้รับค่าแนะนำ จาก : ' . $user_topup->user_name . ' รายการฝากที่ : ' . $payment_code . ' ' . $promotion['type'],
                                    'user_create' => "System Auto",
                                    'user_update' => "System Auto"
                                ]);

//                                $game_user->amount = $member->balance_free;
//                                $game_user->pro_code = $pro_code;
//                                $game_user->bill_code = $bill->code;
                                $gameuser->turnpro = $turnpro;
//                                $game_user->bonus += $bonus;
                                $gameuser->amount_balance += ($bonus * $turnpro);
                                $gameuser->withdraw_limit += $withdraw_limit;
                                $gameuser->withdraw_limit_rate = $withdraw_limit_rate;
                                $gameuser->withdraw_limit_amount += ($bonus * $withdraw_limit_rate);
//                                $game_user->save();

                                $member->faststart += $bonus;
                                $member->save();


                                $this->memberCreditLogRepository->create([
                                    'ip' => $ip,
                                    'credit_type' => 'D',
                                    'game_code' => $game->code,
                                    'gameuser_code' => $game_user->code,
                                    'amount' => $bonus,
                                    'bonus' => 0,
                                    'total' => $bonus,
                                    'balance_before' => $member->balance,
                                    'balance_after' => ($member->balance + $bonus),
                                    'credit' => 0,
                                    'credit_bonus' => 0,
                                    'credit_total' => 0,
                                    'credit_before' => $member->balance,
                                    'credit_after' => ($member->balance + $bonus),
                                    'member_code' => $upline_code,
                                    'pro_code' => $chk->code,
                                    'refer_code' => $payment_code,
                                    'refer_table' => 'banks_payment',
                                    'auto' => 'Y',
                                    'remark' => 'ค่าแนะนำ เข้ากระเป่าโบนัส รอรับ จาก : ' . $user_topup->user_name . ' รายการฝากที่ : ' . $payment_code . ' ' . $promotion['type'],
                                    'kind' => 'FASTSTART',
                                    'amount_balance' => $gameuser->amount_balance,
                                    'withdraw_limit' => $gameuser->withdraw_limit,
                                    'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
                                    'user_create' => "System Auto",
                                    'user_update' => "System Auto"
                                ]);

                            }

                        } else {

                            if ($config->freecredit_open == 'Y') {

                                $gameuser = $this->gameUserFreeRepository->findOneWhere(['member_code' => $member->code, 'enable' => 'Y']);
                                if (!$gameuser) {
                                    return false;
                                }

                                $bill = $this->create([
                                    'ip' => $ip,
                                    'enable' => 'N',
                                    'pro_code' => $chk->code,
                                    'amount' => $amount,
                                    'credit' => $amount,
                                    'credit_bonus' => $bonus,
                                    'credit_before' => 0,
                                    'credit_after' => 0,
                                    'credit_balance' => 0,
                                    'member_code' => $upline_code,
                                    'downline_code' => $downline_code,
                                    'remark' => 'จาก : ' . $user_topup->user_name . ' รายการฝากที่ : ' . $payment_code . ' ' . $promotion['type'],
                                    'user_create' => "System Auto",
                                    'user_update' => "System Auto"
                                ]);

                                $this->memberCreditFreeLogRepository->create([
                                    'ip' => $ip,
                                    'credit_type' => 'D',
                                    'game_code' => $game->code,
                                    'gameuser_code' => $game_user->code,
                                    'amount' => 0,
                                    'bonus' => $bonus,
                                    'total' => $bonus,
                                    'balance_before' => 0,
                                    'balance_after' => 0,
                                    'credit' => 0,
                                    'credit_bonus' => 0,
                                    'credit_total' => 0,
                                    'credit_before' => 0,
                                    'credit_after' => 0,
                                    'member_code' => $upline_code,
                                    'pro_code' => $chk->code,
                                    'refer_code' => $payment_code,
                                    'refer_table' => 'banks_payment',
                                    'auto' => 'N',
                                    'enable' => 'Y',
                                    'remark' => 'จาก : ' . $user_topup->user_name . ' รายการฝากที่ : ' . $payment_code . ' ' . $promotion['type'],
                                    'kind' => 'FASTSTARTS',
                                    'amount_balance' => $game_user->amount_balance,
                                    'withdraw_limit' => $game_user->withdraw_limit,
                                    'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                                    'user_create' => "System Auto",
                                    'user_update' => "System Auto"
                                ]);
                            } else {

                                $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'enable' => 'Y']);
                                if (!$gameuser) {
                                    return false;
                                }

                                $bill = $this->create([
                                    'ip' => $ip,
                                    'enable' => 'N',
                                    'pro_code' => $chk->code,
                                    'amount' => $amount,
                                    'credit' => $amount,
                                    'credit_bonus' => $bonus,
                                    'credit_before' => 0,
                                    'credit_after' => 0,
                                    'credit_balance' => 0,
                                    'member_code' => $upline_code,
                                    'downline_code' => $downline_code,
                                    'remark' => 'จาก : ' . $user_topup->user_name . ' รายการฝากที่ : ' . $payment_code . ' ' . $promotion['type'],
                                    'user_create' => "System Auto",
                                    'user_update' => "System Auto"
                                ]);

                                $this->memberCreditLogRepository->create([
                                    'ip' => $ip,
                                    'credit_type' => 'D',
                                    'game_code' => $game->code,
                                    'gameuser_code' => $game_user->code,
                                    'amount' => 0,
                                    'bonus' => $bonus,
                                    'total' => $bonus,
                                    'balance_before' => 0,
                                    'balance_after' => 0,
                                    'credit' => 0,
                                    'credit_bonus' => 0,
                                    'credit_total' => 0,
                                    'credit_before' => 0,
                                    'credit_after' => 0,
                                    'member_code' => $upline_code,
                                    'pro_code' => $chk->code,
                                    'refer_code' => $payment_code,
                                    'refer_table' => 'banks_payment',
                                    'auto' => 'N',
                                    'enable' => 'Y',
                                    'remark' => 'จาก : ' . $user_topup->user_name . ' รายการฝากที่ : ' . $payment_code . ' ' . $promotion['type'],
                                    'kind' => 'FASTSTARTS',
                                    'amount_balance' => $gameuser->amount_balance,
                                    'withdraw_limit' => $gameuser->withdraw_limit,
                                    'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
                                    'user_create' => "System Auto",
                                    'user_update' => "System Auto"
                                ]);

                            }


                        }

                        DB::commit();
                    } catch (Throwable $e) {
                        DB::rollBack();
                        ActivityLoggerUser::activity('FASTSTART REFER ID : ' . $user_topup->user_name, 'ไม่สามารถทำรายการ FASTSTART ให้กับ User : ' . $member->user_name);


                        report($e);
                        return false;
                    }

                    ActivityLogger::activitie('FASTSTART REFER ID : ' . $user_topup->user_name, 'ทำรายการ FASTSTART ให้กับ User : ' . $member->user_name . ' สำเร็จ');

                } else {

                    ActivityLogger::activitie('FASTSTART REFER ID : ' . $user_topup->user_name, 'โบนัสคำนวนได้ ' . $bonus . ' ?? อดโบนัสให้กับ UPLINE CODE : ' . $upline_code);

                }
            } else {
                ActivityLogger::activitie('FASTSTART REFER ID : ' . $user_topup->user_name, 'ไม่ใช่ครั้งแรก !! อดโบนัสให้กับ UPLINE CODE : ' . $upline_code);
            }

        } else {

            ActivityLogger::activitie('FASTSTART REFER ID : ' . $user_topup->user_name, 'จบการทำงาน UPLINE CODE : ' . $upline_code);
        }


        return true;


    }

    public function checkFastStartSeamless($amount, $user_topup_code, $payment_code = 0): bool
    {
        $datenow = now()->toDateTimeString();
        $ip = request()->ip();
        $config = core()->getConfigData();

        $chk = $this->promotionRepository->findOneByField('id', 'pro_faststart');
        if (empty($chk)) {
            return true;
        }
        if ($chk->enable != 'Y' || $chk->active != 'Y') {

            return false;
        }


        $user_topup = $this->memberRepository->find($user_topup_code);
        if (empty($user_topup)) {
            return false;
        }

        $upline_code = $user_topup->upline_code;
        $downline_code = $user_topup_code;
        ActivityLoggerUser::activity('FASTSTART REFER ID : ' . $user_topup->user_name, 'เริ่มรายการ FASTSTART ให้กับ UPLINE CODE : ' . $upline_code);


        if ($upline_code > 0) {
            $cnt = $this->findOneWhere(['member_code' => $upline_code, 'downline_code' => $downline_code, 'pro_code' => $chk->code]);
            if (empty($cnt)) {

                if($user_topup->count_deposit >= 1){
                    ActivityLoggerUser::activity('FASTSTART NORMAL REFER ID : ' . $user_topup->user_name, 'เติมไม่ใช่ครั้งแรก !! ');
                    return false;
                }


                ActivityLoggerUser::activity('FASTSTART REFER ID : ' . $user_topup->user_name, 'เติมครั้งแรก ' . $amount . ' บาท !! กำลังเชคโบนัสให้กับ UPLINE CODE : ' . $upline_code);
                $promotion = $this->promotionRepository->checkPromotionId("pro_faststart", $amount, $datenow);
                $bonus = $promotion['bonus'];
                $total = $promotion['total'];
                $pro_code = $promotion['pro_code'];
                $pro_name = $promotion['pro_name'];
                $turnpro = $promotion['turnpro'];
                $withdraw_limit = $promotion['withdraw_limit'];
                $withdraw_limit_rate = $promotion['withdraw_limit_rate'];


                if ($bonus > 0) {

                    $member = $this->memberRepository->find($upline_code);
                    if (empty($member)) {
                        return false;
                    }

                    $money_text = 'โบนัส ' . $bonus . 'จากโปร ' . $pro_name;

                    ActivityLoggerUser::activity('FASTSTART REFER ID : ' . $user_topup->user_name, 'มอบโบนัส FASTSTART ให้กับ User : ' . $member->user_name);

                    $game = core()->getGame();
                    $game_user = $this->gameUserEventRepository->findOneWhere(['method' => 'FASTSTART', 'member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
                    if (!$game_user) {
                        $game_user = $this->gameUserEventRepository->create([
                            'game_code' => $game->code,
                            'member_code' => $member->code,
                            'pro_code' => $pro_code,
                            'method' => 'FASTSTART',
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

                    $game_code = $game->code;
                    $user_name = $game_user->user_name;
                    $game_name = $game->name;
                    $user_code = $game_user->code;

//                    DB::beginTransaction();
                    try {


                        if ($chk->use_auto == 'Y') {

                            $bill = $this->create([
                                'ip' => $ip,
                                'pro_code' => $chk->code,
                                'amount' => $amount,
                                'credit' => $amount,
                                'credit_bonus' => $bonus,
                                'credit_before' => 0,
                                'credit_after' => 0,
                                'credit_balance' => $total,
                                'member_code' => $upline_code,
                                'downline_code' => $downline_code,
                                'remark' => 'ได้รับค่าแนะนำ จาก : ' . $user_topup->user_name . ' รายการฝากที่ : ' . $payment_code . ' ' . $promotion['type'],
                                'user_create' => "System Auto",
                                'user_update' => "System Auto"
                            ]);

                            $this->memberCreditLogRepository->create([
                                'ip' => $ip,
                                'credit_type' => 'D',
                                'game_code' => $game->code,
                                'gameuser_code' => $game_user->code,
                                'amount' => $bonus,
                                'bonus' => 0,
                                'total' => $bonus,
                                'balance_before' => 0,
                                'balance_after' => 0,
                                'credit' => 0,
                                'credit_bonus' => 0,
                                'credit_total' => 0,
                                'credit_before' => 0,
                                'credit_after' => 0,
                                'member_code' => $upline_code,
                                'pro_code' => $chk->code,
                                'refer_code' => $payment_code,
                                'refer_table' => 'banks_payment',
                                'auto' => 'Y',
                                'remark' => 'จาก : ' . $user_topup->user_name . ' รายการฝากที่ : ' . $payment_code . ' ' . $promotion['type'],
                                'kind' => 'FASTSTART',
                                'amount_balance' => $game_user->amount_balance,
                                'withdraw_limit' => $game_user->withdraw_limit,
                                'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                                'user_create' => "System Auto",
                                'user_update' => "System Auto"
                            ]);


                            $this->memberPromotionLogRepository->create([
                                'date_start' => now()->toDateString(),
                                'bill_code' => $bill->code,
                                'member_code' => $member->code,
                                'game_code' => $game_code,
                                'game_name' => $game_name,
                                'gameuser_code' => $user_code,
                                'pro_code' => $pro_code,
                                'pro_name' => $pro_name,
                                'turnpro' => $turnpro,
                                'balance' => $member->balance,
                                'amount' => 0,
                                'bonus' => $bonus,
                                'amount_balance' => ($bonus * $turnpro),
                                'total_amount_balance' => ((0) + ($bonus * $turnpro)),
                                'withdraw_limit' => $withdraw_limit,
                                'withdraw_limit_rate' => $withdraw_limit_rate,
                                'complete' => 'N',
                                'enable' => 'Y',
                                'user_create' => $member['name'],
                                'user_update' => $member['name']
                            ]);


                            $member->faststart += $bonus;
                            $member->save();

                            $game_user->amount = $member->balance;
                            $game_user->pro_code = $pro_code;
                            $game_user->bill_code = $bill->code;
                            $game_user->turnpro = $turnpro;
                            $game_user->bonus += $bonus;
                            $game_user->amount_balance += ($bonus * $turnpro);
                            $game_user->withdraw_limit += $withdraw_limit;
                            $game_user->withdraw_limit_rate = $withdraw_limit_rate;
                            $game_user->withdraw_limit_amount += ($bonus * $withdraw_limit_rate);
                            $game_user->save();




                        } else {

                            $bill = $this->create([
                                'ip' => $ip,
                                'enable' => 'N',
                                'pro_code' => $chk->code,
                                'amount' => $amount,
                                'credit' => $amount,
                                'credit_bonus' => $bonus,
                                'credit_before' => 0,
                                'credit_after' => 0,
                                'credit_balance' => 0,
                                'member_code' => $upline_code,
                                'downline_code' => $downline_code,
                                'remark' => 'จาก : ' . $user_topup->user_name . ' รายการฝากที่ : ' . $payment_code . ' ' . $promotion['type'],
                                'user_create' => "System Auto",
                                'user_update' => "System Auto"
                            ]);

                            $this->memberCreditLogRepository->create([
                                'ip' => $ip,
                                'credit_type' => 'D',
                                'game_code' => $game->code,
                                'gameuser_code' => $game_user->code,
                                'amount' => 0,
                                'bonus' => $bonus,
                                'total' => $bonus,
                                'balance_before' => 0,
                                'balance_after' => 0,
                                'credit' => 0,
                                'credit_bonus' => 0,
                                'credit_total' => 0,
                                'credit_before' => 0,
                                'credit_after' => 0,
                                'member_code' => $upline_code,
                                'pro_code' => $chk->code,
                                'refer_code' => $payment_code,
                                'refer_table' => 'banks_payment',
                                'auto' => 'N',
                                'enable' => 'Y',
                                'remark' => 'จาก : ' . $user_topup->user_name . ' รายการฝากที่ : ' . $payment_code . ' ' . $promotion['type'],
                                'kind' => 'FASTSTARTS',
                                'amount_balance' => $game_user->amount_balance,
                                'withdraw_limit' => $game_user->withdraw_limit,
                                'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                                'user_create' => "System Auto",
                                'user_update' => "System Auto"
                            ]);

                        }

//                        DB::commit();
                    } catch (Throwable $e) {
//                        DB::rollBack();
                        ActivityLoggerUser::activity('FASTSTART REFER ID : ' . $user_topup->user_name, 'ไม่สามารถทำรายการ FASTSTART ให้กับ User : ' . $member->user_name);


                        report($e);
                        return false;
                    }

                    ActivityLogger::activitie('FASTSTART REFER ID : ' . $user_topup->user_name, 'ทำรายการ FASTSTART ให้กับ User : ' . $member->user_name . ' สำเร็จ');

                } else {

                    ActivityLogger::activitie('FASTSTART REFER ID : ' . $user_topup->user_name, 'โบนัสคำนวนได้ ' . $bonus . ' ?? อดโบนัสให้กับ UPLINE CODE : ' . $upline_code);

                }
            } else {
                ActivityLogger::activitie('FASTSTART REFER ID : ' . $user_topup->user_name, 'ไม่ใช่ครั้งแรก !! อดโบนัสให้กับ UPLINE CODE : ' . $upline_code);
            }

        } else {

            ActivityLogger::activitie('FASTSTART REFER ID : ' . $user_topup->user_name, 'จบการทำงาน UPLINE CODE : ' . $upline_code);
        }


        return true;


    }

    /**
     * Specify Model class name
     *
     * @return string
     */
    function model(): string
    {
        return \Gametech\Payment\Models\PaymentPromotion::class;

    }
}
