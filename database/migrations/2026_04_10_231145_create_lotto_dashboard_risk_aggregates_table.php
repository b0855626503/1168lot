<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_dashboard_risk_aggregates')) {
            Schema::create('lotto_dashboard_risk_aggregates', function (Blueprint $table): void {
                $table->id();
                $table->string('web_code', 64);
                $table->date('summary_date');
                $table->string('bet_type', 64);
                $table->string('number', 32);
                $table->decimal('stake_total', 18, 2)->default(0);
                $table->decimal('exposure_total', 18, 2)->default(0);
                $table->decimal('liability_total', 18, 2)->default(0);
                $table->unsignedInteger('market_count')->default(0);
                $table->unsignedInteger('round_count')->default(0);
                $table->longText('market_ids_json')->nullable();
                $table->longText('round_ids_json')->nullable();
                $table->timestamp('snapshot_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['web_code', 'summary_date', 'bet_type', 'number'],
                    'uk_lotto_dashboard_risk_aggregates_scope'
                );
                $table->index(['web_code', 'summary_date'], 'idx_lotto_dashboard_risk_aggregates_web_date');
                $table->index(['web_code', 'summary_date', 'exposure_total'], 'idx_lotto_dashboard_risk_aggregates_exposure');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lotto_dashboard_risk_aggregates');
    }
};
