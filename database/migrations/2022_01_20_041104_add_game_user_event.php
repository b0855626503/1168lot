<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGameUserEvent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

            Schema::create('games_user_event', function (Blueprint $table) {
                $table->integer('code', true);
                $table->integer('game_code')->default(0);
                $table->integer('member_code')->default(0);
                $table->string('user_name', 100);
                $table->string('user_pass', 100);
                $table->string('method', 15);
                $table->enum('enable', ['Y', 'N'])->default('Y');
                $table->string('user_create', 100)->default('');
                $table->string('user_update', 100)->default('');
                $table->timestamp('date_create')->nullable();
                $table->timestamp('date_update')->nullable();
                $table->integer('bill_code')->default(0);
                $table->integer('pro_code')->default(0);
                $table->decimal('amount', 10, 2)->default('0.00');
                $table->decimal('bonus', 10, 2)->default('0.00');
                $table->decimal('turnpro', 10, 2)->default('0.00');
                $table->decimal('amount_balance', 10, 2)->default('0.00');
                $table->decimal('withdraw_limit', 10, 2)->default('0.00');
                $table->decimal('withdraw_limit_amount', 10, 2)->default('0.00');
                $table->integer('withdraw_limit_rate')->default(0);
                $table->enum('complete', ['Y', 'N'])->default('N');
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
