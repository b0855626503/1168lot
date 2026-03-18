<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMemberEditLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('members_edit_log')) {
            Schema::create('members_edit_log', function (Blueprint $table) {
                $table->integer('code', true);
                $table->integer('emp_code')->default(0);
                $table->string('emp_user', 40);
                $table->string('mode', 100);
                $table->string('menu', 100);
                $table->integer('member_code')->default(0);
                $table->string('member_user', 40);
                $table->string('remark', 255);
                $table->longText('item_before');
                $table->longText('item');
                $table->string('ip', 100);
                $table->enum('enable', array('Y', 'N'))->default('Y');
                $table->string('user_create', 100);
                $table->string('user_update', 100);
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
        //
    }
}
