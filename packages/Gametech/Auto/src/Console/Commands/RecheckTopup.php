<?php

namespace Gametech\Auto\Console\Commands;

use Gametech\Auto\Jobs\RecheckPayments;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class RecheckTopup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:recheck {limit=10}';

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


        $this->info('Auto Topup Start');
        $payments = DB::table('bank_payment')->where('status',0)
            ->where('member_topup','<>',0)
            ->where('enable','Y')
            ->where('autocheck','Y')->get();

        $bar = $this->output->createProgressBar($payments->count());
        $bar->start();


        foreach ($payments as $i => $payment) {
            RecheckPayments::dispatch($payment->code)->onQueue('topup');
            $bar->advance();
        }

        $bar->finish();
    }

}
