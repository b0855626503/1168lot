<?php

namespace Gametech\Member\Repositories;

use Gametech\Core\Eloquent\Repository;
use Gametech\Core\Repositories\RewardRepository;
use Gametech\Member\Contracts\MemberRewardLog;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Throwable;

class MemberRewardLogRepository extends Repository
{
    private $memberRepository;

    private $rewardRepository;

    private $memberPointLogRepository;

    public function __construct
    (
        MemberRepository $memberRepo,
        MemberPointLogRepository $memberPointLogRepo,
        RewardRepository $rewardRepo,
        App $app
    )
    {
        $this->memberRepository = $memberRepo;
        $this->memberPointLogRepository = $memberPointLogRepo;
        $this->rewardRepository = $rewardRepo;
        parent::__construct($app);
    }

    /**
     * Specify Model class name
     *
     * @return string
     */
    function model(): string
    {
        return \Gametech\Member\Models\MemberRewardLog::class;

    }

    public function exchangeReward($id, $user): array
    {
        $result['success'] = false;
        $result['message'] = '';

        $ip = request()->ip();

        $member = $this->memberRepository->find($user->code);
        $reward = $this->rewardRepository->find($id);

        if ($member->point_deposit < $reward->points) {
            $result['message'] = 'Point ไม่เพียงพอ';
            return $result;
        }

        $total = ($member->point_deposit - $reward->points);

        DB::beginTransaction();
        try {

            $bill = $this->create([
                'member_code' => $member->code,
                'point' => $reward->points,
                'point_amount' => $member->point_deposit,
                'point_before' => $member->point_deposit,
                'point_balance' => $total,
                'ip' => $ip,
                'user_create' => $member->name,
                'user_update' => $member->name,
                'reward_code' => $id
            ]);


            $this->memberPointLogRepository->create([
                'member_code' => $member->code,
                'point_type ' => 'W',
                'point' => $reward->points,
                'point_amount' => $reward->points,
                'point_before' => $member->point_deposit,
                'point_balance' => $total,
                'auto' => 'N',
                'remark' => 'แลกรางวัล อ้างอิง รายการ ที่ : ' . $bill->code,
                'ip' => $ip,
                'user_create' => $member->name,
                'user_update' => $member->name
            ]);

            $member->point_deposit = $total;
            $member->save();
            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            $result['message'] = 'พบข้อผิดพลาดในการทำรายการ';
            return $result;
        }


        $result['message'] = 'ทำรายการแลกรางวัล เรียบร้อยแล้ว';
        $result['success'] = true;
        return $result;
    }
}
