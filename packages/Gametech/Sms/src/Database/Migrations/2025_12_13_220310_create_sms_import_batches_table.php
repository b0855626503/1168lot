<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_import_batches', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->unsignedBigInteger('campaign_id')->nullable()->index(); // ผูกกับแคมเปญหรืออัปโหลดไว้ก่อนก็ได้

            $table->string('file_name', 255);
            $table->string('file_mime', 120)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('file_sha1', 40)->nullable()->index(); // กันไฟล์เดิมซ้ำแบบหยาบ ๆ
            $table->string('storage_disk', 50)->nullable();
            $table->string('storage_path', 255)->nullable(); // ถ้าเก็บไฟล์ไว้ (optional)

            // Parse config
            $table->string('source_label', 120)->nullable(); // เช่น "Q4 Leads", "Partner list"
            $table->string('phone_column', 50)->nullable();  // ถ้าเป็น CSV/XLSX ระบุชื่อคอลัมน์
            $table->string('country_code', 8)->default('66'); // default ไทย
            $table->boolean('has_header')->default(true);

            // Compliance meta สำหรับไฟล์ภายนอก
            $table->string('consent_basis', 50)->nullable(); // เช่น consent, legitimate_interest (เอาไว้ audit)
            $table->text('consent_note')->nullable();

            // Counters
            $table->unsignedBigInteger('total_rows')->default(0);
            $table->unsignedBigInteger('valid_phones')->default(0);
            $table->unsignedBigInteger('invalid_phones')->default(0);
            $table->unsignedBigInteger('duplicate_phones')->default(0);
            $table->unsignedBigInteger('suppressed_phones')->default(0);

            // Status: uploaded, parsing, ready, failed
            $table->string('status', 20)->default('uploaded')->index();
            $table->text('error_message')->nullable();

            $table->unsignedBigInteger('uploaded_by')->nullable()->index(); // employee/admin id
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'campaign_id', 'status'], 'sms_import_batches_team_campaign_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_import_batches');
    }
};
