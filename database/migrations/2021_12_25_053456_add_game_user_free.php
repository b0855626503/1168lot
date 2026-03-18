<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGameUserFree extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumns('games_user_free', ['	bill_code','pro_code','amount','bonus','turnpro','amount_balance','withdraw_limit','withdraw_limit_rate','withdraw_limit_amount'])) {
            Schema::table('games_user_free', function (Blueprint $table) {
                $table->decimal('amount', 10, 2)->default('0.00');
                $table->decimal('bonus', 10, 2)->default('0.00');
                $table->decimal('turnpro', 10, 2)->default('0.00');
                $table->decimal('amount_balance', 10, 2)->default('0.00');
                $table->decimal('withdraw_limit', 10, 2)->default('0.00');
                $table->decimal('withdraw_limit_amount', 10, 2)->default('0.00');
                $table->integer('bill_code')->default(0);
                $table->integer('pro_code')->default(0);
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
        //
    }
}
