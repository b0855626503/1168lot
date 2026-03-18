<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserUfabet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('users_ufabet')) {
            Schema::create('users_ufabet', function (Blueprint $table) {
                $table->integer('code', true);
                $table->integer('batch_code')->default(0)->index('batch_code');
                $table->string('user_name', 100)->default('');
                $table->integer('member_code')->default(0);
                $table->enum('use_account', ['Y', 'N'])->default('N');
                $table->enum('freecredit', ['Y', 'N'])->default('N');
                $table->enum('enable', ['Y', 'N'])->default('Y');
                $table->string('ip', 100)->default('');
                $table->timestamp('date_join')->nullable();
                $table->string('user_create', 100)->default('');
                $table->timestamp('date_create')->nullable();
                $table->string('user_update', 100)->default('');
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
        Schema::dropIfExists('users_ufabet');
    }
}
