<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lotto_dashboard_summary_daily')) {
            Schema::create('lotto_dashboard_summary_daily', function (Blueprint $table): void {
                $table->id();
                $table->date('summary_date');
                $table->string('web_code', 64);

                $table->decimal('total_sales', 18, 2)->default(0);
                $table->decimal('total_payout', 18, 2)->default(0);
                $table->unsignedInteger('total_tickets')->default(0);
                $table->unsignedInteger('total_players')->default(0);
                $table->unsignedInteger('win_tickets')->default(0);
                $table->unsignedInteger('lose_tickets')->default(0);
                $table->unsignedInteger('pending_tickets')->default(0);
                $table->unsignedInteger('settled_tickets')->default(0);
                $table->unsignedInteger('sales_unique_players')->default(0);
                $table->unsignedInteger('settled_unique_players')->default(0);
                $table->unsignedInteger('metric_version')->default(1);
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();

                $table->unique(['summary_date', 'web_code'], 'uk_lotto_dashboard_summary_daily_date_web');
                $table->index(['web_code', 'summary_date'], 'idx_lotto_dashboard_summary_daily_web_date');
            });
        }

        if (!Schema::hasTable('lotto_dashboard_market_summary')) {
            Schema::create('lotto_dashboard_market_summary', function (Blueprint $table): void {
                $table->id();
                $table->date('summary_date');
                $table->string('web_code', 64);
                $table->unsignedBigInteger('market_id');
                $table->unsignedBigInteger('round_id');

                $table->decimal('total_sales', 18, 2)->default(0);
                $table->unsignedInteger('total_tickets')->default(0);
                $table->unsignedInteger('total_players')->default(0);
                $table->decimal('total_payout', 18, 2)->default(0);
                $table->string('status', 32)->default('pending');
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['summary_date', 'web_code', 'market_id', 'round_id'],
                    'uk_lotto_dashboard_market_summary_date_web_market_round'
                );
                $table->index(['web_code', 'summary_date'], 'idx_lotto_dashboard_market_summary_web_date');
                $table->index(['market_id', 'round_id'], 'idx_lotto_dashboard_market_summary_market_round');
            });
        }

        if (!Schema::hasTable('lotto_dashboard_risk_snapshot')) {
            Schema::create('lotto_dashboard_risk_snapshot', function (Blueprint $table): void {
                $table->id();
                $table->string('web_code', 64);
                $table->unsignedBigInteger('market_id');
                $table->unsignedBigInteger('round_id');
                $table->string('bet_type', 64);
                $table->string('number', 32);
                $table->timestamp('snapshot_at');
                $table->decimal('stake_total', 18, 2)->default(0);
                $table->decimal('payout_if_hit', 18, 2)->default(0);
                $table->decimal('liability', 18, 2)->default(0);
                $table->timestamps();

                $table->unique(
                    ['web_code', 'market_id', 'round_id', 'bet_type', 'number', 'snapshot_at'],
                    'uk_lotto_dashboard_risk_snapshot_dimension'
                );
                $table->index(['web_code', 'snapshot_at'], 'idx_lotto_dashboard_risk_snapshot_web_time');
                $table->index(['market_id', 'round_id'], 'idx_lotto_dashboard_risk_snapshot_market_round');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lotto_dashboard_risk_snapshot');
        Schema::dropIfExists('lotto_dashboard_market_summary');
        Schema::dropIfExists('lotto_dashboard_summary_daily');
    }
};

