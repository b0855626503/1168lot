<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_deposit_stats', function (Blueprint $table) {
            $table->bigIncrements('id');

            /**
             * members.code เป็น INT → ใช้ unsignedInteger ให้ตรง
             */
            $table->unsignedInteger('member_code');

            /**
             * จำนวนบิลฝาก "สำเร็จแล้ว"
             */
            $table->unsignedInteger('deposit_success_count')
                ->default(0)
                ->comment('จำนวนบิลฝากที่สำเร็จแล้ว');

            /**
             * ยอดฝากสะสม (เฉพาะบิลสำเร็จ)
             * policy: > 10000
             */
            $table->decimal('deposit_success_sum', 14, 2)
                ->default(0)
                ->comment('ยอดฝากสะสม (สำเร็จแล้วเท่านั้น)');

            /**
             * บันทึกเวลาที่ผ่านเงื่อนไขครั้งแรก
             * once true, always true
             */
            $table->timestamp('legacy_at')
                ->nullable()
                ->comment('ผ่านเงื่อนไขลูกค้าเก่าครั้งแรก');

            $table->timestamps();

            /**
             * 1 member = 1 row
             */
            $table->unique(
                ['member_code'],
                'uk_member_deposit_stats_member_code'
            );

            /**
             * ใช้กรอง legacy เร็ว ๆ
             */
            $table->index(
                ['legacy_at'],
                'idx_member_deposit_stats_legacy_at'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_deposit_stats');
    }
};
