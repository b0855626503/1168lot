<?php

namespace Gametech\Payment\Repositories;

use Gametech\Core\Eloquent\Repository;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser;
use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Container\Container as App;
use Throwable;

class BonusSpinRepository extends Repository
{
    private $memberRepository;

    public function __construct
    (
        MemberRepository $memberRepo,
        App $app
    )
    {
        $this->memberRepository = $memberRepo;

        parent::__construct($app);
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model(): string
    {
        return \Gametech\Payment\Models\BonusSpin::class;

    }

    public function SpinCreate(array $data): bool
    {
        $member_code = $data['member_code'];
        $credit_after = $data['credit_after'];
        $diamond = $data['diamond_balance'];
        $reward_type = $data['reward_type'];
        $amount = $data['amount'];

        ActivityLoggerUser::activity('Spin Reward', 'เริ่มต้นบันทึกรายการเล่นวงล้อมหาสนุก');


        $member = $this->memberRepository->find($member_code);

        $member->balance = $credit_after;
        $member->diamond = $diamond;
        $member->save();

        try {

            $this->create($data);

        } catch (Throwable $e) {

            report($e);
            return false;
        }

        return true;
    }
}
