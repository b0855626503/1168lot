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
            if (! Schema::hasColumn('lotto_markets', 'draw_mode')) {
                $table->string('draw_mode', 20)->default('manual')->after('code');
            }

            if (! Schema::hasColumn('lotto_markets', 'auto_open_time')) {
                $table->time('auto_open_time')->nullable()->after('draw_mode');
            }

            if (! Schema::hasColumn('lotto_markets', 'auto_close_time')) {
                $table->time('auto_close_time')->nullable()->after('auto_open_time');
            }

            if (! Schema::hasColumn('lotto_markets', 'auto_result_time')) {
                $table->time('auto_result_time')->nullable()->after('auto_close_time');
            }

            if (! Schema::hasColumn('lotto_markets', 'result_url')) {
                $table->string('result_url')->nullable()->after('auto_result_time');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotto_markets')) {
            return;
        }

        $columns = [
            'draw_mode',
            'auto_open_time',
            'auto_close_time',
            'auto_result_time',
            'result_url',
        ];

        Schema::table('lotto_markets', function (Blueprint $table) use ($columns): void {
            foreach ($columns as $column) {
                if (Schema::hasColumn('lotto_markets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

