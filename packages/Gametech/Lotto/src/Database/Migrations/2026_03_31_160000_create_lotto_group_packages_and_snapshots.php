<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_group_packages')) {
            Schema::create('lotto_group_packages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('group_id')->constrained('lotto_groups')->onDelete('cascade');
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['group_id', 'is_active']);
                $table->unique(['group_id', 'name']);
            });
        }

        if (! Schema::hasTable('lotto_group_package_bet_settings')) {
            Schema::create('lotto_group_package_bet_settings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('package_id')->constrained('lotto_group_packages')->onDelete('cascade');
                $table->string('bet_type');
                $table->decimal('payout', 8, 2);
                $table->decimal('discount_percent', 5, 2)->default(0);
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();

                $table->unique(['package_id', 'bet_type']);
                $table->index(['package_id', 'is_enabled']);
            });
        }

        if (Schema::hasTable('lotto_ticket_items')) {
            Schema::table('lotto_ticket_items', function (Blueprint $table): void {
                if (! Schema::hasColumn('lotto_ticket_items', 'package_id_at_time')) {
                    $table->unsignedBigInteger('package_id_at_time')->nullable()->after('amount');
                    $table->index('package_id_at_time');
                }

                if (! Schema::hasColumn('lotto_ticket_items', 'package_name_at_time')) {
                    $table->string('package_name_at_time')->nullable()->after('package_id_at_time');
                }

                if (! Schema::hasColumn('lotto_ticket_items', 'calculated_values_at_bet_time')) {
                    $table->json('calculated_values_at_bet_time')->nullable()->after('potential_win_amount_at_time');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lotto_ticket_items')) {
            Schema::table('lotto_ticket_items', function (Blueprint $table): void {
                if (Schema::hasColumn('lotto_ticket_items', 'calculated_values_at_bet_time')) {
                    $table->dropColumn('calculated_values_at_bet_time');
                }

                if (Schema::hasColumn('lotto_ticket_items', 'package_name_at_time')) {
                    $table->dropColumn('package_name_at_time');
                }

                if (Schema::hasColumn('lotto_ticket_items', 'package_id_at_time')) {
                    $table->dropIndex(['package_id_at_time']);
                    $table->dropColumn('package_id_at_time');
                }
            });
        }

        Schema::dropIfExists('lotto_group_package_bet_settings');
        Schema::dropIfExists('lotto_group_packages');
    }
};
