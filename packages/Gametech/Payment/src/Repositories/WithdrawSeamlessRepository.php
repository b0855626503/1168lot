<?php

namespace Gametech\Payment\Repositories;

use App\Events\RealTimeMessage;
use Gametech\Core\Eloquent\Repository;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser;
use Gametech\Member\Repositories\MemberCreditLogRepository;
use Gametech\Member\Repositories\MemberLogRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class WithdrawSeamlessRepository extends Repository
{
    private $memberRepository;

    private $memberLogRepository;

    private $memberCreditLogRepository;

    private $gameUserRepository;

    private $withdrawDetailRepository;

    private $bankPaymentRepository;

    /**
     * WithdrawRepository constructor.
     * @param MemberLogRepository $memberLogRepo
     * @param MemberRepository $memberRepo
     * @param MemberCreditLogRepository $memberCreditLogRepo
     * @param GameUserRepository $gameUserRepo
     * @param App $app
     */
    public function __construct
    (
        MemberLogRepository       $memberLogRepo,
        MemberRepository          $memberRepo,
        MemberCreditLogRepository $memberCreditLogRepo,
        GameUserRepository        $gameUserRepo,
        WithdrawDetailRepository  $withdrawDetailRepo,
        BankPaymentRepository        $bankPaymentRepo,
        App                       $app
    )
    {
        $this->memberLogRepository = $memberLogRepo;

        $this->memberRepository = $memberRepo;

        $this->memberCreditLogRepository = $memberCreditLogRepo;

        $this->gameUserRepository = $gameUserRepo;

        $this->withdrawDetailRepository = $withdrawDetailRepo;

        $this->bankPaymentRepository = $bankPaymentRepo;

        parent::__construct($app);
    }

    /**
     * @param $id
     * @param $amount
     * @return bool
     */

    public function withdraw($id, $amount): bool
    {

        $datenow = now();
        $timenow = $datenow->toTimeString();
        $today = $datenow->toDateString();
        $ip = request()->ip();

        $member = $this->memberRepository->find($id);
        if (!$member) {
            return false;
        }


        if ($member->balance < $amount) {
            ActivityLoggerUser::activity('Request Withdraw Wallet User : ' . $member->user_name, 'ยอดแจ้งถอน มากกว่า ยอดที่มี');
            return false;
        }

        $oldcredit = $member->balance;
        $aftercredit = ($oldcredit - $amount);


        ActivityLoggerUser::activity('Request Withdraw Wallet User : ' . $member->user_name, 'เริ่มต้นทำรายการแจ้งถอน จำนวน '.$amount.' จากทั้งหมด '.$member->balance);

        DB::beginTransaction();

        try {


            $data = [
                'member_code' => $id,
                'amount' => $amount,
                'oldcredit' => $oldcredit,
                'aftercredit' => $aftercredit,
                'ip' => $ip
            ];


//            $this->memberLogRepository->create([
//                'member_code' => $member->code,
//                'mode' => 'WITHDRAW',
//                'menu' => 'withdraw',
//                'record' => $member->code,
//                'remark' => 'ถอนเงินจาก กระเป๋า Wallet',
//                'item_before' => serialize($member),
//                'item' => serialize($data),
//                'ip' => $ip,
//                'user_create' => $member->name
//            ]);

            $chk = $this->findOneWhere(['member_code' => $member->code, 'amount' => $amount, 'status' => 0, 'date_record' => $today, 'timedept' => $timenow]);
            if ($chk) {
                DB::rollBack();
                return false;
            }

            $member->balance -= $amount;
            $member->ip = $ip;
            $member->save();

            $bill = $this->create([
                'member_code' => $member->code,
                'member_user' => $member->user_name,
                'bankm_code' => $member->bank_code,
                'amount' => $amount,
                'oldcredit' => $oldcredit,
                'aftercredit' => $aftercredit,
                'status' => 0,
                'date_record' => $today,
                'bankout' => '',
                'remark' => '',
                'timedept' => $timenow,
                'ip' => $ip,
                'user_create' => $member->name,
                'user_update' => $member->name
            ]);

            $this->memberCreditLogRepository->create([
                'ip' => $ip,
                'credit_type' => 'W',
                'amount' => $amount,
                'bonus' => 0,
                'total' => $amount,
                'balance_before' => $oldcredit,
                'balance_after' => $aftercredit,
                'credit' => 0,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => 0,
                'credit_after' => 0,
                'member_code' => $member->code,
                'game_code' => 0,
                'bank_code' => $member->bank_code,
                'gameuser_code' => 0,
                'auto' => 'N',
                'refer_code' => $bill->code,
                'refer_table' => 'withdraws',
                'remark' => "ทำรายการถอนเงิน อ้างอิงบิล ID :" . $bill->code . ' ยอดก่อนถอน ' . $member->balance . ' แจ้งถอน ' . $amount . ' คงเหลือ ' . number_format($aftercredit),
                'kind' => 'WITHDRAW',
                'user_create' => $member['name'],
                'user_update' => $member['name']
            ]);



            DB::commit();

        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Request Withdraw Wallet User : ' . $member->user_name, 'พบปัญหาในการทำรายการ');
            DB::rollBack();
            ActivityLoggerUser::activity('Request Withdraw Wallet User : ' . $member->user_name, 'ดำเนินการ Rollback แล้ว');

            report($e);
            return false;
        }


        ActivityLoggerUser::activity('Request Withdraw Wallet User : ' . $member->user_name, 'ทำรายการแจ้งถอนสำเร็จแล้ว');
        return true;

    }

    public function withdrawSingle($id, $amount)
    {
        $response['success'] = false;


        $datenow = now();
        $timenow = $datenow->toTimeString();
        $today = $datenow->toDateString();
        $ip = request()->ip();

        $member = $this->memberRepository->find($id);

        ActivityLoggerUser::activity('ทำรายการแจ้งถอนเกมเครดิต จาก : ' . $member->user_name, 'เตรียมการทำรายการแจ้งถอน จำนวน ' . $amount . ' ยอดเครดิตที่มี ' . $member->balance);


        if ($member->balance < $amount) {
            $response['msg'] = 'ยอดเงินที่ระบุ มากกว่าที่มีอยู่';
            ActivityLoggerUser::activity('ทำรายการแจ้งถอนเกมเครดิต จาก : ' . $member->user_name, 'ยอดแจ้งถอน มากกว่า ยอดที่มี');
            return $response;
        }


        $game = core()->getGame();
        $game_user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'game_code' => $game->code, 'enable' => 'Y']);
        $game_code = $game->code;
        $user_name = $game_user->user_name;
        $user_code = $game_user->code;
        $game_name = $game->name;
        $game_balance = $game_user->balance;
        $member_code = $member->code;

        $response = $this->gameUserRepository->UserWithdraw($game_code, $user_name, $amount, false);
        if ($response['success'] === true) {
            ActivityLoggerUser::activity('ถอนเงินจากเกม ' . $game_name . ' ของ ID : ' . $user_name, 'จำนวนเงิน ' . $amount . ' ระบบทำการถอนเงินออกจากเกมแล้ว');
        } else {
            ActivityLoggerUser::activity('ถอนเงินจากเกม ' . $game_name . ' ของ ID : ' . $user_name, 'จำนวนเงิน ' . $amount . ' ไม่สามารถถอนเงินออกจากเกมได้');
            return $response;
        }


        ActivityLoggerUser::activity('ทำรายการแจ้งถอนเกมเครดิต จาก : ' . $member->user_name, 'เริ่มต้นทำรายการแจ้งถอน จำนวน '.$amount.' จากทั้งหมด '.$game_balance);

        DB::beginTransaction();

        try {

            $member->balance = $response['after'];
            $member->ip = $ip;
            $member->save();

            $game_user->balance = $response['after'];
            $game_user->save();

            $bill = $this->create([
                'member_code' => $member->code,
                'member_user' => $member->user_name,
                'bankm_code' => $member->bank_code,
                'amount' => $amount,
                'oldcredit' => $response['before'],
                'aftercredit' => $response['after'],
                'status' => 0,
                'date_record' => $today,
                'bankout' => '',
                'remark' => '',
                'timedept' => $timenow,
                'ip' => $ip,
                'user_create' => $member->name,
                'user_update' => $member->name
            ]);

            $this->memberCreditLogRepository->create([
                'ip' => $ip,
                'credit_type' => 'W',
                'amount' => $amount,
                'bonus' => 0,
                'total' => $amount,
                'balance_before' => 0,
                'balance_after' => 0,
                'credit' => $amount,
                'credit_bonus' => 0,
                'credit_total' => $amount,
                'credit_before' => $response['before'],
                'credit_after' => $response['after'],
                'member_code' => $member->code,
                'game_code' => $game_code,
                'bank_code' => $member->bank_code,
                'gameuser_code' => $user_code,
                'auto' => 'N',
                'refer_code' => $bill->code,
                'refer_table' => 'withdraws',
                'remark' => "ทำรายการถอนเงิน อ้างอิงบิล ID :" . $bill->code,
                'kind' => 'WITHDRAW',
                'user_create' => $member['name'],
                'user_update' => $member['name']
            ]);


            DB::commit();


        } catch (Throwable $e) {
            ActivityLoggerUser::activity('ทำรายการแจ้งถอนเกมเครดิต จาก : ' . $member->user_name, 'พบปัญหาในการทำรายการ');
            DB::rollBack();
            ActivityLoggerUser::activity('ทำรายการแจ้งถอนเกมเครดิต จาก : ' . $member->user_name, 'ดำเนินการ Rollback แล้ว');

            $response = $this->gameUserRepository->UserDeposit($game_code, $user_name, $amount);
            if ($response['success'] === true) {
                ActivityLoggerUser::activity('ถอนเงินจากเกม ' . $game_name . ' ของ ID : ' . $user_name, 'จำนวนเงิน ' . $amount . ' ฝากเงินกลับเข้าเกม เรียบร้อย');
            } else {
                ActivityLoggerUser::activity('ถอนเงินจากเกม ' . $game_name . ' ของ ID : ' . $user_name, 'จำนวนเงิน ' . $amount . ' ไม่สามารถ ฝากเงินเข้าเกมได้');
            }
            report($e);
            return $response;
        }


        ActivityLoggerUser::activity('ทำรายการแจ้งถอนเกมเครดิต จาก : ' . $member->user_name, 'ทำรายการแจ้งถอนสำเร็จแล้ว');
        return $response;

    }

    public function withdrawSeamless($id, $amount)
    {

        $result['success'] = false;
        $result['msg'] = Lang::get('app.withdraw.fail');

        $datenow = now();
        $timenow = $datenow->toTimeString();
        $today = $datenow->toDateString();
        $ip = request()->ip();
        $amount = floor($amount);
        $baseamount = $amount;
        $member = $this->memberRepository->find($id);
        if (!$member) {
            $result['msg'] = Lang::get('app.withdraw.nomember');
            return $result;
        }

//        $play = GameLogProxy::whereIn('method', ['bet','withdraw'])
//            ->where('response', 'in')
//            ->where('game_user', $member->user_name)
//            ->first();
//
//        if(!$play){
//            $result['msg'] = 'พบข้อผิดพลาด ต้องมีประวัติเล่นเกม ก่อนถึงแจ้งถอนได้';
//            return $result;
//        }

        $gameuser = $this->gameUserRepository->findOneWhere(['member_code' => $member->code]);

        $pro_code = $gameuser->pro_code;

        if($gameuser->amount_balance > 0){
            if($baseamount != floor($member->balance)){
                ActivityLoggerUser::activity('Request Withdraw Wallet User : ' . $member->user_name, 'รับโปร บังคับถอนหมด');
                $result['msg'] = Lang::get('app.withdraw.pro');
                return $result;
            }

        }

        if ($member->balance < $gameuser->amount_balance) {
            ActivityLoggerUser::activity('Request Withdraw Wallet User : ' . $member->user_name, 'ยอดเครดิต ยังไม่ผ่าน ยอดเทิน');
            $result['msg'] = Lang::get('app.withdraw.credit_notpass');
            return $result;
        }

        if ($gameuser->amount_balance > 0) {


            if ($amount < $gameuser->amount_balance) {
                ActivityLoggerUser::activity('Request Withdraw Wallet User : ' . $member->user_name, 'ยอดแจ้งถอน ต้องมากกว่า ยอดเทิน');
                $result['msg'] = Lang::get('app.withdraw.credit_wrong');
                return $result;
            }

            if ($amount != floor($member->balance)) {
                ActivityLoggerUser::activity('Request Withdraw Wallet User : ' . $member->user_name, 'แจ้งถอนไม่หมด ยอดแจ้ง '.$amount.' ยอดทั้้งหมด '.$member->balance);
                $result['msg'] = Lang::get('app.withdraw.credit_wrong');
                return $result;
            }

            if ($gameuser->withdraw_limit_amount > 0) {
                if ($amount > $gameuser->withdraw_limit_amount) {
                    $amount = $gameuser->withdraw_limit_amount;
                }
            }

//            if($gameuser->withdraw_limit > 0){
//                    $amount = $gameuser->withdraw_limit;
//            }

        } else {
            if (floor($member->balance) < $baseamount) {
                ActivityLoggerUser::activity('Request Withdraw Wallet User : ' . $member->user_name, 'ยอดแจ้งถอน มากกว่า ยอดที่มี');
                $result['msg'] = Lang::get('app.withdraw.credit_over');
                return $result;
            }
        }


        $oldcredit = $member->balance;
        $aftercredit = ($oldcredit - $baseamount);


        ActivityLoggerUser::activity('Request Withdraw Wallet User : ' . $member->user_name, 'เริ่มต้นทำรายการแจ้งถอน จำนวน '.$baseamount.' จากทั้งหมด '.$member->balance);

        DB::beginTransaction();

        try {


            $data = [
                'member_code' => $id,
                'amount' => $amount,
                'oldcredit' => $oldcredit,
                'aftercredit' => $aftercredit,
                'ip' => $ip
            ];


//            $this->memberLogRepository->create([
//                'member_code' => $member->code,
//                'mode' => 'WITHDRAW',
//                'menu' => 'withdraw',
//                'record' => $member->code,
//                'remark' => 'ถอนเงินจาก กระเป๋า Wallet',
//                'item_before' => serialize($member),
//                'item' => serialize($data),
//                'ip' => $ip,
//                'user_create' => $member->name
//            ]);

            $chk = $this->findOneWhere(['member_code' => $member->code, 'amount' => $amount, 'status' => 0, 'date_record' => $today, 'timedept' => $timenow]);
            if ($chk) {
                DB::rollBack();
                $result['msg'] = Lang::get('app.withdraw.dup');
                return $result;
            }

//            if ($member->status_pro == 0) {
//                $member->status_pro = 1;
//            }

            $member->balance -= $baseamount;
            $member->ip = $ip;
            $member->save();

            $bill = $this->create([
                'member_code' => $member->code,
                'member_user' => $member->user_name,
                'bankm_code' => $member->bank_code,
                'amount' => floor($amount),
                'balance' => $baseamount,
                'amount_balance' => $gameuser->amount_balance,
                'amount_limit' => $gameuser->withdraw_limit,
                'amount_limit_rate' => $gameuser->withdraw_limit_amount,
                'oldcredit' => $oldcredit,
                'aftercredit' => $aftercredit,
                'status' => 0,
                'date_record' => $today,
                'bankout' => '',
                'remark' => '',
                'timedept' => $timenow,
                'ip' => $ip,
                'user_create' => $member->name,
                'user_update' => $member->name
            ]);


            $this->memberCreditLogRepository->create([
                'ip' => $ip,
                'credit_type' => 'W',
                'amount' => $baseamount,
                'bonus' => 0,
                'total' => $baseamount,
                'balance_before' => $oldcredit,
                'balance_after' => $aftercredit,
                'credit' => 0,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => $oldcredit,
                'credit_after' => $aftercredit,
                'member_code' => $member->code,
                'game_code' => 0,
                'pro_code' => $pro_code,
                'bank_code' => $member->bank_code,
                'gameuser_code' => 0,
                'auto' => 'N',
                'refer_code' => $bill->code,
                'refer_table' => 'withdraws',
//                'remark' => 'แจ้งถอน (ยอดก่อนถอน ' . $member->balance . ' แจ้งถอน ' . $amount . ' คงเหลือ ' . $aftercredit.') รายการถอนที่ : '.$bill->code,
                'remark' => 'รายการถอนที่ : ' . $bill->code . ' ยอดที่จะได้รับเมื่ออนุมัติ ' . floor($amount) . ' บาท',
                'kind' => 'WITHDRAW',
                'amount_balance' => $gameuser->amount_balance,
                'withdraw_limit' => $gameuser->withdraw_limit,
                'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
                'user_create' => $member['name'],
                'user_update' => $member['name']
            ]);

            $this->bankPaymentRepository->where('member_topup', $member->code)->where('pro_check', 'N')->update([
                'pro_check' => 'Y',
                'user_update' => $member['name']
            ]);

//            $this->withdrawDetailRepository->create([
//                'withdraw_code' => $bill->code,
//                'game_code' => $gameuser->game_code,
//                'member_code' => $gameuser->member_code,
//                'user_name' => $gameuser->user_name,
//                'user_pass' => $gameuser->user_pass,
//                'balance' => $gameuser->balance,
//                'enable' => $gameuser->enable,
//                'bill_code' => $gameuser->bill_code,
//                'pro_code' => $gameuser->pro_code,
//                'amount' => $gameuser->amount,
//                'bonus' => $gameuser->bonus,
//                'turnpro' => $gameuser->turnpro,
//                'amount_balance' => $gameuser->amount_balance,
//                'withdraw_limit' => $gameuser->withdraw_limit,
//                'withdraw_limit_rate' => $gameuser->withdraw_limit_rate,
//                'withdraw_limit_amount' => $gameuser->withdraw_limit_amount,
//                'user_create' => $gameuser->user_create,
//                'user_update' => $gameuser->user_update
//            ]);


//            $gameuser->balance -= $baseamount;
            if ($gameuser->amount_balance > 0) {
                $gameuser->bill_code = 0;
                $gameuser->pro_code = 0;
                $gameuser->bonus = 0;
                $gameuser->amount = 0;
                $gameuser->turnpro = 0;
                $gameuser->amount_balance = 0;
                $gameuser->withdraw_limit = 0;
                $gameuser->withdraw_limit_rate = 0;
                $gameuser->withdraw_limit_amount = 0;
                $gameuser->save();
            }


            DB::commit();

        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Request Withdraw Wallet User : ' . $member->user_name, 'พบปัญหาในการทำรายการ');
            DB::rollBack();
            ActivityLoggerUser::activity('Request Withdraw Wallet User : ' . $member->user_name, 'ดำเนินการ Rollback แล้ว');

            report($e);
            $result['msg'] = Lang::get('app.withdraw.fail');
            return $result;

        }


        ActivityLoggerUser::activity('Request Withdraw Wallet User : ' . $member->user_name, 'ทำรายการแจ้งถอนสำเร็จแล้ว');
        broadcast(new RealTimeMessage('มีรายการแจ้งถอนใหม่ จาก '.$member->user_name));

        $result['success'] = true;
        $result['msg'] = Lang::get('app.withdraw.complete');
        return $result;

    }


    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model(): string
    {
        return \Gametech\Payment\Models\WithdrawSeamless::class;

    }
}
