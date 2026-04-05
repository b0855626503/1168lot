<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MongoDB\Collection;
use MongoDB\Driver\Exception\BulkWriteException;

class ReindexGameLog extends Command
{
    protected $signature = 'gamelog:reindex
        {--conn=mongodb : ชื่อคอนเนคชัน MongoDB ใน config/database.php}
        {--db= : ชื่อ database (ว่าง = ใช้จากคอนเนคชัน)}
        {--collection=gamelog : ชื่อคอลเลกชัน}
        {--drop : ดรอป index เก่าก่อนสร้าง}
        {--skip-global : ไม่สร้างชุด global}
        {--skip-user-first : ไม่สร้างชุด user-first}';

    protected $description = 'Drop & Recreate indexes (TTL, partial, unique guard) ทั้งชุด GLOBAL + USER-FIRST (company+game_user+method)';

    public function handle()
    {
        $connName = (string)$this->option('conn');
        $dbOpt = $this->option('db');
        $colName = (string)$this->option('collection');
        $mkGlobal = !$this->option('skip-global');
        $mkUser = !$this->option('skip-user-first');

        /** @var \MongoDB\Laravel\Connection $conn */
        $conn = DB::connection($connName);
        $client = $conn->getMongoClient();
        $dbName = $dbOpt ?: ($conn->getDatabaseName() ?? config("database.connections.$connName.database"));
        /** @var Collection $col */
        $col = $client->selectDatabase($dbName)->selectCollection($colName);

        $this->info("Target: {$connName}/{$dbName}.{$colName}");

        if ($this->option('drop')) {
            $this->line('Dropping old indexes...');
            $d = 0;
            foreach ($col->listIndexes() as $idx) {
                if ($idx->getName() === '_id_') continue;
                try {
                    $col->dropIndex($idx->getName());
                    $d++;
                } catch (\Throwable $e) {
                    $this->warn(" - drop fail {$idx->getName()}: " . $e->getMessage());
                }
            }
            if (!$d) $this->line(' - ไม่พบ index ให้ดรอป (ยกเว้น _id_)');
        }

        $this->line('Creating indexes...');

        $create = function (array $keys, array $opts) use ($col) {
            $name = $opts['name'] ?? json_encode($keys);
            $col->createIndex($keys, $opts);
            $this->line(" + created: {$name}");
        };

        $createGuardUnique = function (array $keys, array $opts, ?array $partial = null) use ($col) {
            $name = $opts['name'] ?? json_encode($keys);
            if ($partial) $opts['partialFilterExpression'] = $partial;
            try {
                $col->createIndex($keys, $opts);
                $this->line(" + created: {$name}");
            } catch (BulkWriteException $e) {
                if (strpos($e->getMessage(), 'E11000') !== false) {
                    $this->warn(" ! unique '{$name}' E11000 → fallback non-unique");
                    unset($opts['unique']);
                    $col->createIndex($keys, $opts);
                    $this->line("   -> created NON-UNIQUE: {$name}");
                } else {
                    throw $e;
                }
            }
        };

        // ===== Core & safety =====
        // TTL: documents ที่มี expireAt เท่านั้น
        $create(['expireAt' => 1], ['name' => 'ttl_expireAt', 'expireAfterSeconds' => 0]);

        // uniq_inputId: partial unique (string non-empty เท่านั้น)
//        $pfeNonEmptyInputId = [
//            '$and' => [
//                ['input.id' => ['$exists'=>true]],
//                ['input.id' => ['$type'=>2]],
//                ['input.id' => ['$gt'=>'']],
//            ],
//        ];
//        $createGuardUnique(['input.id'=>1], ['name'=>'uniq_inputId','unique'=>true], $pfeNonEmptyInputId);

        // txn id (array) — index แบบ partial non-unique เพื่อเร่งค้น
        $create(['input.txns.id' => 1], [
            'name' => 'byTxnId',
            'partialFilterExpression' => ['input.txns.0.id' => ['$exists' => true]],
        ]);

        // fingerprint — partial unique เฉพาะที่มีฟิลด์จริง
        try {
            $col->dropIndex('fingerprint_1');
        } catch (\Throwable $e) {
        }
        $createGuardUnique(['fingerprint' => 1], [
            'name' => 'fingerprint_1',
            'unique' => true,
        ], ['fingerprint' => ['$exists' => true]]);

        // ===== GLOBAL set =====
        if ($mkGlobal) {
            $create(['company' => 1, 'method' => 1, 'created_at' => -1], ['name' => 'byCompany_method_createdAt_desc']);
            $create(['company' => 1, 'response' => 1, 'created_at' => -1], ['name' => 'byCompany_response_createdAt']);
            $create(['company' => 1, 'input.productId' => 1, 'created_at' => -1], ['name' => 'byCompany_product_createdAt']);
            $create(['company' => 1, 'input.gameCode' => 1, 'created_at' => -1], ['name' => 'byCompany_gameCode_createdAt']);
            $create(['company' => 1, 'method' => 1, 'con_1' => 1, 'con_2' => 1], ['name' => 'byCompany_method_con1_con2']);
            $create(['output.id' => 1], ['name' => 'byOutputId']);
        }

        // ===== USER-FIRST set (company + game_user + method เป็น prefix) =====
        if ($mkUser) {
            // history ผู้ใช้ & method
            $create(['company' => 1, 'game_user' => 1, 'created_at' => -1], ['name' => 'byCompany_user_createdAt_desc']);
            $create(['company' => 1, 'game_user' => 1, 'method' => 1, 'created_at' => -1], ['name' => 'byCompany_user_method_createdAt_desc']);

            // partial: เฉพาะธุรกรรมจริง (ไม่นับ getbalance)
            $create(['company' => 1, 'game_user' => 1, 'method' => 1, 'created_at' => -1], [
                'name' => 'partial_txn_byUser_method',
                'partialFilterExpression' => ['method' => ['$in' => ['OPEN', 'SETTLED', 'settlemain']]],
            ]);

            // partial for con_1 / con_2 (รุ่นเก่า OK: $exists+$type+$gt)
            $pfeCon1 = ['$and' => [
                ['con_1' => ['$exists' => true]],
                ['con_1' => ['$type' => 2]],
                ['con_1' => ['$gt' => '']],
            ]];
            $pfeCon2 = ['$and' => [
                ['con_2' => ['$exists' => true]],
                ['con_2' => ['$type' => 2]],
                ['con_2' => ['$gt' => '']],
            ]];

            $create(['company' => 1, 'game_user' => 1, 'method' => 1, 'con_1' => 1, 'created_at' => -1], [
                'name' => 'partial_with_con1_user_method',
                'partialFilterExpression' => $pfeCon1,
            ]);
            $create(['company' => 1, 'game_user' => 1, 'method' => 1, 'con_2' => 1, 'created_at' => -1], [
                'name' => 'partial_with_con2_user_method',
                'partialFilterExpression' => $pfeCon2,
            ]);

            // จับคู่ con1+con2 ภายใต้ user-first
            $create(['company' => 1, 'game_user' => 1, 'method' => 1, 'con_1' => 1, 'con_2' => 1], [
                'name' => 'byCompany_user_method_con1_con2',
            ]);
        }

        // แสดงผล
        $this->line("\nCurrent indexes:");
        foreach ($col->listIndexes() as $i) {
            $this->line('  * ' . $i->getName() . ' => ' . json_encode($i->getKey()));
        }

        $this->info('Reindex PRO (global + user-first) complete ✅');
        $this->warn('หมายเหตุ: index เยอะ = เขียนช้าลงเล็กน้อย เลือกใช้ --skip-global หรือ --skip-user-first ได้ตามโหลดจริง');
        return self::SUCCESS;
    }
}
