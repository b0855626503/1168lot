<?php

namespace Gametech\Auto\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;


class DailyStatMonth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dailystat:month';

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

        for ($x = 1; $x <= 18; $x++) {
            Artisan::call('dailystat:check', [
                'date' => '2021-04-'.sprintf("%02d", $x)
            ]);
        }

    }

}
