<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settlement_batches')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE `settlement_batches` 
             MODIFY `mode` ENUM('settlement','backfill','replay','result_correction') 
             NOT NULL DEFAULT 'settlement'"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('settlement_batches')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('settlement_batches')
            ->where('mode', 'result_correction')
            ->update(['mode' => 'settlement']);

        DB::statement(
            "ALTER TABLE `settlement_batches` 
             MODIFY `mode` ENUM('settlement','backfill','replay') 
             NOT NULL DEFAULT 'settlement'"
        );
    }
};
