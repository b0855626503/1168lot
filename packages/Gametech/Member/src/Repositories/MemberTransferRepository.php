<?php

namespace Gametech\Member\Repositories;

use Gametech\Core\Eloquent\Repository;
use Gametech\Game\Repositories\GameUserRepository;
use Illuminate\Container\Container as App;

class MemberTransferRepository extends Repository
{
    private $memberRepository;

    private $gameUserRepository;

    private $memberCreditLogRepository;

    public function __construct
    (

        MemberRepository          $memberRepo,
        GameUserRepository        $gameUserRepo,
        MemberCreditLogRepository $memberCreditLogRepo,
        App                       $app
    )
    {

        $this->memberRepository = $memberRepo;

        $this->gameUserRepository = $gameUserRepo;

        $this->memberCreditLogRepository = $memberCreditLogRepo;

        parent::__construct($app);
    }

    public function moneyTransfer($id, $to, $amount)
    {
        $response['success'] = false;

        $datenow = now();
        $timenow = $datenow->toTimeString();
        $today = $datenow->toDateString();
        $ip = request()->ip();

        $member = $this->memberRepository->find($id);
        if (!$member) {
            $response['message'] = 'ไม่พบข้อมูลผู้โอน';
            return $response;
        }

        if($member->balance < $amount){
            $response['message'] = 'ยอดเงินที่โอนไม่เพียงพอ โปรดตรวจสอบ';
            return $response;
        }

        $game_user = $this->gameUserRepository->findOneWhere(['member_code' => $id , 'enable' => 'Y']);
        if($game_user->amount_balance > 0){
            $response['message'] = 'ไม่สามารถโอนได้ เนื่องจาก ผู้โอน มียอดเทิร์น ค้างอยู่';
            return $response;
        }



        $to_member = $this->memberRepository->find($to);
        if (!$to_member) {
            $response['message'] = 'ไม่พบข้อมูล ผู้รับโอน';
            return $response;
        }

        $to_game_user = $this->gameUserRepository->findOneWhere(['member_code' => $to , 'enable' => 'Y']);
        if($to_game_user->amount_balance > 0){
            $response['message'] = 'ไม่สามารถโอนได้ เนื่องจาก ผู้รับโอน มียอดเทิร์น ค้างอยู่';
            return $response;
        }

        $oldcredit = $member->balance;
        $aftercredit = ($member->balance - $amount);
        $member->balance -= $amount;
        $member->save();

        $bill = $this->create([
            'member_code' => $member->code,
            'user_name' => $member->user_name,
            'to_member_code' => $to_member->code,
            'to_user_name' => $to_member->user_name,
            'amount' => floor($amount),
            'enable' => 'Y',
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
            'credit' => $amount,
            'credit_bonus' => 0,
            'credit_total' => $amount,
            'credit_before' => $oldcredit,
            'credit_after' => $aftercredit,
            'member_code' => $member->code,
            'user_name' => $member->user_name,
            'game_code' => 0,
            'bank_code' => $member->bank_code,
            'gameuser_code' => 0,
            'auto' => 'N',
            'refer_code' => $bill->code,
            'refer_table' => 'members_transfer',
            'remark' => 'โอนเงิน จำนวน ' . $amount . ' ให้กับ ' . $to_member->user_name,
            'kind' => 'TRAN_USER',
            'user_create' => $member['name'],
            'user_update' => $member['name']
        ]);

        $to_oldcredit = $to_member->balance;
        $to_aftercredit = ($to_member->balance + $amount);
        $to_member->balance += $amount;
        $to_member->save();

        $this->memberCreditLogRepository->create([
            'ip' => $ip,
            'credit_type' => 'D',
            'amount' => $amount,
            'bonus' => 0,
            'total' => $amount,
            'balance_before' => $to_oldcredit,
            'balance_after' => $to_aftercredit,
            'credit' => $amount,
            'credit_bonus' => 0,
            'credit_total' => $amount,
            'credit_before' => $to_oldcredit,
            'credit_after' => $to_aftercredit,
            'member_code' => $to_member->code,
            'user_name' => $to_member->user_name,
            'game_code' => 0,
            'bank_code' => $to_member->bank_code,
            'gameuser_code' => 0,
            'auto' => 'N',
            'refer_code' => $bill->code,
            'refer_table' => 'members_transfer',
            'remark' => 'รับโอนเงิน จำนวน ' . $amount . ' จาก ' . $member->user_name,
            'kind' => 'TRAN_USER',
            'user_create' => $member['name'],
            'user_update' => $member['name']
        ]);

        $response['success'] = true;
        $response['message'] = 'ดำเนินการ โอนเงินสำเร็จแล้ว';
        return $response;



    }


    /**
     * Specify Model class name
     *
     * @return string
     */
    function model(): string
    {
        return \Gametech\Member\Models\MemberTransfer::class;

    }


}
