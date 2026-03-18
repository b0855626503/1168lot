<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembersPromotionlogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('members_promotionlog')) {
            Schema::create('members_promotionlog', function (Blueprint $table) {
                $table->integer('code')->unsigned()->primary();
                $table->date('date_start')->nullable();
                $table->integer('bill_code')->default(0)->index('bill_code');
                $table->integer('game_code')->default(0);
                $table->string('game_name', 100);
                $table->integer('member_code')->default(0)->index('member_code');
                $table->integer('gameuser_code')->default(0)->index('gameuser_code');
                $table->integer('pro_code')->default(0);
                $table->string('pro_name', 191);
                $table->decimal('turnpro', 10)->default(0.00);
                $table->decimal('amount', 10)->default(0.00);
                $table->decimal('bonus', 10)->default(0.00);
                $table->decimal('amount_balance', 10)->default(0.00);
                $table->decimal('withdraw_limit', 10)->default(0.00);
                $table->enum('complete', array('Y', 'N'))->default('N');
                $table->enum('enable', array('Y', 'N'))->default('Y');
                $table->integer('emp_code')->default(0);
                $table->string('user_create', 100)->default('');
                $table->string('user_update', 100)->default('');
                $table->timestamp('date_create', 0)->nullable(true);
                $table->timestamp('date_update', 0)->nullable(true);

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
        Schema::drop('members_promotionlog');
    }

}
