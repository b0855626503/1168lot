<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLineMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('line_messages', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id');

            $table->unsignedBigInteger('line_conversation_id')->index();
            $table->unsignedBigInteger('line_account_id')->index();
            $table->unsignedBigInteger('line_contact_id')->index();

            // inbound = ลูกค้าส่งมา, outbound = ระบบ/แอดมินส่งออก
            $table->string('direction', 20)->default('inbound'); // inbound / outbound

            // user / agent / bot / system
            $table->string('source', 20)->default('user');

            // text / image / sticker / template / etc.
            $table->string('type', 50)->default('text');

            // messageId จาก LINE (ใช้ reply token / reference)
            $table->string('line_message_id')->nullable()->index();

            // text หลัก (ถ้ามี)
            $table->text('text')->nullable();

            // เก็บ payload เต็มจาก LINE (event.message ฯลฯ)
            $table->json('payload')->nullable();

            // meta สำหรับ UI / logic เพิ่มเติม เช่น { "event": "register_created", "register_session_id": 123 }
            $table->json('meta')->nullable();

            // ถ้าเป็น outbound จากแอดมิน จะบันทึกไว้ว่าใครส่ง
            $table->unsignedBigInteger('sender_employee_id')->nullable()->index();

            // ถ้าเป็น outbound จาก bot rule ใด ๆ
            $table->string('sender_bot_key', 100)->nullable()->index();

            // เวลาที่ข้อความเกิดจริง (อาจต่างจาก created_at ถ้ามี delay)
            $table->timestamp('sent_at')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('line_messages');
    }
}
