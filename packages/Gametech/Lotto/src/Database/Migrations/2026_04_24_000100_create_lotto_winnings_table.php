<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lotto_winnings')) {
            return;
        }

        Schema::create('lotto_winnings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('draw_id')->constrained('lotto_draws')->onDelete('cascade');
            $table->unsignedBigInteger('bet_id');
            $table->unsignedBigInteger('bet_item_id');
            $table->string('ticket_no', 64)->nullable();
            $table->unsignedInteger('user_id');
            $table->string('username', 255)->nullable();
            $table->string('lottery_type', 100);
            $table->string('market', 100)->nullable();
            $table->string('bet_type', 100);
            $table->string('number', 32);
            $table->decimal('stake', 14, 2);
            $table->decimal('odds', 10, 4);
            $table->decimal('payout', 14, 2)->nullable();
            $table->decimal('net_profit', 14, 2)->nullable();
            $table->string('result_number', 64)->nullable();
            $table->string('matched_rule', 100)->nullable();
            $table->enum('status', ['pending', 'settled', 'credited', 'failed', 'voided'])->default('pending');
            $table->foreignId('settlement_batch_id')->constrained('settlement_batches')->onDelete('restrict');
            $table->dateTime('settled_at')->nullable();
            $table->dateTime('credited_at')->nullable();
            $table->timestamps();

            $table->unique(['draw_id', 'bet_item_id'], 'lotto_winnings_draw_item_unique');
            $table->index('draw_id');
            $table->index(['draw_id', 'user_id']);
            $table->index(['draw_id', 'bet_type']);
            $table->index(['draw_id', 'number']);
            $table->index('status');
            $table->index('settled_at');
            $table->index('settlement_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotto_winnings');
    }
};
