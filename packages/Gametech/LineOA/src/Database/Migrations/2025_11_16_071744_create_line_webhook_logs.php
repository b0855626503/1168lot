<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLineWebhookLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {


        Schema::create('line_webhook_logs', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id');

            // OA ไหน (อาจจะหาได้จาก webhook_token ใน URL)
            $table->unsignedBigInteger('line_account_id')->nullable()->index();
            $table->unsignedBigInteger('line_conversation_id');
            $table->unsignedBigInteger('line_contact_id');
            $table->unsignedBigInteger('line_message_id');

            // ประเภท event คร่าว ๆ เช่น message, follow, unfollow, join, leave, postback ฯลฯ
            $table->string('event_type', 100)->nullable()->index();

            // event id จาก LINE (ถ้ามี)
            $table->string('event_id', 100)->nullable()->index();

            // request_id ภายในระบบเราเอง (เอาไว้ correlate กับ log อื่น)
            $table->string('request_id', 100)->nullable()->index();

            // ข้อมูล client
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();

            // header / body ดิบจาก webhook
            $table->json('headers')->nullable();
            $table->longText('body')->nullable();

            // สถานะการประมวลผลภายในระบบเรา
            // เช่น 200/500 จาก process ภายใน (ไม่ใช่ของ LINE)
            $table->unsignedSmallInteger('http_status')->nullable();

            // ประมวลผลสำเร็จไหม (เช่น parse event แล้ว, dispatch job แล้ว ฯลฯ)
            $table->boolean('is_processed')->default(false)->index();
            $table->timestamp('processed_at')->nullable();

            // เก็บ error message กรณีประมวลผลล้มเหลว
            $table->text('error_message')->nullable();

            $table->timestamps();

            // index รวมเผื่อ query trace รายวัน / event
            $table->index(['event_type', 'created_at']);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('line_webhook_logs');
    }
}
