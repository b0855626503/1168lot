<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dashboard_summary_daily', function (Blueprint $table) {
            $defaultWebCode = trim((string) config('app.name', 'default')) ?: 'default';

            $table->id();

            $table->date('summary_date');
            $table->string('web_code', 64)->default($defaultWebCode);

            $table->unsignedInteger('register_total')->default(0);
            $table->unsignedInteger('register_direct')->default(0);
            $table->unsignedInteger('register_referral')->default(0);
            $table->unsignedInteger('register_campaign')->default(0);

            $table->decimal('deposit_total_amount', 18, 2)->default(0);
            $table->unsignedInteger('deposit_total_count')->default(0);
            $table->unsignedInteger('deposit_total_users')->default(0);

            $table->decimal('deposit_success_amount', 18, 2)->default(0);
            $table->unsignedInteger('deposit_success_count')->default(0);
            $table->unsignedInteger('deposit_success_users')->default(0);

            $table->decimal('deposit_pending_amount', 18, 2)->default(0);
            $table->unsignedInteger('deposit_pending_count')->default(0);
            $table->unsignedInteger('deposit_pending_users')->default(0);

            $table->decimal('deposit_reject_amount', 18, 2)->default(0);
            $table->unsignedInteger('deposit_reject_count')->default(0);
            $table->unsignedInteger('deposit_reject_users')->default(0);

            $table->decimal('deposit_deleted_amount', 18, 2)->default(0);
            $table->unsignedInteger('deposit_deleted_count')->default(0);
            $table->unsignedInteger('deposit_deleted_users')->default(0);

            $table->decimal('withdraw_total_amount', 18, 2)->default(0);
            $table->unsignedInteger('withdraw_total_count')->default(0);
            $table->unsignedInteger('withdraw_total_users')->default(0);

            $table->decimal('withdraw_pending_amount', 18, 2)->default(0);
            $table->unsignedInteger('withdraw_pending_count')->default(0);

            $table->decimal('bonus_deposit_amount', 18, 2)->default(0);
            $table->unsignedInteger('bonus_deposit_count')->default(0);

            $table->decimal('bonus_activity_amount', 18, 2)->default(0);
            $table->unsignedInteger('bonus_activity_count')->default(0);

            $table->decimal('bonus_manual_amount', 18, 2)->default(0);
            $table->unsignedInteger('bonus_manual_count')->default(0);

            $table->decimal('bonus_total_amount', 18, 2)->default(0);
            $table->unsignedInteger('bonus_total_count')->default(0);

            $table->decimal('net_amount', 18, 2)->default(0);

            $table->unsignedInteger('register_deposit_count')->default(0);
            $table->unsignedInteger('register_referral_deposit_count')->default(0);
            $table->unsignedInteger('first_deposit_count')->default(0);
            $table->unsignedInteger('repeat_deposit_count')->default(0);
            $table->unsignedInteger('register_confirmed_count')->default(0);

            $table->decimal('staff_add_amount', 18, 2)->default(0);
            $table->decimal('staff_reduce_amount', 18, 2)->default(0);
            $table->unsignedInteger('staff_adjust_count')->default(0);

            $table->timestamp('last_synced_at')->nullable();
            $table->unsignedInteger('metric_version')->default(1);

            $table->timestamps();

            $table->unique(['summary_date', 'web_code'], 'uk_dashboard_summary_daily_date_web');
            $table->index(['web_code', 'summary_date'], 'idx_dashboard_summary_daily_web_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_summary_daily');
    }
};
