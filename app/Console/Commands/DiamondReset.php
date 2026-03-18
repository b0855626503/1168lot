<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiamondReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diamond:reset';

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
        $lists = DB::table('members')->where('diamond', '>', 0)->orderBy('diamond', 'desc');

        $bar = $this->output->createProgressBar($lists->count());
        $bar->start();

        $lists->chunk(50, function ($itemlist) use ($bar) {
            foreach ($itemlist as $item) {

                $data = [
                    'remark' => 'รีเซตเพชร เนื่องจากระบบมอบ จากค่าพื้นฐานเวบ',
                    'amount' => $item->diamond,
                    'method' => 'W',
                    'member_code' => $item->code,
                    'emp_code' => 0,
                    'emp_name' => 'SYSTEM AUTO',
                ];

                $response = app('Gametech\Member\Repositories\MemberDiamondLogRepository')->setDiamond($data);
                if ($response) {
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        return 0;
        //        DB::table('members')->update(['diamond' => 0]);
    }
}
