<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class KickoffWinlose extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kickoff:turn {start?} {end?}';

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
        $startArg = $this->argument('start');
        $start= $startArg ? Carbon::parse($startArg)->toDateString() : now()->subDay()->toDateString();

        $endArg = $this->argument('end');
        $end= $endArg ? Carbon::parse($endArg)->toDateString() : now()->subDay()->toDateString();

        $param = [
            'startDate' => $start,
            'endDate' => $end,
        ];

        $repo = app('Gametech\Game\Repositories\GameUserRepository');

        // NOTE: ในระบบเดิมคุณใช้ checkUserTurn(1, 0, $param)
        $lists = $repo->checkUserTurn(1, 0, $param);

        $path = storage_path('logs/seamless/winlose-cmd' . $start . '-'.$end.'.log');
        file_put_contents($path, print_r($lists, true), FILE_APPEND);


        return 0;
    }
}
