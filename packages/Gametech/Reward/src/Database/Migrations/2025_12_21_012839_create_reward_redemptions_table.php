<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Relation
            $table->unsignedBigInteger('reward_id')->index();
            $table->unsignedInteger('member_id')->index(); // FK -> members.code (int)

            // Snapshot กัน reward เปลี่ยนทีหลังแล้วประวัติเละ
            $table->string('reward_code_snapshot', 80)->nullable()->index();
            $table->string('reward_name_snapshot', 255)->nullable();

            $table->unsignedInteger('point_cost_snapshot')->default(0);

            $table->string('reward_type_snapshot', 50)->index();       // wallet_credit | wallet_gem | external ...
            $table->string('fulfillment_mode_snapshot', 20)->index();  // auto | manual | approval

            $table->decimal('credit_amount_snapshot', 12, 2)->nullable();
            $table->unsignedInteger('gem_amount_snapshot')->nullable();

            $table->json('payload_snapshot')->nullable(); // เงื่อนไข/ข้อมูลติดต่อ/ของนอก ฯลฯ

            // Status lifecycle
            // status: pending | fulfilled | rejected | cancelled
            $table->string('status', 20)->default('pending')->index();

            // User/staff notes
            $table->string('note_user', 1000)->nullable();
            $table->string('note_staff', 1000)->nullable();

            // Contact info (สำหรับ external/manual)
            $table->string('contact_channel', 30)->nullable()->index(); // line | phone | email | other
            $table->string('contact_value', 255)->nullable();

            // Fulfillment timestamps
            $table->dateTime('fulfilled_at')->nullable()->index();
            $table->dateTime('cancelled_at')->nullable()->index();
            $table->dateTime('rejected_at')->nullable()->index();

            // ทีมงานคนไหนเป็นคนจัดการ (FK -> employees.code)
            $table->unsignedInteger('handled_by')->nullable()->index();

            // Idempotency key (กันยิงซ้ำกรณี auto fulfillment)
            $table->string('idempotency_key', 120)->nullable()->unique();

            $table->timestamps();

            // FK: rewards
            $table->foreign('reward_id')
                ->references('id')
                ->on('rewards_list')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // FK: members.code
            $table->foreign('member_id')
                ->references('code')
                ->on('members')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // FK: employees.code (ถ้าพนักงานถูกลบ/ปิดบัญชี ให้เคลียร์คนทำงาน แต่ไม่ลบประวัติ)
            $table->foreign('handled_by')
                ->references('code')
                ->on('employees')
                ->onUpdate('cascade')
                ->onDelete('set null');

            // Composite indexes ใช้งานจริง
            $table->index(['member_id', 'status'], 'reward_redemptions_member_status_idx');
            $table->index(['reward_id', 'status'], 'reward_redemptions_reward_status_idx');
            $table->index(['status', 'created_at'], 'reward_redemptions_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
    }
};
