<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_payment', function (Blueprint $table) {
            $table->index(['tx_hash', 'account_code'], 'idx_bp_tx_hash_account_code');
        });
    }

    public function down(): void
    {
        Schema::table('bank_payment', function (Blueprint $table) {
            $table->dropIndex('idx_bp_tx_hash_account_code');
        });
    }
};
