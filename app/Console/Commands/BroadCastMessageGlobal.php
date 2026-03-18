<?php

namespace App\Console\Commands;

use App\Events\RealTimeMessageAll;
use Illuminate\Console\Command;

class BroadCastMessageGlobal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'message:global {message}';

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
        $message = $this->argument('message');

        broadcast(new RealTimeMessageAll($message));
        $this->info('Broadcasted: ' . $message);
        return self::SUCCESS; // คืนค่าเป็น int

    }
}
