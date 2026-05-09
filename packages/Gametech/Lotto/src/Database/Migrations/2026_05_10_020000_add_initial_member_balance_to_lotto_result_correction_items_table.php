<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_result_correction_items')) {
            return;
        }

        if (! Schema::hasColumn('lotto_result_correction_items', 'initial_member_balance')) {
            Schema::table('lotto_result_correction_items', function (Blueprint $table): void {
                $table->decimal('initial_member_balance', 14, 2)->default(0)->after('new_win_amount');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotto_result_correction_items')) {
            return;
        }

        if (Schema::hasColumn('lotto_result_correction_items', 'initial_member_balance')) {
            Schema::table('lotto_result_correction_items', function (Blueprint $table): void {
                $table->dropColumn('initial_member_balance');
            });
        }
    }
};
