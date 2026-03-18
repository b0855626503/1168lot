<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpinsNewTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('spins_new')) {
            Schema::create('spins_new', function (Blueprint $table) {
                $table->increments('code');
                $table->string('name', 100)->default('');
                $table->string('types', 10)->default('WALLET');
                $table->decimal('amount', 10);
                $table->integer('winloss')->default(0);
                $table->string('spincolor', 20)->default('');
                $table->string('filepic', 100)->default('');
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
        Schema::drop('spins_new');
    }

}
