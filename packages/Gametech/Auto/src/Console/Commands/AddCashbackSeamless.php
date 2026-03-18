<?php

namespace Gametech\Auto\Console\Commands;


use Gametech\Auto\Jobs\NewMemberCashbackSeamless as NewMemberCashbackSeamlessJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class AddCashbackSeamless extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'new_cb:topup {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and Refill Cashback Seamless to member';

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

        $startdate = $this->argument('date');

        if (empty($startdate)) {
            $startdate = now()->subDays(1)->toDateString();
        }

        $lists = DB::table('members_cashback')->whereDate('date_cashback', $startdate)->where('topupic', 'N')->orderBy('code');


        $bar = $this->output->createProgressBar($lists->count());
        $bar->start();

        $lists->chunk(100, function ($itemlist) use ($startdate, $bar) {
            foreach ($itemlist as $items) {

                NewMemberCashbackSeamlessJob::dispatch($startdate, $items)->onQueue('cashback');;
                $bar->advance();
            }
        });

//        foreach ($lists->cursor() as $items) {
//
//            NewMemberCashbackJob::dispatch($startdate, $items)->onQueue('cashback');
//
//            $bar->advance();
//        }
        $bar->finish();

    }

}
