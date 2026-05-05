<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lotto_dashboard_risk_current')) {
            return;
        }

        Schema::create('lotto_dashboard_risk_current', function (Blueprint $table): void {
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
                ['web_code', 'market_id', 'round_id', 'bet_type', 'number'],
                'uk_lotto_dashboard_risk_current_dimension'
            );
            $table->index(['web_code', 'market_id', 'round_id'], 'idx_lotto_dashboard_risk_current_web_market_round');
            $table->index(['web_code', 'market_id', 'round_id', 'bet_type'], 'idx_lotto_dashboard_risk_current_web_market_round_type');
            $table->index(['web_code', 'updated_at'], 'idx_lotto_dashboard_risk_current_web_updated');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotto_dashboard_risk_current');
    }
};
