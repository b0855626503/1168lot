<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotto_ticket_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('lotto_ticket_items', 'discount_amount_at_time')) {
                $table->decimal('discount_amount_at_time', 12, 2)
                    ->default(0)
                    ->after('discount_percent_at_time');
            }

            if (! Schema::hasColumn('lotto_ticket_items', 'payable_amount_at_time')) {
                $table->decimal('payable_amount_at_time', 12, 2)
                    ->default(0)
                    ->after('discount_amount_at_time');
            }

            if (! Schema::hasColumn('lotto_ticket_items', 'potential_win_amount_at_time')) {
                $table->decimal('potential_win_amount_at_time', 12, 2)
                    ->default(0)
                    ->after('payable_amount_at_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lotto_ticket_items', function (Blueprint $table): void {
            if (Schema::hasColumn('lotto_ticket_items', 'potential_win_amount_at_time')) {
                $table->dropColumn('potential_win_amount_at_time');
            }

            if (Schema::hasColumn('lotto_ticket_items', 'payable_amount_at_time')) {
                $table->dropColumn('payable_amount_at_time');
            }

            if (Schema::hasColumn('lotto_ticket_items', 'discount_amount_at_time')) {
                $table->dropColumn('discount_amount_at_time');
            }
        });
    }
};

