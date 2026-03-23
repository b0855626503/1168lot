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

        Schema::table('dashboard_summary_daily', function (Blueprint $table) {
            if (!Schema::hasColumn('dashboard_summary_daily', 'withdraw_main_total_amount')) {
                $table->decimal('withdraw_main_total_amount', 18, 2)->default(0)->after('deposit_deleted_users');
            }
            if (!Schema::hasColumn('dashboard_summary_daily', 'withdraw_main_total_count')) {
                $table->unsignedInteger('withdraw_main_total_count')->default(0)->after('withdraw_main_total_amount');
            }
            if (!Schema::hasColumn('dashboard_summary_daily', 'withdraw_main_total_users')) {
                $table->unsignedInteger('withdraw_main_total_users')->default(0)->after('withdraw_main_total_count');
            }
            if (!Schema::hasColumn('dashboard_summary_daily', 'withdraw_main_pending_amount')) {
                $table->decimal('withdraw_main_pending_amount', 18, 2)->default(0)->after('withdraw_main_total_users');
            }
            if (!Schema::hasColumn('dashboard_summary_daily', 'withdraw_main_pending_count')) {
                $table->unsignedInteger('withdraw_main_pending_count')->default(0)->after('withdraw_main_pending_amount');
            }

            if (!Schema::hasColumn('dashboard_summary_daily', 'withdraw_free_total_amount')) {
                $table->decimal('withdraw_free_total_amount', 18, 2)->default(0)->after('withdraw_main_pending_count');
            }
            if (!Schema::hasColumn('dashboard_summary_daily', 'withdraw_free_total_count')) {
                $table->unsignedInteger('withdraw_free_total_count')->default(0)->after('withdraw_free_total_amount');
            }
            if (!Schema::hasColumn('dashboard_summary_daily', 'withdraw_free_total_users')) {
                $table->unsignedInteger('withdraw_free_total_users')->default(0)->after('withdraw_free_total_count');
            }
            if (!Schema::hasColumn('dashboard_summary_daily', 'withdraw_free_pending_amount')) {
                $table->decimal('withdraw_free_pending_amount', 18, 2)->default(0)->after('withdraw_free_total_users');
            }
            if (!Schema::hasColumn('dashboard_summary_daily', 'withdraw_free_pending_count')) {
                $table->unsignedInteger('withdraw_free_pending_count')->default(0)->after('withdraw_free_pending_amount');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('dashboard_summary_daily')) {
            return;
        }

        Schema::table('dashboard_summary_daily', function (Blueprint $table) {
            foreach ([
                'withdraw_main_total_amount',
                'withdraw_main_total_count',
                'withdraw_main_total_users',
                'withdraw_main_pending_amount',
                'withdraw_main_pending_count',
                'withdraw_free_total_amount',
                'withdraw_free_total_count',
                'withdraw_free_total_users',
                'withdraw_free_pending_amount',
                'withdraw_free_pending_count',
            ] as $column) {
                if (Schema::hasColumn('dashboard_summary_daily', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
