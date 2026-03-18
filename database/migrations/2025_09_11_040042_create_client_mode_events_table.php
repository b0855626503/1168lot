<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientModeEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_mode_events', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('session_id', 100)->index();
            $t->enum('mode', ['browser','pwa'])->index();
            $t->string('display_mode', 50)->nullable()->index(); // standalone/fullscreen/...
            $t->string('reason', 50)->nullable(); // init, change, appinstalled
            $t->string('url', 2048)->nullable();
            $t->string('ua', 1024)->nullable();
            $t->boolean('pwa_installed_hint')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_mode_events');
    }
}
