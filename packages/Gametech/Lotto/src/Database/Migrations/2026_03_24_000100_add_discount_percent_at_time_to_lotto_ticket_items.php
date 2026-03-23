<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotto_ticket_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('lotto_ticket_items', 'discount_percent_at_time')) {
                $table->decimal('discount_percent_at_time', 5, 2)
                    ->default(0)
                    ->after('payout_at_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lotto_ticket_items', function (Blueprint $table): void {
            if (Schema::hasColumn('lotto_ticket_items', 'discount_percent_at_time')) {
                $table->dropColumn('discount_percent_at_time');
            }
        });
    }
};

