<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLimitRateGameUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('games_user', 'withdraw_limit_rate')) {
            Schema::table('games_user', function (Blueprint $table) {
                $table->integer('withdraw_limit_rate')->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('games_user', function (Blueprint $table) {
            $table->dropColumn(['withdraw_limit_rate']);
        });
    }
}
