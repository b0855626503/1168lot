<?php

namespace Gametech\Payment\Repositories;

use Gametech\Core\Eloquent\Repository;
use Gametech\Game\Repositories\GameUserFreeRepository;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser;
use Gametech\Member\Repositories\MemberLogRepository;
use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Throwable;

class BillFreeRepository extends Repository
{
    private $gameUserFreeRepository;

    private $memberRepository;

    private $paymentLogFreeRepository;

    private $memberLogRepository;

    /**
     * BillRepository constructor.
     * @param GameUserFreeRepository $gameUserFreeRepo
     * @param MemberRepository $memberRepo
     * @param PaymentLogFreeRepository $paymentLogFreeRepo
     * @param MemberLogRepository $memberLogRepo
     * @param App $app
     */
    public function __construct
    (
        GameUserFreeRepository $gameUserFreeRepo,
        MemberRepository $memberRepo,
        PaymentLogFreeRepository $paymentLogFreeRepo,
        MemberLogRepository $memberLogRepo,
        App $app
    )
    {

        $this->gameUserFreeRepository = $gameUserFreeRepo;

        $this->memberRepository = $memberRepo;

        $this->memberLogRepository = $memberLogRepo;

        $this->paymentLogFreeRepository = $paymentLogFreeRepo;

        parent::__construct($app);
    }


    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model(): string
    {
        return \Gametech\Payment\Models\BillFree::class;

    }

    public function transferWallet(array $data): array
    {
        $return['success'] = false;

        $ip = request()->ip();

        $member_code = $data['member_code'];
        $game_code = $data['game_code'];
        $user_code = $data['user_code'];
        $user_name = $data['user_name'];
        $pro_code = $data['pro_code'];
        $amount = $data['amount'];
        $bonus = $data['bonus'];
        $total = $amount;
        $game_balance = $data['game_balance'];

        $gameuser = $this->gameUserFreeRepository->getOneUserNew($user_code,$game_code);
        if($gameuser['success'] == false){
            ActivityLoggerUser::activity('Transfer Game To Cashback', 'อัพเดทยอดเงินไม่ได้');
            $return['msg'] = 'อัพเดทยอดเงินในเกมไม่ได้';
            return $return;
        }

//        $user = $gameuser['data'];

        $user = $this->gameUserFreeRepository->find($user_code);
        if(!$user){
            $return['msg'] = 'ไม่พบข้อมูล ID เกมนี้';
            return $return;
        }

        if (($user->balance - $amount) < 0 || $user->balance != $game_balance) {
            ActivityLoggerUser::activity('Transfer Game To Cashback', 'พบปัญหายอดเงินในการทำรายการไม่ถูกต้อง');
            $return['msg'] = 'ยอดเงินที่ทำรายการ ไม่ถูกต้อง โปรดทำรายการใหม่ อีกครั้งในภายหลัง';
            return $return;
        }

        ActivityLoggerUser::activity('Transfer Game To Cashback', 'เริ่มต้นทำรายการโยกเงิน');

        $response = $this->gameUserFreeRepository->UserWithdraw($game_code, $user_name, $total, false);
        if ($response['success'] !== true) {
            ActivityLoggerUser::activity('Transfer Game To Cashback', 'ไม่สามารถถอนเงินออกจากเกมได้');
            $return['msg'] = 'ไม่สามารถ ทำรายการโยกเงินออกจากเกมได้';
            return $return;
        }

        ActivityLoggerUser::activity('Transfer Game To Cashback', 'ระบบทำการถอนเงินออกจากเกมแล้ว');


        DB::beginTransaction();
        try {

            $member = $this->memberRepository->find($member_code);
            $balance_before = $member->balance_free;
            $balance_after = ($balance_before + $amount);

            $bill = $this->create([
                'enable' => 'Y',
                'ref_id' => $response['ref_id'],
                'credit_before' => $response['before'],
                'credit_after' => $response['after'],
                'member_code' => $member_code,
                'game_code' => $game_code,
                'pro_code' => $pro_code,
                'transfer_type' => 2,
                'amount' => $amount,
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
                'credit' => $amount,
                'credit_bonus' => $bonus,
                'credit_balance' => $total,
                'ip' => $ip,
                'user_create' => $member['name'],
                'user_update' => $member['name']
            ]);


            $this->paymentLogFreeRepository->create([
                'msg' => 'โยกเงินออกจากเกม เข้า Cashback เรียบร้อย',
                'status' => 'COMPLETE',
                'showmsg' => 'Y',
                'confirm' => 'Y',
                'enable' => 'Y',
                'bill_code' => $bill->code,
                'member_code' => $member_code,
                'game_code' => $game_code,
                'token' => '',
                'transfer_type' => 2,
                'amount' => $amount,
                'ip' => $ip,
                'user_create' => $member['name'],
                'user_update' => $member['name']
            ]);


            $this->gameUserFreeRepository->update([
                'balance' => $response['after']
            ], $user_code);

            $member->balance_free = ($member->balance_free + $amount);
            $member->save();

            DB::commit();

        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Confirm Transfer Game To Cashback', 'พบปัญหาในการทำรายการ');
            DB::rollBack();
            ActivityLoggerUser::activity('Reject Transfer Game To Cashback', 'ดำเนินการ Rollback การทำรายการแล้ว');
            $response = $this->gameUserFreeRepository->UserDeposit($game_code, $user_name, $total);
            if ($response['success'] === true) {
                ActivityLoggerUser::activity('Transfer Game To Cashback', 'ระบบทำการคืนยอดเงินเข้าเกมแล้ว');
            } else {
                ActivityLoggerUser::activity('Transfer Game To Cashback', 'ระบบไม่สามารถคืนยอดเงินเข้าเกม');
            }


            $return['msg'] = 'ไม่สามารถทำรายการได้ โปรดลองใหม่อีกครั้ง';
            report($e);
            return $return;
        }


        ActivityLoggerUser::activity('Transfer Game To Cashback', 'ทำรายการโยกเงินสำเร็จ');

        $return['success'] = true;
        $return['data'] = $bill;
        return $return;

    }

    public function transferGame(array $data): array
    {
        $return['success'] = false;

        $ip = request()->ip();

        $member_code = $data['member_code'];
        $game_code = $data['game_code'];
        $user_code = $data['user_code'];
        $user_name = $data['user_name'];
        $pro_code = $data['pro_code'];
        $amount = $data['amount'];
        $bonus = $data['bonus'];
        $total = $data['total'];
        $balance_before = $data['member_balance'];
        $balance_after = ($balance_before - $amount);

        $member = $this->memberRepository->find($member_code);

        if (($member->balance_free - $amount) < 0 || $member->balance_free != $balance_before) {
            ActivityLoggerUser::activity('Transfer Cashback To Game', 'พบปัญหายอดเงินในการทำรายการไม่ถูกต้อง');
            $return['msg'] = 'ยอด Cashback คงเหลือไม่ถูกต้อง';
            return $return;
        }

        ActivityLoggerUser::activity('Transfer Cashback To Game', 'เริ่มต้นทำรายการโยกเงิน');


        $response = $this->gameUserFreeRepository->UserDeposit($game_code, $user_name, $total, false);
        if ($response['success'] !== true) {
            ActivityLoggerUser::activity('Transfer Cashback To Game', 'ไม่สามารถฝากเงินเข้าเกมได้');
            $return['msg'] = 'ไม่สามารถ ทำรายการโยกเงินเข้าเกมได้';
            return $return;
        }

        ActivityLoggerUser::activity('Transfer Cashback To Game', 'ระบบทำการฝากเงินเข้าเกมแล้ว');


        DB::beginTransaction();

        try {

            $member = $this->memberRepository->find($member_code);

            $bill = $this->create([
                'enable' => 'Y',
                'ref_id' => $response['ref_id'],
                'credit_before' => $response['before'],
                'credit_after' => $response['after'],
                'member_code' => $member_code,
                'game_code' => $game_code,
                'pro_code' => $pro_code,
                'transfer_type' => 1,
                'amount' => $amount,
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
                'credit' => $amount,
                'credit_bonus' => $bonus,
                'credit_balance' => $total,
                'ip' => $ip,
                'user_create' => $member['name'],
                'user_update' => $member['name']
            ]);


            $this->paymentLogFreeRepository->create([
                'msg' => 'โยกเงินออกจาก Cashback เข้าเกม เรียบร้อย',
                'status' => 'COMPLETE',
                'showmsg' => 'Y',
                'confirm' => 'Y',
                'enable' => 'Y',
                'bill_code' => $bill->code,
                'member_code' => $member_code,
                'game_code' => $game_code,
                'token' => '',
                'transfer_type' => 1,
                'amount' => $amount,
                'ip' => $ip,
                'user_create' => $member['name'],
                'user_update' => $member['name']
            ]);


            $this->gameUserFreeRepository->update([
                'balance' => $response['after']
            ], $user_code);


            $member->balance_free = $balance_after;
            $member->save();

            DB::commit();

        } catch (Throwable $e) {
            ActivityLoggerUser::activity('Confirm Transfer Cashback To Game', 'พบปัญหาในการทำรายการ');
            DB::rollBack();
            ActivityLoggerUser::activity('Reject Transfer Cashback To Game', 'ดำเนินการ Rollback การทำรายการแล้ว');

            $response = $this->gameUserFreeRepository->UserWithdraw($game_code, $user_name, $total);
            if ($response['success'] === true) {
                ActivityLoggerUser::activity('Transfer Cashback To Game', 'ระบบทำการถอนเงินออกจากเกมแล้ว');
            } else {
                ActivityLoggerUser::activity('Transfer Cashback To Game', 'ระบบไม่สามารถถอนเงินออกจากเกมได้');
            }

            report($e);
            $return['msg'] = 'ไม่สามารถทำรายการได้ โปรดลองใหม่อีกครั้ง';
            return $return;
        }


        ActivityLoggerUser::activity('Transfer Cashback To Game', 'ทำรายการโยกเงินสำเร็จ');

        $return['success'] = true;
        $return['data'] = $bill;
        return $return;

    }
}
