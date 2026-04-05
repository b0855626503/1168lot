<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_tickets') || Schema::hasColumn('lotto_tickets', 'reason')) {
            return;
        }

        Schema::table('lotto_tickets', function (Blueprint $table): void {
            $table->text('reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotto_tickets') || ! Schema::hasColumn('lotto_tickets', 'reason')) {
            return;
        }

        Schema::table('lotto_tickets', function (Blueprint $table): void {
            $table->dropColumn('reason');
        });
    }
};
