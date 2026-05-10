<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lotto_result_corrections')) {
            return;
        }

        Schema::create('lotto_result_corrections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('draw_id')->constrained('lotto_draws')->onDelete('cascade');
            $table->json('old_result_number')->nullable();
            $table->json('new_result_number')->nullable();
            $table->string('old_result_hash', 128)->nullable();
            $table->string('new_result_hash', 128)->nullable();
            $table->string('source', 32)->default('manual');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'previewed', 'processing', 'completed', 'partial_failed', 'failed'])->default('pending');
            $table->unsignedInteger('ticket_count')->default(0);
            $table->unsignedInteger('affected_ticket_count')->default(0);
            $table->unsignedInteger('old_winning_ticket_count')->default(0);
            $table->unsignedInteger('new_winning_ticket_count')->default(0);
            $table->decimal('total_reversed_amount', 14, 2)->default(0);
            $table->decimal('total_reverse_failed_amount', 14, 2)->default(0);
            $table->decimal('total_new_payout_amount', 14, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['draw_id', 'status']);
            $table->index('created_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotto_result_corrections');
    }
};
