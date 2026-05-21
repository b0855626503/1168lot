<?php

namespace App\Console;

use App\Console\Commands\BackfillLottoBetConfirmedAtCommand;
use App\Console\Commands\BackfillLottoRiskCurrentCommand;
use App\Console\Commands\CleanupLottoRiskSnapshotsCommand;
use App\Console\Commands\Dashboard\LottoRiskCurrentCleanupCommand;
use App\Console\Commands\RebuildLottoDashboardSummaryCommand;
use App\Console\Commands\ValidateLottoRiskCurrentCommand;
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
use Gametech\Lotto\Services\Relay\LotteryRelayRuntime;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
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
        RebuildLottoDashboardSummaryCommand::class,
        BackfillLottoBetConfirmedAtCommand::class,
        BackfillLottoRiskCurrentCommand::class,
        ValidateLottoRiskCurrentCommand::class,
        CleanupLottoRiskSnapshotsCommand::class,
        LottoRiskCurrentCleanupCommand::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $relayRuntime = $this->app->make(LotteryRelayRuntime::class);
        $yesterday = now()->subDays(1)->toDateString();

        $schedule->command('cleanup:inactive-users')->everyFiveMinutes();
        $schedule->command('dailystat:check '.$yesterday)->dailyAt('00:05')->runInBackground();
        $schedule->command('lada-cache:flush')->dailyAt('12:30');
        $schedule->command('lada-cache:flush')->dailyAt('00:18');
        $schedule->command('optimize:clear')->dailyAt('12:30');
        $schedule->command('queue:restart')->everyFourHours();
        $schedule->command('migrate --force')->dailyAt('23:28');

        $schedule->command('payment:get kbank')->everyMinute();

        $schedule->command('lotto:sync-draw-statuses')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('lotto:generate-auto-draws --days=1')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('lotto:generate-yeekee-draws --date=tomorrow')
            ->dailyAt('00:05')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('lotto:generate-yeekee-draws --window=+6h')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('lotto:settle-yeekee-rounds --limit=200')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('lotto:fetch-auto-results --limit=100')
            ->everyMinute()
            ->when(static fn (): bool => $relayRuntime->shouldRunLegacyAutoResultSweep())
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('lotto:relay:consume-stream --once --count=50 --block-ms=1000')
            ->everyMinute()
            ->when(static fn (): bool => $relayRuntime->shouldConsumeRelayStream())
            ->withoutOverlapping()
            ->runInBackground();

        // $schedule->command('lotto:clone-auto-pull-thai-results')
        //     ->everyMinute()
        //     ->when(static fn (): bool => $relayRuntime->isClone())
        //     ->withoutOverlapping()
        //     ->runInBackground();

        $schedule->command('lotto:mirror-draws-to-legacy-archive')
            ->everyMinute()
            ->when(static fn (): bool => ! $relayRuntime->isClone())
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('lotto:legacy-results:fetch --today')
            ->everyTenMinutes()
            ->between('5:00', '18:00')
            ->when(static fn (): bool => ! $relayRuntime->isClone())
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('lotto:cleanup-browser-runtime-artifacts')
            ->dailyAt('03:55')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('dashboard:lotto-risk-retention', [
            '--chunk' => 5000,
            '--max-runtime' => 120,
            '--sleep-ms' => 100,
        ])
            ->dailyAt('03:40')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('dashboard:lotto-risk-current-backfill', [
            '--chunk' => 500,
            '--since-days' => 90,
            '--max-runtime' => 300,
        ])
            ->dailyAt('03:50')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('wallet:prune-transactions --days=7')
            ->dailyAt('04:00')
            ->withoutOverlapping()
            ->runInBackground();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        $this->load(__DIR__.'/../../packages/Gametech/Auto/src/Console/Commands');

        require base_path('routes/console.php');
    }
}
