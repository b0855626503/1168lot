<?php

namespace Gametech\Auto\Console\Commands;

use Illuminate\Console\Command;


class DailyStat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dailystat:check {date?} {--queue=default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create and Check Daily Stat';

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
        $date = $this->argument('date');
        if (!$date) {
            $date = now()->toDateString();
        }

        app('Gametech\Core\Repositories\DailyStatRepository')->sumData($date);

    }

}
