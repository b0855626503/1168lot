<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdraws_seamless', function (Blueprint $table) {
            // เพิ่ม composite index ใหม่ที่เหมาะกับ pattern query
            $table->index(['enable', 'status', 'code', 'date_create'], 'idx_enable_status_code_date');
        });
    }

    public function down(): void
    {
        Schema::table('withdraws_seamless', function (Blueprint $table) {
            $table->dropIndex(['enable', 'status', 'code', 'date_create']);
        });
    }
};

