<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoggerUserActivityTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('logger_user_activity')) {
            Schema::create('logger_user_activity', function (Blueprint $table) {
                $table->increments('id');
                $table->text('description');
                $table->text('details')->nullable();
                $table->string('userType', 191);
                $table->integer('userId')->nullable();
                $table->text('route')->nullable();
                $table->string('ipAddress', 45)->nullable();
                $table->text('userAgent')->nullable();
                $table->string('locale', 191)->nullable();
                $table->text('referer')->nullable();
                $table->string('methodType', 191)->nullable();
                $table->timestamps(10);
                $table->softDeletes();
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
        Schema::drop('logger_user_activity');
    }

}
