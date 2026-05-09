<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lotto_result_correction_items')) {
            return;
        }

        Schema::create('lotto_result_correction_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('correction_id')->constrained('lotto_result_corrections')->onDelete('cascade');
            $table->foreignId('draw_id')->constrained('lotto_draws')->onDelete('cascade');
            $table->foreignId('ticket_id')->constrained('lotto_tickets')->onDelete('cascade');
            $table->unsignedBigInteger('member_id');
            $table->decimal('old_win_amount', 14, 2)->default(0);
            $table->decimal('new_win_amount', 14, 2)->default(0);
            $table->decimal('reverse_required_amount', 14, 2)->default(0);
            $table->decimal('reverse_debited_amount', 14, 2)->default(0);
            $table->decimal('reverse_remaining_amount', 14, 2)->default(0);
            $table->decimal('new_credit_amount', 14, 2)->default(0);
            $table->enum('status', ['unchanged', 'credited', 'reversed', 'reverse_partial', 'reverse_failed', 'completed', 'failed'])->default('unchanged');
            $table->unsignedBigInteger('reverse_wallet_txn_id')->nullable();
            $table->unsignedBigInteger('new_credit_wallet_txn_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['correction_id', 'status']);
            $table->index(['draw_id', 'ticket_id']);
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotto_result_correction_items');
    }
};
