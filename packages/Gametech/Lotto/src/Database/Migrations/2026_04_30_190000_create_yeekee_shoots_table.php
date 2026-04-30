<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('yeekee_shoots')) {
            return;
        }

        Schema::create('yeekee_shoots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('yeekee_round_id')->constrained('yeekee_rounds')->onDelete('cascade');
            $table->foreignId('lotto_draw_id')->constrained('lotto_draws')->onDelete('cascade');
            $table->foreignId('market_id')->constrained('lotto_markets')->onDelete('cascade');
            $table->unsignedBigInteger('member_id');
            $table->unsignedInteger('position');
            $table->string('number_text', 5);
            $table->unsignedInteger('number_value');
            $table->dateTime('submitted_at');
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->unique(['yeekee_round_id', 'position'], 'yeekee_shoots_round_position_unique');
            $table->index(['yeekee_round_id', 'member_id'], 'yeekee_shoots_round_member_idx');
            $table->index(['market_id', 'submitted_at'], 'yeekee_shoots_market_submitted_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yeekee_shoots');
    }
};
