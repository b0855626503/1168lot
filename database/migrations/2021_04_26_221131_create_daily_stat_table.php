<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyStatTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('daily_stat')) {
            Schema::create('daily_stat', function (Blueprint $table) {
                $table->increments('code');
                $table->date('date')->nullable();
                $table->integer('member_all')->default(0);
                $table->integer('member_new')->default(0);
                $table->integer('member_new_refill')->default(0);
                $table->integer('member_all_refill')->default(0);
                $table->integer('deposit_count')->default(0);
                $table->decimal('deposit_sum', 10)->default(0.00);
                $table->integer('withdraw_count')->default(0);
                $table->decimal('withdraw_sum', 10)->default(0.00);
                $table->text('member_new_list')->nullable();
                $table->text('member_new_refill_list')->nullable();
                $table->decimal('setwallet_d_sum', 10)->default(0.00);
                $table->decimal('setwallet_w_sum', 10)->default(0.00);
                $table->timestamps(10);
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
        Schema::drop('daily_stat');
    }

}
