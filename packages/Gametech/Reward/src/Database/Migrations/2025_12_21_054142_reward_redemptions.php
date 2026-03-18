<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reward_redemptions', function (Blueprint $table) {

            // audit/debug
            $table->string('request_ip', 45)->nullable()->after('idempotency_key')->index();
            $table->string('request_ua', 500)->nullable()->after('request_ip');
            $table->string('request_source', 30)->nullable()->after('request_ua')->index(); // wallet_modal, pwa, api, admin

            // point accounting flags
            $table->boolean('point_debited')->default(true)->after('point_cost_snapshot')->index();
            $table->dateTime('refunded_at')->nullable()->after('rejected_at')->index();
            $table->unsignedInteger('refunded_by')->nullable()->after('refunded_at')->index(); // employees.code

            // explicit redeemed time (separate from created_at)
            $table->dateTime('redeemed_at')->nullable()->after('payload_snapshot')->index();

            // indexes for limit queries
            $table->index(['member_id', 'reward_id', 'created_at'], 'reward_redemptions_member_reward_created_idx');
            $table->index(['member_id', 'reward_id', 'status'], 'reward_redemptions_member_reward_status_idx');

            // FK for refunded_by (optional)
            $table->foreign('refunded_by')
                ->references('code')
                ->on('employees')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('reward_redemptions', function (Blueprint $table) {
            // drop FK first
            $table->dropForeign(['refunded_by']);

            $table->dropIndex('reward_redemptions_member_reward_created_idx');
            $table->dropIndex('reward_redemptions_member_reward_status_idx');

            $table->dropColumn([
                'request_ip',
                'request_ua',
                'request_source',
                'point_debited',
                'refunded_at',
                'refunded_by',
                'redeemed_at',
            ]);
        });
    }
};
