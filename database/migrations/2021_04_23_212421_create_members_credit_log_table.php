<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembersCreditLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('members_credit_log')) {


            Schema::create('members_credit_log', function (Blueprint $table) {
                $table->increments('code');
                $table->integer('member_code')->default(0)->index('member_code');
                $table->integer('gameuser_code')->default(0);
                $table->integer('game_code')->default(0);
                $table->integer('bank_code')->default(0);
                $table->integer('pro_code')->default(0);
                $table->enum('credit_type', array('D', 'W'))->default('D');
                $table->integer('refer_code')->default(0);
                $table->string('refer_table', 20);
                $table->decimal('amount', 10, 0)->default(0);
                $table->decimal('bonus', 10)->default(0.00);
                $table->decimal('total', 10)->default(0.00);
                $table->decimal('balance_before', 10)->default(0.00);
                $table->decimal('balance_after', 10)->default(0.00);
                $table->decimal('credit', 10)->default(0.00);
                $table->decimal('credit_bonus', 10)->default(0.00);
                $table->decimal('credit_total', 10)->default(0.00);
                $table->decimal('credit_after', 10)->default(0.00);
                $table->decimal('credit_amount', 10)->default(0.00);
                $table->decimal('credit_before', 10)->default(0.00);
                $table->decimal('credit_balance', 10)->default(0.00);
                $table->string('ip', 30)->default('');
                $table->enum('auto', array('Y', 'N'))->default('N');
                $table->string('remark', 191)->default('');
                $table->string('kind', 10)->default('');
                $table->enum('enable', array('Y', 'N'))->default('Y');
                $table->integer('emp_code')->default(0)->index('emp_code');
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
        Schema::drop('members_credit_log');
    }

}
