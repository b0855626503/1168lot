<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('banks') && Schema::hasColumn('banks', 'code')) {
            DB::statement('ALTER TABLE banks MODIFY code INT UNSIGNED NOT NULL AUTO_INCREMENT');
        }

        if (Schema::hasTable('banks_account') && Schema::hasColumn('banks_account', 'code')) {
            DB::statement('ALTER TABLE banks_account MODIFY code INT UNSIGNED NOT NULL AUTO_INCREMENT');
        }
    }

    public function down(): void {}
};
