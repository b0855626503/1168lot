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
            if (! Schema::hasColumn('lotto_markets', 'auto_refund_on_no_result')) {
                $table->boolean('auto_refund_on_no_result')->default(false)->after('auto_settle_on_result');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotto_markets')) {
            return;
        }

        Schema::table('lotto_markets', function (Blueprint $table): void {
            if (Schema::hasColumn('lotto_markets', 'auto_refund_on_no_result')) {
                $table->dropColumn('auto_refund_on_no_result');
            }
        });
    }
};
