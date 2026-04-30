<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('yeekee_rounds')) {
            return;
        }

        Schema::create('yeekee_rounds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('market_id')->constrained('lotto_markets')->onDelete('cascade');
            $table->foreignId('lotto_draw_id')->constrained('lotto_draws')->onDelete('cascade');
            $table->date('round_date');
            $table->unsignedInteger('round_no')->default(1);
            $table->dateTime('bet_open_at');
            $table->dateTime('bet_close_at');
            $table->dateTime('shoot_open_at');
            $table->dateTime('shoot_close_at');
            $table->dateTime('result_compute_at');
            $table->dateTime('expected_settlement_deadline_at');
            $table->string('status', 32)->default('draft');
            $table->json('config_snapshot_json')->nullable();
            $table->timestamps();

            $table->unique('lotto_draw_id', 'yeekee_rounds_lotto_draw_id_unique');
            $table->index(['market_id', 'round_date'], 'yeekee_rounds_market_date_idx');
            $table->index(['market_id', 'status'], 'yeekee_rounds_market_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yeekee_rounds');
    }
};
