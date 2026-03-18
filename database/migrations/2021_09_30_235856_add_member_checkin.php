<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMemberCheckin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('members_checkin')) {
            Schema::create('members_checkin', function (Blueprint $table) {
                $table->increments('code');
                $table->date('date_checkin');
                $table->integer('check_code')->default(0);
                $table->integer('member_code')->default(0);
                $table->ipAddress('ip');
                $table->enum('enable', ['Y', 'N'])->default('Y');
                $table->string('user_create', 100)->default('');
                $table->string('user_update', 100)->default('');
                $table->timestamp('date_create')->nullable();
                $table->timestamp('date_update')->nullable();
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
        Schema::dropIfExists('members_checkin');
    }
}
