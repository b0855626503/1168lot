<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RedisToMongoFlusher;

class FlushRedisLogs extends Command
{
    /**
     * ใช้ได้สองโหมด:
     *  - php artisan gamelog:flush                    // ไม่ระบุ user → flush ทั้งหมดของเว็บนี้
     *  - php artisan gamelog:flush boattester --batch=2000
     */
    protected $signature = 'gamelog:flush
                            {user? : game_user (username) ; เว้นไว้เพื่อ flush ทั้งหมด}
                            {--batch=2000 : จำนวนต่อแบตช์ระหว่างเขียน Mongo}';

    protected $description = 'Flush game-logs จาก Redis ขึ้น Mongo (ทั้งระบบ หรือเฉพาะ user)';

    public function handle(RedisToMongoFlusher $flusher): int
    {
        $user  = (string)($this->argument('user') ?? '');
        $batch = (int)$this->option('batch');

        try {
            $res = $user !== ''
                ? $flusher->flushUser($user, $batch)
                : $flusher->flushAll($batch);    // ใช้เวอร์ชันที่แก้ให้รองรับทั้งระบบ

            $this->table(
                ['mode','user','processed','inserted','matched','modified','skipped','upserted','error'],
                [[
                    $user !== '' ? 'single-user' : 'all',
                    $user ?: '-',
                    $res['processed'] ?? 0,
                    $res['inserted']  ?? ($res['upserted'] ?? 0),
                    $res['matched']   ?? 0,
                    $res['modified']  ?? 0,
                    $res['skipped']   ?? 0,
                    $res['upserted']  ?? ($res['inserted'] ?? 0),
                    $res['error']     ?? '',
                ]]
            );

            return empty($res['error']) ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
