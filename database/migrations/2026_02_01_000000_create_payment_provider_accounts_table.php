<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_provider_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Provider identity
            $table->string('provider', 50);              // ex: smkpay
            $table->unsignedBigInteger('member_code');   // อิงระบบเก่า

            // Provider IDs (SMK)
            $table->string('customer_id', 64)->nullable();
            $table->string('customer_account_id', 64)->nullable();

            // Lookup keys (กันซ้ำ + ใช้ค้นหา)
            // แนะนำให้ account_identifier = member_code (string) เพื่อไม่เปลี่ยน
            $table->string('account_identifier', 64);
            $table->string('account_platform', 32);      // ex: SCB/KBANK ตาม SMK
            $table->string('currency_code', 10)->default('THB');

            // Profile ที่ sync ได้
            $table->string('name', 150)->nullable();
            $table->string('phone_number', 30)->nullable(); // เก็บแบบ 085... (เลขล้วนก็ได้ตาม policy ทีม)

            // Bank info (ตามที่ต้องการเก็บ)
            $table->integer('bank_code')->nullable();        // ผูก banks.code
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_name', 150)->nullable();

            // Sync/Trace
            $table->string('sync_hash', 64)->nullable();      // fingerprint เทียบกับ members
            $table->timestamp('last_synced_at')->nullable();
            $table->json('meta')->nullable();

            // Legacy timestamps (เหมือน check_case)
            $table->timestamp('date_create')->nullable();
            $table->timestamp('date_update')->nullable();

            // Indexes
            $table->unique(
                ['provider', 'account_identifier', 'account_platform', 'currency_code'],
                'uniq_provider_identifier_platform_currency'
            );

            $table->index(['provider', 'member_code'], 'idx_provider_member_code');
            $table->index(['phone_number'], 'idx_phone_number');
            $table->index(['customer_id'], 'idx_customer_id');
            $table->index(['customer_account_id'], 'idx_customer_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_provider_accounts');
    }
};
