<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RealtimeUpdate
{

    public function getUpdate()
    {
        $id = auth()->guard('admin')->id();
        $startdate = now()->toDateString() . ' 00:00:00';
        $enddate = now()->toDateString() . ' 23:59:59';
        $today = now()->toDateString();

        $config = core()->getConfigData();

        $bank_in_today = app('Gametech\Payment\Repositories\BankPaymentRepository')
            ->income()->active()->waiting()
            ->whereDate('date_create', $today)
            ->whereIn('autocheck', ['N', 'W'])
            ->count();
        $bank_in = app('Gametech\Payment\Repositories\BankPaymentRepository')
            ->income()->active()->waiting()
//            ->whereIn('autocheck', ['N', 'W'])
            ->count();

        $bank_out = app('Gametech\Payment\Repositories\BankPaymentRepository')
            ->profit()->active()->waiting()
            ->where('autocheck', 'N')
            ->whereBetween('date_create', array($startdate, $enddate))
            ->count();

        if($config->seamless == 'Y'){
            $withdraw = app('Gametech\Payment\Repositories\WithdrawSeamlessRepository')
                ->active()->waiting()
                ->count();
            $withdraw_free = app('Gametech\Payment\Repositories\WithdrawSeamlessFreeRepository')
                ->active()->waiting()
                ->count();

//            $withdraw_free = 0;
        }else{
            $withdraw = app('Gametech\Payment\Repositories\WithdrawRepository')
                ->active()->waiting()
                ->count();
            $withdraw_free = app('Gametech\Payment\Repositories\WithdrawFreeRepository')
                ->active()->waiting()
                ->count();
        }




        $payment_waiting = app('Gametech\Payment\Repositories\PaymentWaitingRepository')
            ->whereDate('date_create', '>', '2021-04-05')
            ->active()->waiting()
            ->count();

        $member_confirm = app('Gametech\Member\Repositories\MemberRepository')
            ->active()->waiting()
            ->count();


        $announce = [
            'content' => '',
            'updated_at' => now()->toDateTimeString()
        ];

        $announce_new = 'N';

        $response = Http::get('https://announce.168csn.com/api/announce');

        if ($response->successful()) {
            $response = $response->json();
//            dd($response);
            $announce = $response['data'];
        }

//        dd($announce);

        if (!Cache::has($id . 'announce_start')) {
            Cache::add($id . 'announce_stop', $announce['updated_at']);
        }
        if (!Cache::has($id . 'announce_stop')) {
            Cache::add($id . 'announce_stop', $announce['updated_at']);
        } else {
            Cache::put($id . 'announce_stop', $announce['updated_at']);
        }

        $start = Cache::get($id . 'announce_start');
        $stop = Cache::get($id . 'announce_stop');
        if ($start != $stop) {
            $announce_new = 'Y';
            Cache::put($this->id() . 'announce_start', $stop);
        }


        $result['member_confirm'] = $member_confirm;
        $result['bank_in_today'] = $bank_in_today;
        $result['bank_in'] = $bank_in;
        $result['bank_out'] = $bank_out;
        $result['withdraw'] = $withdraw;
        $result['withdraw_free'] = $withdraw_free;
        $result['payment_waiting'] = $payment_waiting;
        $result['announce'] = $announce['content'];
        $result['announce_new'] = $announce_new;

        return $result;
    }

}
