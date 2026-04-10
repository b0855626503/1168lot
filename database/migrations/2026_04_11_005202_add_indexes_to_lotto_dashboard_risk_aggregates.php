<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotto_dashboard_risk_aggregates')) {
            return;
        }

        Schema::table('lotto_dashboard_risk_aggregates', function (Blueprint $table): void {
            $table->index(
                ['web_code', 'summary_date', 'bet_type', 'exposure_total'],
                'idx_lotto_dash_risk_agg_type_exposure'
            );
            $table->index(
                ['web_code', 'summary_date', 'number'],
                'idx_lotto_dash_risk_agg_number'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotto_dashboard_risk_aggregates')) {
            return;
        }

        Schema::table('lotto_dashboard_risk_aggregates', function (Blueprint $table): void {
            $table->dropIndex('idx_lotto_dash_risk_agg_type_exposure');
            $table->dropIndex('idx_lotto_dash_risk_agg_number');
        });
    }
};
