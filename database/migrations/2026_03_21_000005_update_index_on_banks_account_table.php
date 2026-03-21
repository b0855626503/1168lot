<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banks_account', function (Blueprint $table) {
            $table->index(['bank_type', 'enable'], 'idx_bank_type_enable');
        });
    }

    public function down(): void
    {
        Schema::table('banks_account', function (Blueprint $table) {
            $table->dropIndex(['bank_type', 'enable']);
        });
    }
};

