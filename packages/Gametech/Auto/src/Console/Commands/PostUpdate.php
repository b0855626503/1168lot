<?php

namespace Gametech\Auto\Console\Commands;

use Gametech\Admin\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;


class PostUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'postupdate:work';

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

//        $this->call('migrate',[
//            '--force' => true
//    ]);
//        $this->call('optimize:clear');


    }
}
