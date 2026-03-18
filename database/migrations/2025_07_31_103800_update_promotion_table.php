<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePromotionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->decimal('withdraw_limit_rate', $precision = 10, $scale = 2)->default(0.00)->change();
        });

        Schema::table('games_user_event', function (Blueprint $table) {
            $table->decimal('withdraw_limit_rate', $precision = 10, $scale = 2)->default(0.00)->change();
        });

        Schema::table('games_user', function (Blueprint $table) {
            $table->decimal('withdraw_limit_rate', $precision = 10, $scale = 2)->default(0.00)->change();
        });

        Schema::table('games_user_free', function (Blueprint $table) {
            $table->decimal('withdraw_limit_rate', $precision = 10, $scale = 2)->default(0.00)->change();
        });

        Schema::table('members_promotionlog', function (Blueprint $table) {
            $table->decimal('withdraw_limit_rate', $precision = 10, $scale = 2)->default(0.00)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
