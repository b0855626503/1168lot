<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RedisToMongoGameDataFlusher;

class FlushGameDataUser extends Command
{
    protected $signature = 'gamedata:flush-user {user : game_user (username)} {--batch=500}';
    protected $description = 'Flush gamedata logs for a single user from Redis to MongoDB';

    public function handle(RedisToMongoGameDataFlusher $flusher)
    {
        $user = (string)$this->argument('user');
        $batch = (int)$this->option('batch');

        $res = $flusher->flushUser($user, $batch);

        $this->info(sprintf(
            "User=%s Processed: %d, Inserted: %d, Matched: %d, Modified: %d, Skipped: %d",
            $user, $res['processed'] ?? 0, $res['inserted'] ?? 0, $res['matched'] ?? 0, $res['modified'] ?? 0, $res['skipped'] ?? 0
        ));

        return self::SUCCESS;
    }
}
