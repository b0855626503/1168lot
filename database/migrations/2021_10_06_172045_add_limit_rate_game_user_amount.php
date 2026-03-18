<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLimitRateGameUserAmount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('games_user', 'withdraw_limit_amount')) {
            Schema::table('games_user', function (Blueprint $table) {
                $table->decimal('withdraw_limit_amount', 10, 2)->default(0.00);
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
            $table->dropColumn(['withdraw_limit_amount']);
        });
    }
}
