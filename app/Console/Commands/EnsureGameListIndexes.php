<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Gametech\API\Models\GameList;

class EnsureGameListIndexes extends Command
{
    protected $signature = 'mongo:indexes:gamelist {--migrate : drop & recreate ถ้า option ไม่ตรง}';
    protected $description = 'Ensure MongoDB indexes for GameList collection';

    public function handle(): int
    {
        // เช็กว่ามีคอนเนกชัน mongodb ไหม
        if (!is_array(config('database.connections.mongodb'))) {
            $this->error('MongoDB connection "mongodb" not configured.');
            return self::FAILURE;
        }

        // เตรียม collection
        $db = GameList::getConnectionResolver()->connection('mongodb')->getMongoDB();
        $col = $db->selectCollection((new GameList)->getTable());

        $migrate = (bool)$this->option('migrate');

        // helper: normalize partial filter
        $normPartial = function ($expr): ?array {
            if ($expr === null) return null;
            if ($expr instanceof \MongoDB\Model\BSONDocument) $expr = $expr->getArrayCopy();
            elseif ($expr instanceof \stdClass) $expr = (array)$expr;
            if (array_key_exists('enable', $expr)) {
                $v = $expr['enable'];
                if (is_array($v) && array_key_exists('$eq', $v)) $expr['enable'] = (bool)$v['$eq'];
                else $expr['enable'] = (bool)$v;
            }
            ksort($expr);
            return $expr;
        };

        // helper: ensure index (drop/create ถ้า --migrate และ option ต่าง)
        $ensure = function (array $keys, array $options = []) use ($col, $migrate, $normPartial) {
            $wantName = $options['name'] ?? null;
            $wantUnique = (bool)($options['unique'] ?? false);
            $wantPartial = $normPartial($options['partialFilterExpression'] ?? null);

            foreach ($col->listIndexes() as $idx) {
                $existKeys = $idx->getKey();
                $existName = $idx->getName();
                $existUnique = method_exists($idx, 'isUnique') ? (bool)$idx->isUnique() : false;
                $existPartial = method_exists($idx, 'getPartialFilterExpression') ? $normPartial($idx->getPartialFilterExpression()) : null;

                // ชื่อชนแต่ key ต่าง -> ไม่ยุ่ง (กันชนชื่อ)
                if ($wantName && $existName === $wantName && $existKeys !== $keys) {
                    $this->warn("Skip: index name conflicts (different keys): {$wantName}");
                    return;
                }

                if ($existKeys === $keys) {
                    $sameUnique = ($existUnique === $wantUnique);
                    $samePartial = ($existPartial === $wantPartial)
                        || ($wantPartial !== null && $existPartial === ['enable' => true]);

                    if ($sameUnique && $samePartial) {
                        $this->line("OK   : " . ($wantName ?: json_encode($keys)) . " (exists)");
                        return;
                    }

                    if ($migrate) {
                        $this->warn("MIGR : {$existName} -> drop & recreate");
                        $col->dropIndex($existName);
                        $col->createIndex($keys, $options);
                        $this->info("DONE : recreated " . ($wantName ?: json_encode($keys)));
                    } else {
                        $this->warn("DIFF : options differ; use --migrate to fix");
                    }
                    return;
                }
            }

            // ไม่พบ -> สร้างใหม่
            $col->createIndex($keys, $options);
            $this->info("CREATE: " . ($wantName ?: json_encode($keys)));
        };

        // === รายการ index ที่ต้องมี ===

        // 1) unique: product + code
        $ensure(['product' => 1, 'code' => 1], [
            'name' => 'uniq_product_code',
            'unique' => true,
        ]);

        // 2) partial: product, click desc, rank desc เมื่อ enable=true
        $ensure(['product' => 1, 'click' => -1, 'rank' => -1], [
            'name' => 'idx_prod_click_rank_enabled',
            'partialFilterExpression' => ['enable' => true],
        ]);

        // 3) สำหรับ soft-disable/รายงาน
        $ensure(['product' => 1, 'enable' => 1, 'disabled_at' => 1], [
            'name' => 'idx_prod_enable_disabled',
        ]);

        return self::SUCCESS;
    }
}
