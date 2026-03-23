<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lotto_tickets')) {
            Schema::table('lotto_tickets', function (Blueprint $table): void {
                if (!Schema::hasColumn('lotto_tickets', 'bet_confirmed_at')) {
                    $table->dateTime('bet_confirmed_at')->nullable()->after('draw_id');
                }
            });

            Schema::table('lotto_tickets', function (Blueprint $table): void {
                if (Schema::hasColumn('lotto_tickets', 'bet_confirmed_at')) {
                    $table->index(['bet_confirmed_at', 'id'], 'idx_lotto_tickets_confirmed_id');
                    $table->index(['status', 'bet_confirmed_at', 'id'], 'idx_lotto_tickets_status_confirmed_id');
                }
            });
        }

        if (Schema::hasTable('lotto_ticket_items')) {
            Schema::table('lotto_ticket_items', function (Blueprint $table): void {
                $table->index(['ticket_id', 'bet_type', 'number'], 'idx_lotto_ticket_items_ticket_type_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lotto_ticket_items')) {
            Schema::table('lotto_ticket_items', function (Blueprint $table): void {
                $table->dropIndex('idx_lotto_ticket_items_ticket_type_number');
            });
        }

        if (Schema::hasTable('lotto_tickets')) {
            Schema::table('lotto_tickets', function (Blueprint $table): void {
                $table->dropIndex('idx_lotto_tickets_status_confirmed_id');
                $table->dropIndex('idx_lotto_tickets_confirmed_id');

                if (Schema::hasColumn('lotto_tickets', 'bet_confirmed_at')) {
                    $table->dropColumn('bet_confirmed_at');
                }
            });
        }
    }
};
