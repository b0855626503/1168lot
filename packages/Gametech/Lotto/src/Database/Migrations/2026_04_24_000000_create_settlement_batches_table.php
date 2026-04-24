<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settlement_batches')) {
            return;
        }

        Schema::create('settlement_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('draw_id')->constrained('lotto_draws')->onDelete('cascade');
            $table->string('lottery_type', 100);
            $table->string('market', 100)->nullable();
            $table->enum('mode', ['settlement', 'backfill', 'replay'])->default('settlement');
            $table->enum('status', ['pending', 'settled', 'failed', 'voided'])->default('pending');
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->unsignedInteger('total_bets_processed')->default(0);
            $table->unsignedInteger('total_winning_records')->default(0);
            $table->decimal('total_stake', 14, 2)->default(0);
            $table->decimal('total_payout', 14, 2)->default(0);
            $table->text('error_message')->nullable();
            $table->string('triggered_by', 100)->nullable();
            $table->string('idempotency_key', 255)->unique('settlement_batches_idempotency_key_unique');
            $table->timestamps();

            $table->index('draw_id');
            $table->index(['draw_id', 'status']);
            $table->index('status');
            $table->index('started_at');
            $table->index('finished_at');
            $table->index('lottery_type');
            $table->index('market');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_batches');
    }
};
