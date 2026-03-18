<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_campaigns', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Multi-tenant / site scope (ปรับให้เข้ากับโครงของคุณได้)
            $table->unsignedBigInteger('team_id')->nullable()->index(); // หรือ site_id/brand_id
            $table->string('code', 50)->nullable()->unique(); // campaign code อ้างอิงภายนอก (optional)

            // Campaign content
            $table->string('name', 120);
            $table->string('sender_name', 50)->nullable(); // Sender ID หรือ from (แล้วแต่ provider รองรับ)
            $table->text('message'); // ข้อความการตลาด

            // Targeting policy
            // member_all = ส่งสมาชิกทุกคน (ที่ผ่านเงื่อนไข)
            // member_filter = ส่งสมาชิกตาม filter_json
            // upload_only = ส่งจากไฟล์อย่างเดียว
            // mixed = รวม member + upload
            $table->string('audience_mode', 20)->default('member_all')->index();
            $table->json('filter_json')->nullable(); // เก็บเงื่อนไข/segment ของสมาชิก (ถ้ามี)

            // Compliance / safety flags
            $table->boolean('respect_opt_out')->default(true); // กันส่งให้ opt-out เสมอ
            $table->boolean('require_consent')->default(true); // ส่งเฉพาะที่มี consent (สำหรับ member)

            // Scheduling
            $table->timestamp('scheduled_at')->nullable()->index(); // เวลาเริ่มส่ง
            $table->time('window_start')->nullable(); // ช่วงเวลาที่อนุญาตให้ส่ง (เช่น 09:00)
            $table->time('window_end')->nullable();   // (เช่น 20:00)
            $table->string('timezone', 64)->default('Asia/Bangkok');

            // Throttle / retry policy
            $table->unsignedInteger('throttle_per_minute')->default(120); // จำกัด rate ต่อแคมเปญ
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->unsignedInteger('retry_backoff_seconds')->default(30);

            // Provider
            $table->string('provider', 30)->default('vonage')->index();

            // Status
            // draft, scheduled, running, paused, completed, cancelled
            $table->string('status', 20)->default('draft')->index();

            // Summary counters (denormalized เพื่อ report เร็ว)
            $table->unsignedBigInteger('total_recipients')->default(0);
            $table->unsignedBigInteger('queued_count')->default(0);
            $table->unsignedBigInteger('sent_count')->default(0);
            $table->unsignedBigInteger('delivered_count')->default(0);
            $table->unsignedBigInteger('failed_count')->default(0);
            $table->unsignedBigInteger('invalid_count')->default(0);
            $table->unsignedBigInteger('duplicate_count')->default(0);
            $table->unsignedBigInteger('opted_out_count')->default(0);
            $table->unsignedBigInteger('suppressed_count')->default(0);

            // Audit
            $table->unsignedBigInteger('created_by')->nullable()->index(); // employee/admin id
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            // Notes/meta
            $table->string('subject', 180)->nullable(); // หัวข้อภายใน (optional)
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status', 'scheduled_at'], 'sms_campaigns_team_status_sched_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_campaigns');
    }
};
