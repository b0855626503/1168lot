<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFailedRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('failed_requests', function (Blueprint $table) {
            $table->id();
            $table->string('company');
            $table->string('game_user');
            $table->string('url');
            $table->string('method');
            $table->json('headers')->nullable();
            $table->json('body')->nullable();
            $table->integer('status');
            $table->longText('response')->nullable();
            $table->timestamps();
        });

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
