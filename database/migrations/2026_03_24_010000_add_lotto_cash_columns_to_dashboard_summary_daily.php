<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dashboard_summary_daily')) {
            return;
        }

        Schema::table('dashboard_summary_daily', function (Blueprint $table): void {
            if (!Schema::hasColumn('dashboard_summary_daily', 'lotto_sales_cash')) {
                $table->decimal('lotto_sales_cash', 18, 2)->default(0)->after('bonus_total_count');
            }

            if (!Schema::hasColumn('dashboard_summary_daily', 'lotto_payout_cash')) {
                $table->decimal('lotto_payout_cash', 18, 2)->default(0)->after('lotto_sales_cash');
            }

            if (!Schema::hasColumn('dashboard_summary_daily', 'lotto_refund_cash')) {
                $table->decimal('lotto_refund_cash', 18, 2)->default(0)->after('lotto_payout_cash');
            }

            if (!Schema::hasColumn('dashboard_summary_daily', 'lotto_net_cash')) {
                $table->decimal('lotto_net_cash', 18, 2)->default(0)->after('lotto_refund_cash');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('dashboard_summary_daily')) {
            return;
        }

        Schema::table('dashboard_summary_daily', function (Blueprint $table): void {
            foreach (['lotto_net_cash', 'lotto_refund_cash', 'lotto_payout_cash', 'lotto_sales_cash'] as $column) {
                if (Schema::hasColumn('dashboard_summary_daily', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

