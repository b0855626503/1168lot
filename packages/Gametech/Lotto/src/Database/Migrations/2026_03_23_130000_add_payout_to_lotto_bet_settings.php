<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lotto_market_bet_settings', function (Blueprint $table) {
            $table->decimal('payout', 8, 2)->default(0)->after('bet_type');
        });

        Schema::table('lotto_draw_bet_settings', function (Blueprint $table) {
            $table->decimal('payout', 8, 2)->default(0)->after('bet_type');
        });
    }

    public function down()
    {
        Schema::table('lotto_draw_bet_settings', function (Blueprint $table) {
            $table->dropColumn('payout');
        });

        Schema::table('lotto_market_bet_settings', function (Blueprint $table) {
            $table->dropColumn('payout');
        });
    }
};

