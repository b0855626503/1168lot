<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rewards_list', function (Blueprint $table) {

            // ===== Redemption limit rule =====
            // unlimited      : ไม่จำกัด
            // per_reward     : จำกัดจำนวนครั้งต่อ reward (ใช้ limit_per_user)
            // per_period     : จำกัดตามช่วงเวลา (ใช้ limit_per_period + limit_period)
            $table->string('limit_type', 20)
                ->default('unlimited')
                ->after('point_cost')
                ->index()
                ->comment('unlimited | per_reward | per_period');

            // ใช้เมื่อ limit_type = per_period
            $table->string('limit_period', 20)
                ->nullable()
                ->after('limit_type')
                ->index()
                ->comment('day | week | month | event');

            // จำนวนครั้งที่แลกได้ต่อช่วงเวลา
            $table->unsignedInteger('limit_per_period')
                ->nullable()
                ->after('limit_period')
                ->comment('จำนวนครั้งที่แลกได้ต่อช่วงเวลา');

            // ===== Optional: soft guard กันกดซ้ำเร็วมาก =====
            // ถ้ามี cooldown_minutes เดิมอยู่แล้ว อันนี้ไม่จำเป็น
            $table->boolean('strict_limit')
                ->default(false)
                ->after('limit_per_period')
                ->comment('true = บังคับตรวจ limit แบบ strict (ใช้ lock/tx)');
        });
    }

    public function down(): void
    {
        Schema::table('rewards_list', function (Blueprint $table) {
            $table->dropColumn([
                'limit_type',
                'limit_period',
                'limit_per_period',
                'strict_limit',
            ]);
        });
    }
};
