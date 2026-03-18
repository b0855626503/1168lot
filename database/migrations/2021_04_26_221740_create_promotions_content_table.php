<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionsContentTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('promotions_content')) {
            Schema::create('promotions_content', function (Blueprint $table) {
                $table->increments('code');
                $table->string('name_th', 100)->default('');
                $table->integer('sort')->default(0);
                $table->text('content');
                $table->string('filepic', 191)->default('');
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
        Schema::drop('promotions_content');
    }

}
