<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lotto_dashboard_risk_snapshot_archive')) {
            return;
        }

        Schema::create('lotto_dashboard_risk_snapshot_archive', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('web_code', 64);
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('round_id');
            $table->string('bet_type', 64);
            $table->string('number', 32);
            $table->timestamp('snapshot_at');
            $table->decimal('stake_total', 18, 2)->default(0);
            $table->decimal('payout_if_hit', 18, 2)->default(0);
            $table->decimal('liability', 18, 2)->default(0);
            $table->timestamp('archived_at');
            $table->timestamps();

            $table->index(['snapshot_at', 'id'], 'idx_lotto_dash_risk_snap_arch_snapshot_id');
            $table->index(
                ['web_code', 'market_id', 'round_id', 'bet_type', 'number', 'snapshot_at'],
                'idx_lotto_dash_risk_snap_arch_audit_lookup'
            );
            $table->index(['round_id', 'snapshot_at'], 'idx_lotto_dash_risk_snap_arch_round_snapshot');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `lotto_dashboard_risk_snapshot_archive` COMMENT = "Cold storage for risk snapshot archive path"');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lotto_dashboard_risk_snapshot_archive');
    }
};
