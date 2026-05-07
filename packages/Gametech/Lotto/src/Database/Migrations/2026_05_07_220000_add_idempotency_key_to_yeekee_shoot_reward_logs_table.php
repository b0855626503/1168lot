<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('yeekee_shoot_reward_logs')) {
            return;
        }

        Schema::table('yeekee_shoot_reward_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('yeekee_shoot_reward_logs', 'idempotency_key')) {
                $table->string('idempotency_key', 191)->nullable()->after('reward_ref_type');
                $table->unique('idempotency_key', 'yeekee_reward_idempotency_key_unique');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('yeekee_shoot_reward_logs')) {
            return;
        }

        Schema::table('yeekee_shoot_reward_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('yeekee_shoot_reward_logs', 'idempotency_key')) {
                $table->dropUnique('yeekee_reward_idempotency_key_unique');
                $table->dropColumn('idempotency_key');
            }
        });
    }
};
