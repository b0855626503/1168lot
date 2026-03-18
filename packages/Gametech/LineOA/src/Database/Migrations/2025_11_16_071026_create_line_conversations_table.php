<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLineConversationsTable extends Migration
{
    public function up()
    {
        Schema::create('line_conversations', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id');

            $table->unsignedBigInteger('line_account_id');
            $table->unsignedBigInteger('line_contact_id');

            // สถานะห้อง: open / closed / blocked ฯลฯ
            $table->string('status')->default('open');

            // สรุปข้อความล่าสุด (เอาไว้ใช้ใน list)
            $table->text('last_message_preview')->nullable();
            $table->timestamp('last_message_at')->nullable();

            // จำนวนข้อความที่ยังไม่อ่านสำหรับทีมงาน
            $table->unsignedInteger('unread_count')->default(0);

            // future use: assign / lock ให้พนักงานคนใดคนหนึ่ง
            $table->unsignedBigInteger('assigned_employee_id')->nullable()->index();
            $table->unsignedBigInteger('locked_by_employee_id')->nullable()->index();

            $table->timestamps();

            // 1 contact ต่อ 1 active conversation (ตามแนวคิดปัจจุบัน)
            $table->index(['line_account_id', 'line_contact_id']);
            $table->index(['status', 'last_message_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('line_conversations');
    }
}
