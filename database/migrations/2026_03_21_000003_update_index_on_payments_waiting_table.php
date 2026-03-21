<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments_waiting', function (Blueprint $table) {
            $table->index(['enable', 'confirm', 'date_create'], 'idx_enable_confirm_date');
        });
    }

    public function down(): void
    {
        Schema::table('payments_waiting', function (Blueprint $table) {
            $table->dropIndex(['enable', 'confirm', 'date_create']);
        });
    }
};

