<?php

namespace App\Console\Commands;

use Gametech\Auto\Jobs\UpdateBalanceKingPay;
use Gametech\Auto\Jobs\UpdateBalanceWellPay;
use Gametech\Auto\Jobs\UpdateBalanceWildPay;
use Illuminate\Console\Command;

class KickoffRegister extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kickoff:register';

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

        return 0;
    }
}
