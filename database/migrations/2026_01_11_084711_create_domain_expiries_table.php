<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_expiries', function (Blueprint $table) {
            $table->bigIncrements('id');

            // public = หน้าบ้านลูกค้า, admin = หลังบ้าน/ทีมงาน
            $table->string('role', 16)->default('public')->index();

            // เก็บเป็นโดเมนล้วน เช่น kick789.com (ไม่มี https://)
            $table->string('domain', 255);

            // วันหมดอายุโดเมน (Registrar Expiry)
            $table->dateTime('expires_at')->nullable()->index();

            // เวลาเช็คครั้งล่าสุด (กันยิงซ้ำ และใช้ทำ retry policy)
            $table->dateTime('checked_at')->nullable()->index();

            // สถานะการเช็ค: ok | no_data | invalid_domain | rate_limited | error | ...
            $table->string('status', 32)->nullable()->index();

            // แหล่งข้อมูล: rdap | whois
            $table->string('source', 16)->nullable()->index();

            // เก็บ raw response (ควรตัดความยาวตอนบันทึกเพื่อกัน DB โต)
            $table->longText('raw')->nullable();

            $table->timestamps();

            // กันซ้ำ: โดเมนเดียวกันแต่ role ต่างกันได้ (public/admin)
            $table->unique(['role', 'domain'], 'domain_expiries_role_domain_unique');

            // ช่วย query แบบเลือกเฉพาะรายการที่ยังไม่เคยเช็ค/ยังไม่มีวันหมดอายุ
            $table->index(['expires_at', 'checked_at'], 'domain_expiries_expires_checked_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_expiries');
    }
};
