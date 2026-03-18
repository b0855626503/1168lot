<?php

namespace Gametech\Member\Repositories;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Throwable;

class MemberFreeCreditRepository extends Repository
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
     * @return string
     */
    function model(): string
    {
        return \Gametech\Member\Models\MemberFreeCredit::class;

    }

    public function setCredit(array $data): bool
    {

        $ip = request()->ip();
        $credit_balance = 0;
        $member_code = $data['member_code'];
        $amount = $data['amount'];
        $method = $data['method'];
        $remark = $data['remark'];
        $emp_code = $data['emp_code'];
        $emp_name = $data['emp_name'];
        $kind = $data['kind'];

        $member = $this->memberRepository->find($member_code);

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
                'credit_type' => $method,
                'credit' => $amount,
                'credit_amount' => $amount,
                'credit_before' => $member->balance_free,
                'credit_balance' => $credit_balance,
                'member_code' => $member_code,
                'kind' => $kind,
                'auto' => 'N',
                'remark' => $remark,
                'emp_code' => $emp_code,
                'ip' => $ip,
                'user_create' => $emp_name,
                'user_update' => $emp_name
            ]);

            $member->balance_free = $credit_balance;
            $member->save();

            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            return false;
        }

        return true;
    }
}
