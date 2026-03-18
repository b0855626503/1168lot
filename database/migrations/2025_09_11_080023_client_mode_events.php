<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ClientModeEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('client_mode_events');
        Schema::create('client_mode_events', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('mode', 16);             // 'pwa' | 'web'
            $t->string('name', 64);             // ชื่อเหตุการณ์
            $t->json('props')->nullable();      // รายละเอียดเสริม (ไม่ใส่ PII)
            $t->string('client_id', 64)->nullable();
            $t->string('ua')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();

            $t->index(['user_id','mode','created_at']);
            $t->index('name');
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
