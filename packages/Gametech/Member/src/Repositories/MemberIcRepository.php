<?php

namespace Gametech\Member\Repositories;

use Gametech\Core\Eloquent\Repository;
use Gametech\Game\Repositories\GameUserEventRepository;
use Gametech\LogAdmin\Http\Traits\ActivityLogger;
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

    public function __construct
    (
        MemberRepository           $memberRepo,
        MemberFreeCreditRepository $memberFreeCreditRepo,
        GameUserEventRepository    $gameUserEventRepo,
        MemberCreditFreeLogRepository $memberCreditFreeLogRepo,
        MemberCreditLogRepository $memberCreditLogRepo,
        App                        $app
    )
    {
        $this->memberRepository = $memberRepo;
        $this->memberFreeCreditRepository = $memberFreeCreditRepo;
        $this->gameUserEventRepository = $gameUserEventRepo;
        $this->memberCreditFreeLogRepository = $memberCreditFreeLogRepo;
        $this->memberCreditLogRepository = $memberCreditLogRepo;
        parent::__construct($app);
    }

    /**
     * Specify Model class name
     *
     * @return string
     */
    function model(): string
    {
        return \Gametech\Member\Models\MemberIc::class;

    }

    public function refill(array $data): bool
    {
        $config = core()->getConfigData();
        $code = ($data['code'] ?? 0);
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
        if (!$member) {
            return false;
        }


        ActivityLogger::activitie('IC REFER USER : ' . $member->user_name, 'เริ่มรายการ IC');


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
                    'user_update' => $emp_name
                ]);
                $code = $bill->code;
            }

            if ($config->seamless == 'Y') {

                $game = core()->getGame();
                $game_user = $this->gameUserEventRepository->findOneWhere(['method' => 'IC', 'member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
                if (!$game_user) {
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
                    'user_create' => "System Auto",
                    'user_update' => "System Auto"
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
                    'user_create' => "System Auto",
                    'user_update' => "System Auto"
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
                    'remark' => "เพิ่ม IC อ้างอิง record : " . $code,
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
            ActivityLogger::activitie('IC REFER USER : ' . $member->user_name, 'พบข้อผิดพลาด IC');

            report($e);
            return false;
        }

        ActivityLogger::activitie('IC REFER USER : ' . $member->user_name, 'ทำรายการ IC สำเร็จ');


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
        if (!$member) {
            return false;
        }

        $total = ($member->balance_free - $cashback);

        ActivityLogger::activitie('IC REFER USER : ' . $member->user_name, 'เริ่มรายการ ลบ IC');


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
                    'user_update' => $emp_name
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
                'remark' => "ลบ IC อ้างอิง record : " . $code,
                'emp_code' => $emp_code,
                'user_create' => $emp_name,
                'user_update' => $emp_name,
            ]);

            $member->balance_free -= $cashback;
            $member->save();
            DB::commit();


        } catch (Throwable $e) {
            DB::rollBack();
            ActivityLogger::activitie('IC REFER USER : ' . $member->user_name, 'พบข้อผิดพลาด IC');

            report($e);
            return false;
        }

        ActivityLogger::activitie('IC REFER USER : ' . $member->user_name, 'ทำรายการ IC สำเร็จ');


        return true;
    }

    public function refillSeamless(array $data): bool
    {
        $config = core()->getConfigData();
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
        if (!$member) {
            return false;
        }

        if($config->freecredit_open == 'Y'){
            $total = ($member->balance_free + $cashback);
        }else{
            $total = ($member->balance + $cashback);
        }

        ActivityLogger::activitie('IC REFER USER : ' . $member->user_name, 'เริ่มรายการ IC');


//        DB::beginTransaction();
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
                    'user_update' => $emp_name
                ]);
                $code = $bill->code;
            }

            if ($config->seamless == 'Y') {

                $game = core()->getGame();
                $game_user = $this->gameUserEventRepository->findOneWhere(['method' => 'IC', 'member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
                if (!$game_user) {
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
                if($config->freecredit_open == 'Y') {
                    $game_user->amount = $member->balance_free;
                }else{
                    $game_user->amount = $member->balance;
                }
                $game_user->pro_code = $pro_code;
                $game_user->bill_code = $code;
                $game_user->turnpro = $turnpro;
                $game_user->bonus += $cashback;
                $game_user->amount_balance += ($cashback * $turnpro);
                $game_user->withdraw_limit += $withdraw_limit;
                $game_user->withdraw_limit_rate = $withdraw_limit_rate;
                $game_user->withdraw_limit_amount += ($cashback * $withdraw_limit_rate);
                $game_user->save();

                $member->ic += $cashback;
                $member->save();

                if($config->freecredit_open == 'Y') {
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
                        'remark' => 'ได้รับ IC จากการคำนวนประจำวัน',
                        'kind' => 'IC',
                        'amount_balance' => $game_user->amount_balance,
                        'withdraw_limit' => $game_user->withdraw_limit,
                        'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                        'user_create' => "System Auto",
                        'user_update' => "System Auto"
                    ]);

                }else{

                    $this->memberCreditLogRepository->create([
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
                        'remark' => 'ได้รับ IC จากการคำนวนประจำวัน',
                        'kind' => 'IC',
                        'amount_balance' => $game_user->amount_balance,
                        'withdraw_limit' => $game_user->withdraw_limit,
                        'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                        'user_create' => "System Auto",
                        'user_update' => "System Auto"
                    ]);

                }

            } else {
                $this->memberFreeCreditRepository->create([
                    'ip' => $ip,
                    'credit_type' => 'D',
                    'credit' => $cashback,
                    'credit_amount' => $cashback,
                    'credit_before' => $member->balance_free,
                    'credit_balance' => $total,
                    'member_code' => $member_code,
                    'kind' => 'IC',
                    'remark' => "เติม IC อ้างอิง record : " . $code,
                    'emp_code' => $emp_code,
                    'user_create' => $emp_name,
                    'user_update' => $emp_name,
                ]);

                $member->balance_free += $cashback;
                $member->save();
            }


//            DB::commit();


        } catch (Throwable $e) {
//            DB::rollBack();
            ActivityLogger::activitie('IC REFER USER : ' . $member->user_name, 'พบข้อผิดพลาด IC');

            report($e);
            return false;
        }

        ActivityLogger::activitie('IC REFER USER : ' . $member->user_name, 'ทำรายการ IC สำเร็จ');


        return true;
    }
}
