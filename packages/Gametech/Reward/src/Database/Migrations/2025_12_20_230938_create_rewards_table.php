<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards_list', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Identity / display
            $table->string('code', 80)->unique();               // อ้างอิง/ล็อก/ออกรายงาน
            $table->string('name', 255);
            $table->string('slug', 255)->nullable()->index();
            $table->text('description')->nullable();
            $table->string('image', 500)->nullable();
            $table->json('images')->nullable();

            // Type & fulfillment
            // reward_type: wallet_credit | wallet_gem | external | coupon | custom
            $table->string('reward_type', 50)->default('wallet_credit')->index();

            // fulfillment_mode: auto | manual | approval
            $table->string('fulfillment_mode', 20)->default('auto')->index();

            // auto_claim: แลกแล้วรับทันที (โดยทั่วไป auto=true)
            $table->boolean('auto_claim')->default(true);

            // require_staff_contact: เคสของนอก/ต้องติดต่อ (manual)
            $table->boolean('require_staff_contact')->default(false);

            // Costs & limits
            $table->unsignedInteger('point_cost')->default(0)->index();
            $table->unsignedInteger('limit_per_user')->nullable(); // จำกัดต่อคน (รวมตลอดช่วงเวลา reward นี้)
            $table->unsignedInteger('limit_total')->nullable();    // จำกัดจำนวนรวม
            $table->unsignedInteger('cooldown_minutes')->nullable(); // กันกดรัว/แลกซ้ำ

            // Availability
            $table->dateTime('start_at')->nullable()->index();
            $table->dateTime('end_at')->nullable()->index();
            $table->string('timezone', 64)->default('Asia/Bangkok');

            // Stock
            $table->boolean('stock_unlimited')->default(true)->index();
            $table->unsignedInteger('stock')->nullable();          // ใช้เมื่อ stock_unlimited=false
            $table->unsignedInteger('reserved_stock')->default(0); // กัน race ตอนจอง/ตัดสต๊อก
            $table->boolean('auto_disable_when_out_of_stock')->default(true);

            // Amounts (หลัก ๆ)
            $table->decimal('credit_amount', 12, 2)->nullable()->index(); // เครดิตเข้าเว็บ
            $table->unsignedInteger('gem_amount')->nullable()->index();   // เพชร/หน่วยเป็นจำนวนเต็ม

            // Extra config / external info
            $table->json('payload')->nullable();

            // Flags / sorting
            // status: draft | active | inactive | archived
            $table->string('status', 20)->default('draft')->index();
            $table->integer('priority')->default(0)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_hidden')->default(false)->index();

            // Optional linkage
            $table->unsignedBigInteger('campaign_id')->nullable()->index();
            $table->unsignedBigInteger('event_id')->nullable()->index();
            $table->json('tags')->nullable();

            // Audit
            $table->unsignedInteger('created_by')->nullable()->index(); // ถ้าคุณเก็บ employee.code เป็น int แนะนำ unsignedInteger
            $table->unsignedInteger('updated_by')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            // Helpful composite indexes
            $table->index(['status', 'start_at', 'end_at'], 'rewards_status_time_idx');
            $table->index(['reward_type', 'fulfillment_mode'], 'rewards_type_fulfillment_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards_list');
    }
};
