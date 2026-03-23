<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lotto_dashboard_bet_type_summary_daily')) {
            Schema::create('lotto_dashboard_bet_type_summary_daily', function (Blueprint $table): void {
                $table->id();
                $table->date('summary_date');
                $table->string('bet_type', 64);
                $table->unsignedInteger('item_count')->default(0);
                $table->decimal('total_amount', 18, 2)->default(0);
                $table->unsignedInteger('unique_players')->default(0);
                $table->timestamps();

                $table->unique(['summary_date', 'bet_type'], 'uk_lotto_dash_bt_daily_date_type');
                $table->index(['summary_date'], 'idx_lotto_dash_bt_daily_date');
            });
        }

        if (!Schema::hasTable('lotto_dashboard_bet_type_number_daily')) {
            Schema::create('lotto_dashboard_bet_type_number_daily', function (Blueprint $table): void {
                $table->id();
                $table->date('summary_date');
                $table->string('bet_type', 64);
                $table->string('number', 64);
                $table->unsignedInteger('item_count')->default(0);
                $table->decimal('total_amount', 18, 2)->default(0);
                $table->timestamps();

                $table->unique(['summary_date', 'bet_type', 'number'], 'uk_lotto_dash_btn_daily_date_type_number');
                $table->index(['summary_date', 'bet_type'], 'idx_lotto_dash_btn_daily_date_type');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lotto_dashboard_bet_type_number_daily');
        Schema::dropIfExists('lotto_dashboard_bet_type_summary_daily');
    }
};
