<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dashboard_summary_daily')) {
            return;
        }

        Schema::table('dashboard_summary_daily', function (Blueprint $table) {
            if (!Schema::hasColumn('dashboard_summary_daily', 'deposit_success_users')) {
                $table->unsignedInteger('deposit_success_users')->default(0)->after('deposit_success_count');
            }

            if (!Schema::hasColumn('dashboard_summary_daily', 'deposit_pending_users')) {
                $table->unsignedInteger('deposit_pending_users')->default(0)->after('deposit_pending_count');
            }

            if (!Schema::hasColumn('dashboard_summary_daily', 'deposit_reject_users')) {
                $table->unsignedInteger('deposit_reject_users')->default(0)->after('deposit_reject_count');
            }

            if (!Schema::hasColumn('dashboard_summary_daily', 'deposit_deleted_users')) {
                $table->unsignedInteger('deposit_deleted_users')->default(0)->after('deposit_deleted_count');
            }
        });

        $defaultWebCode = trim((string) config('app.name', 'default')) ?: 'default';

        DB::table('dashboard_summary_daily')
            ->where(function ($query) {
                $query->whereNull('web_code')->orWhere('web_code', '');
            })
            ->update(['web_code' => $defaultWebCode]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('dashboard_summary_daily')) {
            return;
        }

        Schema::table('dashboard_summary_daily', function (Blueprint $table) {
            foreach (['deposit_success_users', 'deposit_pending_users', 'deposit_reject_users', 'deposit_deleted_users'] as $column) {
                if (Schema::hasColumn('dashboard_summary_daily', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
