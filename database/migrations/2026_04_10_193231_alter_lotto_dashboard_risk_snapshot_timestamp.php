<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('lotto_dashboard_risk_snapshot') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('
            ALTER TABLE `lotto_dashboard_risk_snapshot`
            MODIFY `snapshot_at` TIMESTAMP NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('lotto_dashboard_risk_snapshot') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('
            ALTER TABLE `lotto_dashboard_risk_snapshot`
            MODIFY `snapshot_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ');
    }
};
