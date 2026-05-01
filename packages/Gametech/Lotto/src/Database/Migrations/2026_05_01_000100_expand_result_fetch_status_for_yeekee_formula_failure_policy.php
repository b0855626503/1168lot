<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExpandResultFetchStatusForYeekeeFormulaFailurePolicy extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_draws') || ! Schema::hasColumn('lotto_draws', 'result_fetch_status')) {
            return;
        }

        DB::statement('ALTER TABLE lotto_draws MODIFY result_fetch_status VARCHAR(64) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotto_draws') || ! Schema::hasColumn('lotto_draws', 'result_fetch_status')) {
            return;
        }

        DB::statement('ALTER TABLE lotto_draws MODIFY result_fetch_status VARCHAR(32) NULL');
    }
}
