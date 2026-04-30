<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('yeekee_shoot_reward_logs')) {
            return;
        }

        Schema::create('yeekee_shoot_reward_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('yeekee_round_id')->constrained('yeekee_rounds')->onDelete('cascade');
            $table->unsignedBigInteger('member_id');
            $table->unsignedInteger('position');
            $table->decimal('credit_amount', 12, 2);
            $table->string('reward_ref_type', 32)->default('YEEKEE_SHOOT_REWARD');
            $table->timestamps();

            $table->unique(['yeekee_round_id', 'member_id', 'position'], 'yeekee_reward_round_member_position_unique');
            $table->index(['member_id', 'created_at'], 'yeekee_reward_member_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yeekee_shoot_reward_logs');
    }
};
