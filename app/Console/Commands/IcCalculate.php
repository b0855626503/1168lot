<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IcCalculate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ic:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = now();

        $lastWeekStart = $now->copy()->subWeek()->startOfWeek();  // จันทร์ที่แล้ว
        $lastWeekEnd = $now->copy()->subWeek()->endOfWeek();      // อาทิตย์ที่แล้ว
        $startDate = $lastWeekStart->startOfDay();
        $endDate = $lastWeekEnd->endOfDay();

        //        $lastFriday = $now->copy()->subWeek()->endOfWeek(Carbon::FRIDAY);
        //
        //        // หา "เสาร์ก่อนหน้าศุกร์นั้น"
        //        $lastSaturday = $lastFriday->copy()->subDays(6);
        //
        //        // สร้างช่วงเวลา
        //        $startDate = $lastSaturday->startOfDay();  // เสาร์ที่แล้ว เวลา 00:00
        //        $endDate = $lastFriday->endOfDay();

        $roundLabel = 'รอบสัปดาห์ (' . $startDate->format('d/m') . ' - ' . $endDate->format('d/m') . ')';
        $this->info($roundLabel);
        $promotion = DB::table('promotions')->where('id', 'pro_ic')->first();

        if ($promotion->enable != 'Y' || $promotion->active != 'Y' || $promotion->use_auto != 'Y') {
            return false;
        }

//        $latestBi = DB::table('bills')
//            ->select('bills.member_code', DB::raw('SUM(bills.credit_bonus)  as bonus_amount'))
//            ->where('bills.enable', 'Y')
//            ->where('bills.transfer_type', 1)
//            ->when($startDate, function ($query) use ($startDate, $endDate) {
//                $query->whereBetween('bills.date_create', [$startDate, $endDate]);
//            })
//            ->groupBy('bills.member_code');

        $latestWD = DB::table('withdraws_seamless', 'withdraws')
            ->select('withdraws.member_code', DB::raw('SUM(withdraws.amount)  as withdraw_amount'))
            ->where('withdraws.enable', 'Y')
            ->where('withdraws.status', 1)
            ->when($startDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('withdraws.date_approve', [$startDate, $endDate]);
            })
            ->groupBy('withdraws.member_code');

        $latestBP = DB::table('bank_payment')
            ->select(DB::raw('MAX(bank_payment.code) as code'), DB::raw('SUM(bank_payment.value) as deposit_amount'), DB::raw("'" . $startDate . "' as date_cashback"), 'bank_payment.member_topup')
            ->where('bank_payment.value', '>', 0)
            ->where('bank_payment.bankstatus', 1)
            ->where('bank_payment.pro_id', 0)
            ->where('bank_payment.enable', 'Y')
            ->where('bank_payment.status', 1)
            ->when($startDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('bank_payment.date_approve', [$startDate, $endDate]);
            })
            ->groupBy('bank_payment.member_topup');

        $lists = DB::table('members')
            ->select('members.upline_code', 'members.code as member_code', 'members.user_name as user_name', 'members.name as member_name', 'members.balance as balance', DB::raw('IFNULL(withdraw_amount,0) as withdraw_amount'), 'bank_payment.deposit_amount', DB::raw("'" . $startDate . "' as date_start"), DB::raw("'" . $endDate . "' as date_stop"), 'bank_payment.code')
            ->orderByDesc('members.code')
            ->joinSub($latestBP, 'bank_payment', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'members.code');
            })
//            ->leftJoinSub($latestBi, 'bills', function ($join) {
//                $join->on('bank_payment.member_topup', '=', 'bills.member_code');
//                //                $join->on(DB::raw('Date(bank_payment.date_approve)'), '=', 'bills.date_approve');
//
//            })
            ->leftJoinSub($latestWD, 'withdraws', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'withdraws.member_code');
                //                $join->on(DB::raw('Date(bank_payment.date_approve)'), '=', 'withdraws.date_approve');

            });

        //        dd($lists->get());

        $results = [];
        $lists->chunk(50, function ($itemlist) use ($startDate, $promotion) {

            foreach ($itemlist as $items) {

                \App\Jobs\IcCalculate::dispatch($startDate, $items, $promotion)->onQueue('ic');

            }

        });

        return 0;
    }
}
