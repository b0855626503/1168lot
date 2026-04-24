<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_markets')) {
            return;
        }

        Schema::table('lotto_markets', function (Blueprint $table): void {
            if (! Schema::hasColumn('lotto_markets', 'draw_schedule_type')) {
                $table->string('draw_schedule_type', 20)->nullable()->after('draw_mode');
            }

            if (! Schema::hasColumn('lotto_markets', 'draw_days')) {
                $table->json('draw_days')->nullable()->after('draw_schedule_type');
            }

            if (! Schema::hasColumn('lotto_markets', 'draw_dates')) {
                $table->json('draw_dates')->nullable()->after('draw_days');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotto_markets')) {
            return;
        }

        Schema::table('lotto_markets', function (Blueprint $table): void {
            if (Schema::hasColumn('lotto_markets', 'draw_dates')) {
                $table->dropColumn('draw_dates');
            }

            if (Schema::hasColumn('lotto_markets', 'draw_days')) {
                $table->dropColumn('draw_days');
            }

            if (Schema::hasColumn('lotto_markets', 'draw_schedule_type')) {
                $table->dropColumn('draw_schedule_type');
            }
        });
    }
};
