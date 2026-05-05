<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('lotto_dashboard_risk_snapshot')) {
            if (! $this->indexExists('lotto_dashboard_risk_snapshot', 'idx_lotto_dash_risk_snap_snapshot_id')) {
                Schema::table('lotto_dashboard_risk_snapshot', function (Blueprint $table): void {
                    $table->index(['snapshot_at', 'id'], 'idx_lotto_dash_risk_snap_snapshot_id');
                });
            }

            if (! $this->indexExists('lotto_dashboard_risk_snapshot', 'idx_lotto_dash_risk_snap_round_snapshot_key')) {
                Schema::table('lotto_dashboard_risk_snapshot', function (Blueprint $table): void {
                    $table->index(
                        ['round_id', 'snapshot_at', 'web_code', 'market_id', 'bet_type', 'number'],
                        'idx_lotto_dash_risk_snap_round_snapshot_key'
                    );
                });
            }
        }

        if (Schema::hasTable('lotto_dashboard_risk_current')) {
            if (! $this->indexExists('lotto_dashboard_risk_current', 'idx_lotto_dash_risk_cur_web_round_market')) {
                Schema::table('lotto_dashboard_risk_current', function (Blueprint $table): void {
                    $table->index(['web_code', 'round_id', 'market_id'], 'idx_lotto_dash_risk_cur_web_round_market');
                });
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('lotto_dashboard_risk_snapshot')) {
            if ($this->indexExists('lotto_dashboard_risk_snapshot', 'idx_lotto_dash_risk_snap_snapshot_id')) {
                Schema::table('lotto_dashboard_risk_snapshot', function (Blueprint $table): void {
                    $table->dropIndex('idx_lotto_dash_risk_snap_snapshot_id');
                });
            }

            if ($this->indexExists('lotto_dashboard_risk_snapshot', 'idx_lotto_dash_risk_snap_round_snapshot_key')) {
                Schema::table('lotto_dashboard_risk_snapshot', function (Blueprint $table): void {
                    $table->dropIndex('idx_lotto_dash_risk_snap_round_snapshot_key');
                });
            }
        }

        if (Schema::hasTable('lotto_dashboard_risk_current')) {
            if ($this->indexExists('lotto_dashboard_risk_current', 'idx_lotto_dash_risk_cur_web_round_market')) {
                Schema::table('lotto_dashboard_risk_current', function (Blueprint $table): void {
                    $table->dropIndex('idx_lotto_dash_risk_cur_web_round_market');
                });
            }
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
