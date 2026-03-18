<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ClientPresence extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_presence', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('client_id', 64);                 // จาก localStorage
            $t->string('mode', 16);                      // 'pwa' | 'web'
            $t->string('display_mode', 32)->nullable();  // 'standalone' | 'browser' | ...
            $t->boolean('sw')->default(false);           // มี SW คุมหน้าไหม
            $t->string('ua')->nullable();
            $t->string('last_path')->nullable();
            $t->timestamp('first_seen_at')->useCurrent();
            $t->timestamp('last_seen_at')->useCurrent();

            $t->unique(['user_id','client_id']);         // 1 client ต่อ 1 ผู้ใช้
            $t->index(['user_id','mode']);
            $t->index('last_seen_at');
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
