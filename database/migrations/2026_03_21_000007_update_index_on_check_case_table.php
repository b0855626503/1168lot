<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_case', function (Blueprint $table) {
            $table->index(['date_create', 'bank_code', 'status'], 'idx_date_bank_status');
        });
    }

    public function down(): void
    {
        Schema::table('check_case', function (Blueprint $table) {
            $table->dropIndex(['date_create', 'bank_code', 'status']);
        });
    }
};

