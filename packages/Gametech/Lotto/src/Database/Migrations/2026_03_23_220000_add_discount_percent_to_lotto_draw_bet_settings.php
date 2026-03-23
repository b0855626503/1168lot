<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lotto_draw_bet_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('lotto_draw_bet_settings', 'discount_percent')) {
                $table->decimal('discount_percent', 5, 2)->default(0)->after('payout');
            }
        });
    }

    public function down()
    {
        Schema::table('lotto_draw_bet_settings', function (Blueprint $table) {
            if (Schema::hasColumn('lotto_draw_bet_settings', 'discount_percent')) {
                $table->dropColumn('discount_percent');
            }
        });
    }
};
