<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCheckin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('checkins')) {
            Schema::create('checkins', function (Blueprint $table) {
                $table->increments('code');
                $table->string('name', 191);
                $table->date('date_start');
                $table->date('date_stop');
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
        Schema::dropIfExists('checkins');
    }
}
