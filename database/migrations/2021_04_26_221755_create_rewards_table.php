<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRewardsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('rewards')) {
            Schema::create('rewards', function (Blueprint $table) {
                $table->increments('code');
                $table->string('name', 100)->default('');
                $table->string('short_details', 199);
                $table->text('details');
                $table->boolean('qty')->default(0);
                $table->decimal('points', 10)->default(0.00);
                $table->string('filepic', 100)->default('');
                $table->enum('active', array('Y', 'N'))->default('Y');
                $table->enum('enable', array('Y', 'N'))->default('Y');
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
        Schema::drop('rewards');
    }

}
