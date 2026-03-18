<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLineTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('line_templates', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id');

            // หมวด / scope เช่น register, general, error
            $table->string('category', 50)->default('general')->index();

            // key เช่น register.ask_phone, register.error_phone_invalid
            $table->string('key', 100)->unique();

            // ข้อความ template (รองรับ {username}, {password}, {login_url}, ฯลฯ)
            $table->string('message_type', 10)->default('text')->index();

            // ข้อความ template (รองรับ {username}, {password}, {login_url}, ฯลฯ)
            $table->longText('message');

            // อธิบายว่าใช้ตรงไหนในระบบ (ให้ทีมงานเข้าใจเวลาแก้)
            $table->string('description')->nullable();

            // เปิด/ปิดการใช้งาน template นี้
            // ใช้ชื่อ enabled ให้ตรงกับ LineTemplateService ที่เราเขียนไปแล้ว
            $table->boolean('enabled')->default(true)->index();

            // ใครสร้าง (อ้างไป employees/id หรือ code แล้วแต่ระบบ)
            $table->unsignedBigInteger('created_by')->nullable()->index();

            // ใครเป็นคนแก้ล่าสุด
            $table->unsignedBigInteger('updated_by')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('line_templates');
    }
}
