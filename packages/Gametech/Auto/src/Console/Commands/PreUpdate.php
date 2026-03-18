<?php

namespace Gametech\Auto\Console\Commands;


use Gametech\Auto\Jobs\MemberCashback as MemberCashbackJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;


class PreUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'preupdate:work';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto Topup From Payment To Member';

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
        $result = shell_exec('composer update');
        $this->info($result);

    }
}
