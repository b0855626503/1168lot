<?php

namespace Gametech\Auto\Console\Commands;


use Gametech\Auto\Jobs\NewDelMemberIc as NewDelMemberIcJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class DelMemberIC extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'newic:deltopup {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and Refill IC to upline';

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
        $ip = request()->ip();
        $startdate = $this->argument('date');

        if (empty($startdate)) {
            $startdate = now()->subDays(1)->toDateString();
        }

        $lists = DB::table('members_ic')->whereDate('date_cashback', $startdate)->where('topupic', 'Y')->where('member_code', '>' , 0)->orderBy('code');


        $bar = $this->output->createProgressBar($lists->count());
        $bar->start();

        $lists->chunk(100, function ($itemlist) use ($startdate, $bar) {
            foreach ($itemlist as $items) {

                NewDelMemberIcJob::dispatchNow($startdate, $items);
                $bar->advance();
            }
        });

//        foreach ($lists->cursor() as $items) {
//
//            NewMemberIcJob::dispatch($startdate, $items)->onQueue('ic');
//            $bar->advance();
//        }
        $bar->finish();

    }

}
