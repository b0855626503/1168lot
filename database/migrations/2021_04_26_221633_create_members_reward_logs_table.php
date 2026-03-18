<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembersRewardLogsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('members_reward_logs')) {
            Schema::create('members_reward_logs', function (Blueprint $table) {
                $table->increments('code');
                $table->integer('member_code')->default(0)->index('member_code');
                $table->integer('reward_code')->default(0)->index('reward_code');
                $table->decimal('point', 10)->default(0.00);
                $table->decimal('point_amount', 10)->default(0.00);
                $table->decimal('point_before', 10)->default(0.00);
                $table->decimal('point_balance', 10)->default(0.00);
                $table->string('ip', 30)->default('');
                $table->string('remark', 191)->default('');
                $table->boolean('approve')->default(0);
                $table->dateTime('date_approve')->nullable();
                $table->string('ip_admin', 20)->nullable();
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
        Schema::drop('members_reward_logs');
    }

}
