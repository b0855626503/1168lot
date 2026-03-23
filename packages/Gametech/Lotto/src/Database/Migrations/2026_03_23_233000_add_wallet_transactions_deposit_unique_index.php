<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table = 'wallet_transactions';
    private string $index = 'wallet_txn_member_dir_ref_unique';

    public function up(): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        // Keep oldest row per logical transaction key before adding unique index.
        DB::statement("
            DELETE w1 FROM {$this->table} w1
            INNER JOIN {$this->table} w2
                ON w1.member_id = w2.member_id
               AND w1.direction = w2.direction
               AND w1.ref_type = w2.ref_type
               AND IFNULL(w1.ref_id, 0) = IFNULL(w2.ref_id, 0)
               AND w1.id > w2.id
        ");

        if ($this->hasIndex($this->table, $this->index)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            $table->unique(
                ['member_id', 'direction', 'ref_type', 'ref_id'],
                $this->index
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        if (! $this->hasIndex($this->table, $this->index)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            $table->dropUnique($this->index);
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();
        $result = DB::selectOne(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $indexName]
        );

        return $result !== null;
    }
};

