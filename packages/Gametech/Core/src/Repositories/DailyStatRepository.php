<?php

namespace Gametech\Core\Repositories;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DailyStatRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return \Gametech\Core\Models\DailyStat::class;
    }

    public function sumData($date)
    {
        $config = core()->getConfigData();


        $yesterday = Carbon::parse($date)->subDay()->toDateString();

//        $chkbefore = $this->whereDate('date',$yesterday);
//        if($chkbefore->exists()){
//            $member_all_yesterday
//        }

        $members = app('Gametech\Member\Repositories\MemberRepository')->where('enable','Y');
//        dd($members->count());
        $member_all = (clone $members)->whereDate('date_regis','<=',$date)->count();
        $member_new =  (clone $members)->whereDate('date_regis',$date)->count();
        $member_new_code = (clone $members)->whereDate('date_regis',$date)->pluck('code');

        $payments = app('Gametech\Payment\Repositories\BankPaymentRepository')->income()->active()
            ->complete()
            ->where('enable','Y')
            ->whereRaw(DB::raw("DATE_FORMAT(date_create,'%Y-%m-%d') = ?"), [$date])
            ->where('bankstatus',1);

        $member_all_refill = (clone $payments)->select('member_topup')->distinct('member_topup')->where('member_topup', '<>' , 0)->count();
        $member_new_refill = (clone $payments)->select('member_topup')->distinct('member_topup')->whereIntegerInRaw('member_topup',$member_new_code);

        $deposit_count = (clone $payments)->count();
        $deposit_sum = (clone $payments)->sum('value');


        if($config['seamless'] == 'Y'){

            $withdraws1 =app('Gametech\Payment\Repositories\WithdrawSeamlessRepository')->active()->complete()
                ->whereRaw(DB::raw("DATE_FORMAT(date_approve,'%Y-%m-%d') = ?"), [$date]);

        }else{

            $withdraws1 =app('Gametech\Payment\Repositories\WithdrawRepository')->active()->complete()
                ->whereRaw(DB::raw("DATE_FORMAT(date_approve,'%Y-%m-%d') = ?"), [$date]);

        }


//        $withdraws2 =app('Gametech\Payment\Repositories\WithdrawFreeRepository')->active()->complete()
//            ->whereRaw(DB::raw("DATE_FORMAT(date_approve,'%Y-%m-%d') = ?"), [$date]);

        $withdraw_count1 = (clone $withdraws1)->count();
//        $withdraw_count2 = (clone $withdraws2)->count();
        $withdraw_count = ($withdraw_count1);
        $withdraw_sum1 = (clone $withdraws1)->sum('amount');
//        $withdraw_sum2 = (clone $withdraws2)->sum('amount');
        $withdraw_sum = ($withdraw_sum1);

        $data1 = app('Gametech\Payment\Repositories\PaymentPromotionRepository')->active()->aff()->whereDate('date_create',$date)->sum('credit_bonus');
        $data2 = app('Gametech\Payment\Repositories\BillRepository')->active()->getpro()->where('transfer_type', 1)->whereDate('date_create',$date)->sum('credit_bonus');
        $bonus_sum = ($data1 + $data2);

        $setwallet_d_sum = app('Gametech\Member\Repositories\MemberCreditLogRepository')
            ->where('kind','SETWALLET')->where('credit_type','D')->where('enable','Y')
            ->whereDate('date_create',$date)->sum('amount');

        $setwallet_w_sum = app('Gametech\Member\Repositories\MemberCreditLogRepository')
            ->where('kind','SETWALLET')->where('credit_type','W')->where('enable','Y')
            ->whereDate('date_create',$date)->sum('amount');

        $daily = $this->updateOrCreate(
            ['date' => $date],
            [
                'member_all' => $member_all,
                'member_new' => $member_new,
                'member_new_list' => collect($member_new_code)->toJson(),
                'member_new_refill' => $member_new_refill->count(),
                'member_new_refill_list' => collect($member_new_refill->pluck('member_topup'))->toJson(),
                'member_all_refill' => $member_all_refill,
                'deposit_count' => $deposit_count,
                'deposit_sum' => $deposit_sum,
                'withdraw_count' => $withdraw_count,
                'withdraw_sum' => $withdraw_sum,
                'bonus_sum' => $bonus_sum,
                'setwallet_d_sum' => $setwallet_d_sum,
                'setwallet_w_sum' => $setwallet_w_sum,

            ]
        );
    }
}
