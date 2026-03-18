<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembersRemark extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('members_remark')) {
            Schema::create('members_remark', function (Blueprint $table) {
                $table->bigIncrements('code');
                $table->integer('member_code')->default(0);
                $table->integer('emp_code')->default(0);
                $table->string('remark', 191);
                $table->string('ip', 100);
                $table->enum('enable', ['Y', 'N'])->default('Y');
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
        Schema::dropIfExists('members_remark');
    }
}
