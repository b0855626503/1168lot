<?php

namespace App\Console\Commands;

use App\Services\RedisToMongoFlusher;
use Illuminate\Console\Command;

class FlushRedisLogsUser extends Command
{
    protected $signature = 'gamelog:flush-user {user} {--batch=2000}';
    protected $description = 'Flush all Redis game logs (all companies) for a user to MongoDB';

    public function handle(RedisToMongoFlusher $flusher)
    {
        $user = (string)$this->argument('user');
        $batch = (int)$this->option('batch');

        $res = $flusher->flushUser($user, $batch);

        $this->info("User={$user} Processed: {$res['processed']}, Upserted: {$res['upserted']}");
        return self::SUCCESS;
    }
}
