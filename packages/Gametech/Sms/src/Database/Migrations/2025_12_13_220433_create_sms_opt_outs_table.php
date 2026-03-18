<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_opt_outs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('team_id')->nullable()->index(); // ถ้าต้องการ suppression แยกตามเว็บ/ทีม
            $table->string('phone_e164', 20)->index();
            $table->string('phone_raw', 50)->nullable();

            // source: stop_keyword, support, admin, import
            $table->string('source', 30)->default('admin')->index();
            $table->string('reason', 120)->nullable();
            $table->text('note')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->index(); // employee/admin
            $table->timestamp('opted_out_at')->nullable()->index();

            // proof/meta: เช่น inbound message "STOP", ticket id, etc.
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Global unique (ถ้าต้องการให้ทั้งระบบห้ามส่งซ้ำ)
            // ถ้าคุณต้องการแยกตาม team_id ให้เปลี่ยนเป็น unique(team_id, phone_e164)
            $table->unique(['phone_e164'], 'sms_opt_outs_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_opt_outs');
    }
};
