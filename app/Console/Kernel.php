<?php

namespace App\Console;

use App\Console\Commands\GenerateGameIdsForPayments;
use Gametech\Auto\Console\Commands\AddCashback;
use Gametech\Auto\Console\Commands\AddCashbackSeamless;
use Gametech\Auto\Console\Commands\AddMemberIC;
use Gametech\Auto\Console\Commands\AddMemberICSeamless;
use Gametech\Auto\Console\Commands\AutoPayOut;
use Gametech\Auto\Console\Commands\Cashback;
use Gametech\Auto\Console\Commands\CheckFastStart;
use Gametech\Auto\Console\Commands\CheckPayment;
use Gametech\Auto\Console\Commands\ClearDB;
use Gametech\Auto\Console\Commands\DailyStat;
use Gametech\Auto\Console\Commands\DailyStatMonth;
use Gametech\Auto\Console\Commands\DiamondClear;
use Gametech\Auto\Console\Commands\GetPayment;
use Gametech\Auto\Console\Commands\GetPaymentAcc;
use Gametech\Auto\Console\Commands\MemberIC;
use Gametech\Auto\Console\Commands\NewCashback;
use Gametech\Auto\Console\Commands\NewCashbackSeamless;
use Gametech\Auto\Console\Commands\NewCashbackV2;
use Gametech\Auto\Console\Commands\NewICV2;
use Gametech\Auto\Console\Commands\NewMemberIC;
use Gametech\Auto\Console\Commands\NewMemberICSeamless;
use Gametech\Auto\Console\Commands\OptimizeTable;
use Gametech\Auto\Console\Commands\PostUpdate;
use Gametech\Auto\Console\Commands\PreUpdate;
use Gametech\Auto\Console\Commands\TopupPayment;
use Gametech\Auto\Console\Commands\UpdateHash;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CheckPayment::class,
        TopupPayment::class,
        GetPayment::class,
        GetPaymentAcc::class,
        Cashback::class,
        MemberIC::class,
        DailyStat::class,
        CheckFastStart::class,
        DailyStatMonth::class,
        UpdateHash::class,
        PostUpdate::class,
        OptimizeTable::class,
        PreUpdate::class,
        NewCashback::class,
        NewMemberIC::class,
        AddCashback::class,
        AddMemberIC::class,
        ClearDB::class,
        NewCashbackSeamless::class,
        NewMemberICSeamless::class,
        AddCashbackSeamless::class,
        AddMemberICSeamless::class,
        DiamondClear::class,
        AutoPayOut::class,
        NewCashbackV2::class,
        NewICV2::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $yesterday = now()->subDays(1)->toDateString();

//        $schedule->command('cashback:start')
//            ->weeklyOn(1, '0:05')
//            ->runInBackground();
//
//        $schedule->command('clear:cashback')
//            ->weeklyOn(2, '0:00')
//            ->runInBackground();

        $schedule->command('newcashback2:list')->dailyAt('00:05');

//        $schedule->command('newic2:list')->dailyAt('12:00');

        $schedule->command('cleanup:inactive-users')->everyFiveMinutes();

//        $schedule->command('cleardb:start')->dailyAt('13:00')->runInBackground();

        $schedule->command('dailystat:check ' . $yesterday)->dailyAt('00:05')->runInBackground();

        $schedule->command('lada-cache:flush')->dailyAt('12:30');
        $schedule->command('lada-cache:flush')->dailyAt('00:18');
        $schedule->command('optimize:clear')->dailyAt('12:30');
        $schedule->command('queue:restart')->everyFourHours();
        $schedule->command('migrate --force')->dailyAt('23:28');
        //        $schedule->command('postupdate:work')->everyFiveMinutes();

        $schedule->command('payment:get kbank')->everyMinute()
            ->after(function () {
//                $this->call('payment:get scb');
//                $this->call('payment:get gsb');
//                $this->call('payment:get ktb');
                $this->call('payment:check tw 10');
//                $this->call('payment:check bay 10');
                $this->call('payment:check scb 10');
                $this->call('payment:check kbank 10');
//                $this->call('payment:check ttb 10');
            });

        $schedule->command('payment:emp-topup 50')->everyMinute();

        // Lotto auto draw bootstrap from market schedule (manual-safe, idempotent).
        $schedule->command('lotto:generate-auto-draws --days=3')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        $this->load(__DIR__ . '/../../packages/Gametech/Auto/src/Console/Commands');

        require base_path('routes/console.php');
    }
}
