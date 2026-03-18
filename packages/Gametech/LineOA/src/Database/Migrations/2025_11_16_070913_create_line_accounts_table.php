<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLineAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('line_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');

            // ชื่อเรียก OA ในระบบ เช่น "Grand789 Main", "Grand789 VIP"
            $table->string('name');

            // LINE Messaging API
            $table->string('channel_id')->nullable();
            $table->string('channel_secret')->nullable();

            // long-lived access token
            $table->text('access_token')->nullable();

            // token สำหรับ map webhook URL เช่น /line-oa/webhook/{webhook_token}
            $table->string('webhook_token')->unique();

            // สถานะ OA ในระบบนี้
            $table->string('status')->default('active'); // active / inactive

            // note เพิ่มเติม เช่น ใช้กับเว็บอะไร OA ไหน
            $table->text('remark')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('line_accounts');
    }
}
