<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MongoDB\Collection;

class ReindexGameData extends Command
{
    protected $signature = 'gamedata:reindex
        {--conn=mongodb : ชื่อคอนเนคชัน MongoDB ใน config/database.php}
        {--db= : ชื่อฐานข้อมูล (เว้นว่างให้ดึงจากคอนเนคชัน)}
        {--collection=gamedatas : ชื่อคอลเลกชัน}
        {--drop : ดรอป index เก่าทั้งหมดยกเว้น _id_ ก่อนสร้างใหม่}
        {--with-unique : สร้าง unique index ที่ (productId, username, rid)}
        {--with-tsms : เพิ่มดัชนีสำหรับเรียงด้วย ts_ms เผื่อใช้งานบางหน้า}';

    protected $description = 'สร้างดัชนีสำหรับ gamedatas โดยโฟกัสการค้น/เรียงด้วย created_at และใช้ rid เป็นตัวคั่นลำดับ รองรับ partial filter และ (ออปชัน) unique บนอีเวนต์ multi-state';

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

        if ($this->option('drop')) {
            $this->line('Dropping old indexes...');
            $dropped = 0;
            foreach ($col->listIndexes() as $idx) {
                $name = $idx->getName();
                if ($name === '_id_') continue;
                try {
                    $col->dropIndex($name);
                    $this->line(" - dropped: {$name}");
                    $dropped++;
                } catch (\Throwable $e) {
                    $this->warn(" ! drop fail {$name}: " . $e->getMessage());
                }
            }
            if ($dropped === 0) $this->line(' - ไม่พบ index ให้ดรอป (ยกเว้น _id_)');
        }

        $this->line('Creating indexes...');

        $mk = function (array $keys, array $opts = []) use ($col) {
            $name = $opts['name'] ?? json_encode($keys);
            $col->createIndex($keys, $opts);
            $this->line(" + created: {$name}");
        };

        /* ---------- TTL ---------- */
        $mk(['expireAt' => 1], [
            'name' => 'ttl_expireAt',
            'expireAfterSeconds' => 0,
            // หมายเหตุ: บางเวอร์ชันของ MongoDB ไม่รองรับ partialFilter กับ TTL
            // หากมีข้อผิดพลาด ให้ลบบรรทัด partialFilterExpression ทิ้ง
            'partialFilterExpression' => ['expireAt' => ['$exists' => true]],
        ]);

        /* ---------- ดัชนีหลัก: list ตามผู้ใช้/เกม เรียงเวลาแท้ + คั่นด้วย rid ---------- */
        $mk(['productId' => 1, 'username' => 1, 'created_at' => -1, 'rid' => -1], [
            'name' => 'byProductUser_created_desc',
        ]);

        /* ---------- รายงานตามสถานะเดิมพัน (เติม rid เป็นตัวคั่น) ---------- */
        $mk(['productId' => 1, 'username' => 1, 'betStatus' => 1, 'created_at' => -1, 'rid' => -1], [
            'name' => 'byProductUser_status_created',
        ]);

        /* ---------- ไทม์ไลน์เฉพาะผู้ใช้ (ทุกเกม) ---------- */
        $mk(['username' => 1, 'created_at' => -1, 'rid' => -1], [
            'name' => 'byUser_created_desc',
        ]);

        /* ---------- ค้นธุรกรรมด้วย betId (non-unique + partial) ---------- */
        $pfeBetIdStr = ['$and' => [
            ['productId' => ['$exists' => true]],
            ['username' => ['$exists' => true]],
            ['betId' => ['$exists' => true]],
            ['betId' => ['$type' => 2]],   // string
            ['betId' => ['$gt' => '']],    // not empty
        ]];
        $pfeBetIdNum = ['$and' => [
            ['productId' => ['$exists' => true]],
            ['username' => ['$exists' => true]],
            ['betId' => ['$exists' => true]],
            ['$or' => [
                ['betId' => ['$type' => 16]],  // int32
                ['betId' => ['$type' => 18]],  // int64
                ['betId' => ['$type' => 1]],   // double
                ['betId' => ['$type' => 19]],  // decimal
            ]],
        ]];

        $mk(['productId' => 1, 'username' => 1, 'betId' => 1, 'created_at' => -1, 'rid' => -1], [
            'name' => 'byProductUser_betId_created_str',
            'partialFilterExpression' => $pfeBetIdStr,
        ]);
        $mk(['productId' => 1, 'username' => 1, 'betId' => 1, 'created_at' => -1, 'rid' => -1], [
            'name' => 'byProductUser_betId_created_num',
            'partialFilterExpression' => $pfeBetIdNum,
        ]);

        /* ---------- ค้นธุรกรรมด้วย roundId (non-unique + partial) ---------- */
        $pfeRoundIdStr = ['$and' => [
            ['productId' => ['$exists' => true]],
            ['username' => ['$exists' => true]],
            ['roundId' => ['$exists' => true]],
            ['roundId' => ['$type' => 2]],
            ['roundId' => ['$gt' => '']],
        ]];
        $pfeRoundIdNum = ['$and' => [
            ['productId' => ['$exists' => true]],
            ['username' => ['$exists' => true]],
            ['roundId' => ['$exists' => true]],
            ['$or' => [
                ['roundId' => ['$type' => 16]],
                ['roundId' => ['$type' => 18]],
                ['roundId' => ['$type' => 1]],
                ['roundId' => ['$type' => 19]],
            ]],
        ]];

        $mk(['productId' => 1, 'username' => 1, 'roundId' => 1, 'created_at' => -1, 'rid' => -1], [
            'name' => 'byProductUser_roundId_created_str',
            'partialFilterExpression' => $pfeRoundIdStr,
        ]);
        $mk(['productId' => 1, 'username' => 1, 'roundId' => 1, 'created_at' => -1, 'rid' => -1], [
            'name' => 'byProductUser_roundId_created_num',
            'partialFilterExpression' => $pfeRoundIdNum,
        ]);

        /* ---------- เคสที่มีทั้ง betId และ roundId (กัน planner ต้องสแกนเยอะ) ---------- */
        $pfeBoth = ['$and' => [
            ['betId' => ['$exists' => true]],
            ['roundId' => ['$exists' => true]],
        ]];
        $mk(['productId' => 1, 'username' => 1, 'betId' => 1, 'roundId' => 1, 'created_at' => -1, 'rid' => -1], [
            'name' => 'byProductUser_bet_round_created',
            'partialFilterExpression' => $pfeBoth,
        ]);

        /* ---------- สำหรับ trace/ดีบัก ---------- */
        $mk(['redis_id' => 1], ['name' => 'byRedisId']);

        /* ---------- (ออปชัน) unique ต่อเหตุการณ์ (multi-state) ---------- */
        if ($this->option('with-unique')) {
            try {
                $mk(['productId' => 1, 'username' => 1, 'rid' => 1], [
                    'name' => 'uniq_prod_user_rid',
                    'unique' => true,
                ]);
            } catch (\Throwable $e) {
                $this->warn(' ! unique index uniq_prod_user_rid สร้างไม่สำเร็จ: ' . $e->getMessage());
                $this->warn('   ตรวจสอบเอกสารซ้ำ (productId+username+rid) ก่อนรันใหม่ด้วย --drop หรือแก้ข้อมูลซ้ำให้เรียบร้อย');
            }
        }

        /* ---------- (ออปชัน) เรียงด้วย ts_ms + rid ---------- */
        if ($this->option('with-tsms')) {
            $mk(['productId' => 1, 'username' => 1, 'ts_ms' => -1, 'rid' => -1], [
                'name' => 'byProductUser_ts_desc',
                // ช่วยให้ขนาด index เล็กลงถ้ามีเอกสารบางส่วนไม่มี ts_ms
                'partialFilterExpression' => ['ts_ms' => ['$type' => 18]], // Int64
            ]);
        }

        $this->line("\nCurrent indexes:");
        foreach ($col->listIndexes() as $i) {
            $this->line('  * ' . $i->getName() . ' => ' . json_encode($i->getKey()));
        }

        $this->info('Reindex gamedatas complete ✅');
        return self::SUCCESS;
    }
}
