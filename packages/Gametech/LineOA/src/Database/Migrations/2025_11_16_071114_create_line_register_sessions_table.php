<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLineRegisterSessionsTable extends Migration
{
    public function up()
    {
        Schema::create('line_register_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('line_contact_id')->index();
            $table->unsignedBigInteger('line_conversation_id')->nullable()->index();

            // waiting / in_progress / completed / cancelled / failed / expired
            $table->string('status', 30)->default('in_progress')->index();

            // step ปัจจุบัน เช่น phone / name / surname / bank / account
            $table->string('current_step', 50)->nullable();

            // เก็บคำตอบระหว่างทาง
            // เช่น { "phone": "...", "name": "...", "surname": "...", "bank_code": "...", "account_no": "..." }
            $table->json('data')->nullable();

            // member ที่ถูกสร้าง (ถ้าสมัครสำเร็จ)
            $table->unsignedBigInteger('member_id')->nullable()->index();

            // เหตุผล error กรณี fail
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['status', 'current_step']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('line_register_sessions');
    }
}
