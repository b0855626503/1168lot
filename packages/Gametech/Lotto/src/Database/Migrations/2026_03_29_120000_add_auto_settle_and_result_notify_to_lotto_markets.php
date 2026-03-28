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
            if (! Schema::hasColumn('lotto_markets', 'auto_settle_on_result')) {
                $table->boolean('auto_settle_on_result')->default(true)->after('result_url');
            }

            if (! Schema::hasColumn('lotto_markets', 'notify_result_telegram')) {
                $table->boolean('notify_result_telegram')->default(true)->after('auto_settle_on_result');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotto_markets')) {
            return;
        }

        Schema::table('lotto_markets', function (Blueprint $table): void {
            if (Schema::hasColumn('lotto_markets', 'notify_result_telegram')) {
                $table->dropColumn('notify_result_telegram');
            }

            if (Schema::hasColumn('lotto_markets', 'auto_settle_on_result')) {
                $table->dropColumn('auto_settle_on_result');
            }
        });
    }
};

