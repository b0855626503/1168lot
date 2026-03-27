<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSelectionDebugJsonToLottoResultFetchLogs extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_result_fetch_logs')) {
            return;
        }

        Schema::table('lotto_result_fetch_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('lotto_result_fetch_logs', 'selection_debug_json')) {
                $table->json('selection_debug_json')->nullable()->after('normalized_result_json');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotto_result_fetch_logs')) {
            return;
        }

        Schema::table('lotto_result_fetch_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('lotto_result_fetch_logs', 'selection_debug_json')) {
                $table->dropColumn('selection_debug_json');
            }
        });
    }
}
