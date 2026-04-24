<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settlement_batches') || Schema::hasColumn('settlement_batches', 'draw_date')) {
            return;
        }

        Schema::table('settlement_batches', function (Blueprint $table): void {
            $table->date('draw_date')->nullable()->after('draw_id')->index();
        });

        if (Schema::hasTable('lotto_draws')) {
            DB::table('settlement_batches')
                ->join('lotto_draws', 'lotto_draws.id', '=', 'settlement_batches.draw_id')
                ->whereNull('settlement_batches.draw_date')
                ->update(['settlement_batches.draw_date' => DB::raw('lotto_draws.draw_date')]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settlement_batches') || ! Schema::hasColumn('settlement_batches', 'draw_date')) {
            return;
        }

        Schema::table('settlement_batches', function (Blueprint $table): void {
            $table->dropIndex(['draw_date']);
            $table->dropColumn('draw_date');
        });
    }
};
