<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class RealtimeCounterService
{
    private const CACHE_KEY = 'realtime:counters:v2';
    private const CACHE_TTL_SECONDS = 5;

    /**
     * @return array<string, int>
     */
    public function getCounts(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            fn (): array => $this->queryCounts()
        );
    }

    /**
     * @return array<string, int>
     */
    private function queryCounts(): array
    {
        [$todayStartAt, $tomorrowStartAt] = $this->todayRange();
        $config = core()->getConfigData();

        $bankInToday = app('Gametech\Payment\Repositories\BankPaymentRepository')
            ->income()
            ->active()
            ->waiting()
            ->where('date_create', '>=', $todayStartAt)
            ->where('date_create', '<', $tomorrowStartAt)
            ->count();

        $bankIn = app('Gametech\Payment\Repositories\BankPaymentRepository')
            ->income()
            ->active()
            ->waiting()
            ->where('date_create', '<', $todayStartAt)
            ->count();

        $bankOut = app('Gametech\Payment\Repositories\BankPaymentRepository')
            ->profit()
            ->active()
            ->waiting()
            ->where('autocheck', 'N')
            ->where('date_create', '>=', $todayStartAt)
            ->where('date_create', '<', $tomorrowStartAt)
            ->count();

        if ($config->seamless == 'Y') {
            $withdraw = app('Gametech\Payment\Repositories\WithdrawSeamlessRepository')
                ->active()
                ->waiting()
                ->count();
            $withdrawFree = app('Gametech\Payment\Repositories\WithdrawSeamlessFreeRepository')
                ->active()
                ->waiting()
                ->count();
        } else {
            $withdraw = app('Gametech\Payment\Repositories\WithdrawRepository')
                ->active()
                ->waiting()
                ->count();
            $withdrawFree = app('Gametech\Payment\Repositories\WithdrawFreeRepository')
                ->active()
                ->waiting()
                ->count();
        }

        $paymentWaiting = app('Gametech\Payment\Repositories\PaymentWaitingRepository')
            ->where('date_create', '>=', '2021-04-06 00:00:00')
            ->active()
            ->waiting()
            ->count();

        $memberConfirm = app('Gametech\Member\Repositories\MemberRepository')
            ->active()
            ->waiting()
            ->count();

        return [
            'member_confirm' => (int) $memberConfirm,
            'bank_in_today' => (int) $bankInToday,
            'bank_in' => (int) $bankIn,
            'bank_out' => (int) $bankOut,
            'withdraw' => (int) $withdraw,
            'withdraw_free' => (int) $withdrawFree,
            'payment_waiting' => (int) $paymentWaiting,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function todayRange(): array
    {
        $startAt = Carbon::now()->startOfDay();
        $endAt = (clone $startAt)->addDay();

        return [$startAt->toDateTimeString(), $endAt->toDateTimeString()];
    }
}
