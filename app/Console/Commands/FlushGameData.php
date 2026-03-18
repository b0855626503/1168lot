<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RedisToMongoGameDataFlusher;

class FlushGameData extends Command
{
    protected $signature = 'gamedata:flush {--batch=500}';
    protected $description = 'Flush ALL gamedata logs (this site/prefix) from Redis to MongoDB';

    public function handle(RedisToMongoGameDataFlusher $flusher)
    {
        $batch = (int) $this->option('batch');
        $this->line("<info>==> Gamedata flush (ALL)</info> batch={$batch}");

        $res = $flusher->flushAll($batch);

        $this->table(
            ['processed','inserted','matched','modified','skipped','upserted','error'],
            [[
                $res['processed'] ?? 0,
                $res['inserted']  ?? ($res['upserted'] ?? 0),
                $res['matched']   ?? 0,
                $res['modified']  ?? 0,
                $res['skipped']   ?? 0,
                $res['upserted']  ?? ($res['inserted'] ?? 0),
                $res['error']     ?? '',
            ]]
        );

        return self::SUCCESS;
    }
}
