<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_recipients', function (Blueprint $table) {
            $table->bigIncrements('id');

            // ถ้าโปรเจกต์นี้ 1 เว็บจริง ๆ team_id อาจไม่ต้องใช้
            // แต่คงไว้แบบ nullable ไม่เสียหาย (หรือคุณจะลบออกก็ได้)
            $table->unsignedBigInteger('team_id')->nullable()->index();

            $table->unsignedBigInteger('campaign_id')->index();
            $table->unsignedBigInteger('import_batch_id')->nullable()->index();

            // Source mapping
            // member = มาจากสมาชิก (members.tel)
            // upload = มาจากไฟล์ภายนอก
            $table->string('source_type', 20)->index(); // member|upload
            $table->unsignedBigInteger('source_id')->nullable()->index(); // member_code หรือ id ของแถวไฟล์ (ถ้ามี)

            // Phone
            $table->string('phone_e164', 20)->index(); // เช่น +66812345678
            $table->string('phone_raw', 50)->nullable(); // เก็บ raw เผื่อ debug
            $table->string('country_code', 8)->default('66');

            // Optional personalization fields (การตลาดชอบใส่ชื่อ)
            $table->string('first_name', 80)->nullable();
            $table->string('last_name', 80)->nullable();

            // Compliance snapshot ต่อ recipient (เผื่อข้อมูลเปลี่ยนภายหลัง)
            $table->boolean('has_consent')->nullable(); // สำหรับ member
            $table->timestamp('consent_at')->nullable();
            $table->boolean('is_opted_out')->default(false); // snapshot ตอนสร้าง recipient
            $table->timestamp('opted_out_at')->nullable();

            // Sending status
            // queued, sending, sent, delivered, failed, invalid, duplicate, suppressed, opted_out
            $table->string('status', 20)->default('queued')->index();

            // Provider response
            $table->string('provider', 30)->default('vonage')->index();
            $table->string('provider_message_id', 80)->nullable()->index();

            // DLR fields (ห้ามใช้ ->after ใน CREATE TABLE)
            $table->string('dlr_status_raw', 30)->nullable()->index();
            $table->string('dlr_err_code', 20)->nullable()->index();
            $table->string('dlr_scts', 20)->nullable(); // carrier timestamp
            $table->timestamp('dlr_received_at')->nullable()->index();
            $table->json('dlr_payload')->nullable();

            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->string('error_code', 60)->nullable();
            $table->text('error_message')->nullable();

            // Duplicate control per campaign:
            // สร้าง fingerprint = sha1(campaign_id + phone_e164) แล้ว unique
            $table->char('recipient_fingerprint', 40)->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['campaign_id', 'phone_e164'], 'sms_recipients_campaign_phone_unique');
            $table->index(['campaign_id', 'status'], 'sms_recipients_campaign_status_idx');
            $table->index(['team_id', 'campaign_id', 'status'], 'sms_recipients_team_campaign_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_recipients');
    }
};
