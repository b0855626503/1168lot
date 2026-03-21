<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_payment', function (Blueprint $table) {
            // ลบ index เดิมที่ไม่จำเป็น (ถ้ามี)
            try { $table->dropIndex(['account_code', 'tx_hash']); } catch (\Exception $e) {}
            try { $table->dropIndex(['deposit_status']); } catch (\Exception $e) {}
            // เพิ่ม composite index ใหม่ที่เหมาะกับ pattern query
            $table->index(['enable', 'status', 'date_create', 'value'], 'idx_enable_status_date_value');
        });
    }

    public function down(): void
    {
        Schema::table('bank_payment', function (Blueprint $table) {
            $table->dropIndex(['enable', 'status', 'date_create', 'value']);
            // สามารถเพิ่ม index เดิมกลับได้ถ้าต้องการ
            $table->index(['account_code', 'tx_hash']);
            $table->index(['deposit_status']);
        });
    }
};

