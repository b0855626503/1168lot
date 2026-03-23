<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_groups') || ! Schema::hasColumn('lotto_groups', 'description')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE lotto_groups MODIFY description TEXT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotto_groups') || ! Schema::hasColumn('lotto_groups', 'description')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE lotto_groups MODIFY description VARCHAR(255) NULL');
    }
};
