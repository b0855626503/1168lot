<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MongoDB\Collection;

class GamelogMakeIndexesSafe extends Command
{
    protected $signature = 'gamelog:index-safe
        {--conn=mongodb : ชื่อคอนเนคชัน}
        {--db= : ชื่อฐานข้อมูล (ว่าง = จากคอนเนคชัน)}
        {--collection=gamelog : คอลเลกชัน}
        {--drop-dup : ดรอป fingerprint_1 (unique) ถ้ามี}';

    protected $description = 'ทำ index ให้ปลอดภัย: redis_id เป็น unique, fingerprint เป็น non-unique (partial)';

    public function handle()
    {
        $connName = (string)$this->option('conn');
        $dbOpt = $this->option('db');
        $colName = (string)$this->option('collection');

        $conn = DB::connection($connName);
        $client = $conn->getMongoClient();
        $dbName = $dbOpt ?: ($conn->getDatabaseName() ?? config("database.connections.$connName.database"));
        /** @var Collection $col */
        $col = $client->selectDatabase($dbName)->selectCollection($colName);

        $this->info("Target: {$connName}/{$dbName}.{$colName}");

        // 1) ดรอป fingerprint_1 (unique) ถ้ามี
        if ($this->option('drop-dup')) {
            try {
                $col->dropIndex('fingerprint_1');
                $this->line(' - dropped: fingerprint_1');
            } catch (\Throwable $e) {
                $this->line(' - no fingerprint_1 to drop');
            }
        }

        // 2) ทำ redis_id เป็น unique (การันตีเอกสารไม่ซ้ำ)
        try {
            $col->createIndex(['redis_id' => 1], [
                'name' => 'uniq_redis_id',
                'unique' => true,
                'sparse' => false,
            ]);
            $this->line(' + created: uniq_redis_id');
        } catch (\Throwable $e) {
            $this->warn(' ! uniq_redis_id: ' . $e->getMessage());
        }

        // 3) fingerprint เป็น non-unique + partial (เฉพาะเอกสารที่มีฟิลด์นี้จริง)
        try {
            $col->createIndex(['fingerprint' => 1], [
                'name' => 'fingerprint_1',
                'partialFilterExpression' => [
                    'fingerprint' => ['$exists' => true, '$type' => 2],
                ],
            ]);
            $this->line(' + created: fingerprint_1 (non-unique partial)');
        } catch (\Throwable $e) {
            $this->warn(' ! fingerprint_1: ' . $e->getMessage());
        }

        $this->line("\nCurrent indexes:");
        foreach ($col->listIndexes() as $idx) {
            $this->line('  * ' . $idx->getName() . ' => ' . json_encode($idx->getKey()));
        }

        $this->info('Index safe setup complete ✅');
        return self::SUCCESS;
    }
}
